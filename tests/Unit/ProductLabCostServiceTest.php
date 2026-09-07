<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\LabExperiment;
use App\Models\LabRun;
use App\Models\Order;
use App\Models\Product;
use App\Services\ProductLabCostService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ProductLabCostServiceTest extends TestCase
{
    public function test_returns_the_three_product_grades_with_current_toman_rate(): void
    {
        $product = new Product();
        $experiment = new LabExperiment();
        $runs = new Collection();

        foreach ([
            ['standard', 'استاندارد', 0.10],
            ['professional', 'حرفه‌ای', 0.20],
            ['best', 'بهترین خروجی', 0.30],
        ] as [$key, $label, $cost]) {
            $model = new AiModel([
                'provider' => 'replicate',
                'openrouter_model_id' => 'test/' . $key,
                'name' => $label,
            ]);
            $run = new LabRun([
                'grade_key' => $key,
                'grade_label' => $label,
                'status' => 'completed',
                'actual_cost_usd' => $cost,
                'estimated_cost_usd' => 0,
                'is_selected' => true,
            ]);
            $run->setRelation('aiModel', $model);
            $runs->push($run);
        }

        $experiment->setRelation('runs', $runs);
        $product->setRelation('latestLabExperiment', $experiment);

        $summary = app(ProductLabCostService::class)->summarize($product, 100_000);

        self::assertSame(['استاندارد', 'حرفه‌ای', 'بهترین خروجی'], array_column($summary, 'label'));
        self::assertSame([0.1, 0.2, 0.3], array_column($summary, 'usd'));
        self::assertSame([1000, 2000, 3000], array_column($summary, 'toman'));
        self::assertSame(['actual', 'actual', 'actual'], array_column($summary, 'source'));
    }

    public function test_maps_legacy_economic_grade_to_standard_for_existing_experiments(): void
    {
        $product = new Product();
        $experiment = new LabExperiment();
        $run = new LabRun([
            'grade_key' => 'economic',
            'status' => 'completed',
            'estimated_cost_usd' => 0.07,
            'is_selected' => true,
        ]);
        $experiment->setRelation('runs', collect([$run]));
        $product->setRelation('latestLabExperiment', $experiment);

        $summary = app(ProductLabCostService::class)->summarize($product, 100_000);

        self::assertSame('استاندارد', $summary[0]['label']);
        self::assertSame(0.07, $summary[0]['usd']);
        self::assertSame(700, $summary[0]['toman']);
    }

    public function test_prefers_the_latest_successful_user_build_and_marks_it_as_actual(): void
    {
        $product = new Product();
        $model = new AiModel([
            'provider' => 'replicate',
            'openrouter_model_id' => 'test/user-build',
            'name' => 'User build model',
        ]);
        $providerRequest = new AiProviderRequest([
            'status' => 'completed',
            'actual_cost_usd' => 0.42,
            'estimated_cost_usd' => 0.30,
        ]);
        $providerRequest->setRelation('aiModel', $model);
        $order = new Order([
            'model_tier_key' => 'economy',
            'input_payload' => ['main_quality' => 'standard'],
            'completed_at' => now(),
        ]);
        $order->setRelation('providerRequests', collect([$providerRequest]));

        $summary = app(ProductLabCostService::class)->summarize(
            $product,
            100_000,
            collect([$model]),
            collect([$order]),
        );

        self::assertSame(0.42, $summary[0]['usd']);
        self::assertSame('actual', $summary[0]['source']);
        self::assertSame('user', $summary[0]['origin']);
        self::assertSame('actual', $summary[0]['tone']);
        self::assertStringContainsString('ساخت موفق کاربر', $summary[0]['source_label']);
    }

    public function test_falls_back_to_model_catalog_price_with_estimated_tone(): void
    {
        $product = new Product([
            'model_configuration' => [
                'quality_models' => [
                    'standard' => ['primary' => ['provider' => 'replicate', 'model_id' => 'test/catalog']],
                ],
            ],
        ]);
        $model = new AiModel([
            'provider' => 'replicate',
            'openrouter_model_id' => 'test/catalog',
            'name' => 'Catalog model',
            'cost_per_generation_usd' => 0.08,
        ]);

        $summary = app(ProductLabCostService::class)->summarize($product, 100_000, collect([$model]));

        self::assertSame(0.08, $summary[0]['usd']);
        self::assertSame('estimated', $summary[0]['source']);
        self::assertSame('model', $summary[0]['origin']);
        self::assertSame('model', $summary[0]['tone']);
    }

    public function test_successful_user_build_with_official_model_price_is_verified_not_actual(): void
    {
        $product = new Product();
        $model = new AiModel([
            'provider' => 'replicate',
            'openrouter_model_id' => 'google/nano-banana-2-lite',
            'name' => 'Nano Banana 2 Lite',
            'pricing_config' => [
                'source' => 'replicate_official',
                'price_source' => 'official_model_page',
                'unit_price' => 0.034,
            ],
        ]);
        $providerRequest = new AiProviderRequest([
            'status' => 'completed',
            'actual_cost_usd' => null,
            'estimated_cost_usd' => 0.034,
        ]);
        $providerRequest->setRelation('aiModel', $model);
        $order = new Order([
            'model_tier_key' => 'economy',
            'input_payload' => ['main_quality' => 'standard'],
            'completed_at' => now(),
        ]);
        $order->setRelation('providerRequests', collect([$providerRequest]));

        $summary = app(ProductLabCostService::class)->summarize(
            $product,
            100_000,
            collect([$model]),
            collect([$order]),
        );

        self::assertSame(0.034, $summary[0]['usd']);
        self::assertSame('verified', $summary[0]['tone']);
        self::assertSame('verified', $summary[0]['source']);
        self::assertSame('user', $summary[0]['origin']);
        self::assertStringContainsString('قیمت رسمی مدل', $summary[0]['source_label']);
        self::assertNotSame('actual', $summary[0]['source']);
    }
}
