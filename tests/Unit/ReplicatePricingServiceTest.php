<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Services\ProviderPricingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReplicatePricingServiceTest extends TestCase
{
    public function test_reads_resolution_tier_from_official_replicate_model_page(): void
    {
        Cache::forget('replicate.pricing.tiers.' . sha1('google/nano-banana-pro'));
        Http::fake([
            'https://replicate.com/google/nano-banana-pro' => Http::response(
                '<script type="application/json">' . json_encode([
                    'billingConfig' => [
                        'current_tiers' => [
                            ['criteria' => [['value' => '1K']], 'prices' => [['price' => '$0.15', 'metric_display' => 'output image']]],
                            ['criteria' => [['value' => '4K']], 'prices' => [['price' => '$0.30', 'metric_display' => 'output image']]],
                        ],
                    ],
                ]) . '</script>'
            ),
        ]);

        $model = new AiModel([
            'provider' => 'replicate',
            'external_model_id' => 'google/nano-banana-pro',
        ]);

        $result = app(ProviderPricingService::class)->estimate($model, 1, true, ['resolution' => '2160']);

        $this->assertSame(0.3, $result['usd']);
        $this->assertSame('Replicate official model page', $result['source']);
        $this->assertSame('4K', $result['resolution']);
    }

    public function test_normalizes_form_quality_to_replicate_resolution(): void
    {
        Cache::forget('replicate.pricing.tiers.' . sha1('google/nano-banana-pro'));
        Http::fake([
            'https://replicate.com/google/nano-banana-pro' => Http::response(
                '<script type="application/json">' . json_encode([
                    'billingConfig' => [
                        'current_tiers' => [
                            ['criteria' => [['value' => '1K']], 'prices' => [['price' => '$0.15', 'metric_display' => 'output image']]],
                        ],
                    ],
                ]) . '</script>'
            ),
        ]);

        $model = new AiModel([
            'provider' => 'replicate',
            'external_model_id' => 'google/nano-banana-pro',
        ]);

        $result = app(ProviderPricingService::class)->estimate($model, 2, true, ['resolution' => '720']);

        $this->assertSame(0.3, $result['usd']);
        $this->assertSame('1K', $result['resolution']);
    }
}
