<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\ModelTierService;
use Tests\TestCase;

class ModelTierServiceTest extends TestCase
{
    public function test_user_plan_selects_the_expected_model_tier(): void
    {
        $plan = new Plan();
        $plan->model_tier_key = 'pro';
        $user = new User();
        $user->setRelation('plan', $plan);

        $service = app(ModelTierService::class);

        $this->assertSame('pro', $service->tierKeyForUser($user));
        $this->assertSame('free', $service->tierKeyForUser(new User()));
    }

    public function test_product_execution_uses_the_tier_primary_and_fallback_models(): void
    {
        $product = new Product();
        $product->primary_model = 'legacy/model';
        $product->ai_provider = 'openrouter';
        $product->model_configuration = [
            'tiers' => [
                'business' => [
                    'primary' => ['model_id' => 'primary/model', 'provider' => 'replicate'],
                    'fallback' => ['model_id' => 'backup/model', 'provider' => 'fal'],
                ],
            ],
        ];

        $execution = app(ModelTierService::class)->executionProduct($product, 'business');

        $this->assertNotSame($product, $execution);
        $this->assertSame('primary/model', $execution->primary_model);
        $this->assertSame('replicate', $execution->ai_provider);
        $this->assertSame(['backup/model'], $execution->fallback_models);
        $this->assertSame(['fal'], $execution->fallback_model_providers);
    }

    public function test_free_user_only_uses_standard_and_paid_user_can_use_all_qualities(): void
    {
        $service = app(ModelTierService::class);

        $this->assertNull($service->resolveOutputQuality(null, 'best'));
        $this->assertNull($service->resolveOutputQuality(null, 'professional'));
        $this->assertSame('free', $service->resolveOutputQuality(null, 'standard')['execution_tier_key']);

        $paidPlan = new Plan(['billing_type' => 'one_time', 'price' => 970000]);
        $paidUser = new User();
        $paidUser->setRelation('plan', $paidPlan);

        $this->assertSame([true, false, false], array_column($service->outputQualityOptions(null), 'available'));
        $this->assertSame([true, true, true], array_column($service->outputQualityOptions($paidUser), 'available'));
        $this->assertSame('pro', $service->resolveOutputQuality($paidUser, 'professional')['execution_tier_key']);
        $this->assertSame(50, $service->resolveOutputQuality($paidUser, 'best')['credits']);
        $this->assertSame('business', $service->resolveOutputQuality($paidUser, 'best')['execution_tier_key']);
    }

    public function test_execution_model_follows_the_selected_output_quality(): void
    {
        $product = new Product([
            'primary_model' => 'legacy/model',
            'ai_provider' => 'openrouter',
            'model_configuration' => [
                'quality_models' => [
                    'standard' => [
                        'primary' => ['model_id' => 'standard/model', 'provider' => 'replicate'],
                        'fallback' => ['model_id' => 'standard/backup', 'provider' => 'fal'],
                    ],
                    'professional' => [
                        'primary' => ['model_id' => 'pro/model', 'provider' => 'replicate'],
                        'fallback' => ['model_id' => 'pro/backup', 'provider' => 'fal'],
                    ],
                ],
                'free_quality_models' => [
                    'standard' => [
                        'primary' => ['model_id' => 'gift/model', 'provider' => 'openrouter'],
                        'fallback' => ['model_id' => 'gift/backup', 'provider' => 'openrouter'],
                    ],
                ],
            ],
        ]);

        $service = app(ModelTierService::class);
        $freeExecution = $service->executionProductForQuality($product, null, 'standard', 'free');
        $professionalExecution = $service->executionProductForQuality($product, null, 'professional', 'pro');

        $this->assertSame('standard/model', $freeExecution->primary_model);
        $this->assertSame('replicate', $freeExecution->ai_provider);
        $this->assertSame('pro/model', $professionalExecution->primary_model);
        $this->assertSame('replicate', $professionalExecution->ai_provider);
    }

    public function test_each_paid_output_quality_uses_its_own_primary_and_fallback_pair(): void
    {
        $product = new Product([
            'primary_model' => 'legacy/model',
            'ai_provider' => 'openrouter',
            'model_configuration' => [
                'quality_models' => [
                    'standard' => ['primary' => ['model_id' => 'standard/model', 'provider' => 'fal'], 'fallback' => ['model_id' => 'standard/backup', 'provider' => 'replicate']],
                    'professional' => ['primary' => ['model_id' => 'professional/model', 'provider' => 'fal'], 'fallback' => ['model_id' => 'professional/backup', 'provider' => 'replicate']],
                    'best' => ['primary' => ['model_id' => 'best/model', 'provider' => 'replicate'], 'fallback' => ['model_id' => 'best/backup', 'provider' => 'fal']],
                ],
            ],
        ]);

        $service = app(ModelTierService::class);
        $paidPlan = new Plan(['billing_type' => 'one_time', 'price' => 970000]);
        $paidUser = new User();
        $paidUser->setRelation('plan', $paidPlan);
        foreach ([
            'standard' => ['standard/model', 'standard/backup'],
            'professional' => ['professional/model', 'professional/backup'],
            'best' => ['best/model', 'best/backup'],
        ] as $quality => [$primary, $fallback]) {
            $execution = $service->executionProductForQuality($product, $paidUser, $quality, 'economy');
            $this->assertSame($primary, $execution->primary_model);
            $this->assertSame([$fallback], $execution->fallback_models);
        }
    }

    public function test_disabled_quality_architecture_uses_the_legacy_execution_model(): void
    {
        $product = new Product([
            'primary_model' => 'legacy/model',
            'ai_provider' => 'openrouter',
            'model_configuration' => [
                'quality_architecture_enabled' => false,
                'quality_models' => [
                    'standard' => [
                        'primary' => ['model_id' => 'new/model', 'provider' => 'fal'],
                    ],
                ],
            ],
        ]);

        $execution = app(ModelTierService::class)->executionProductForQuality($product, null, 'standard', 'free');

        $this->assertSame('legacy/model', $execution->primary_model);
        $this->assertSame('openrouter', $execution->ai_provider);
    }
}
