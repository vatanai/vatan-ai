<?php

namespace Tests\Unit;

use App\Models\AiModel;
use App\Services\AiProviderCredentials;
use App\Services\FalAiBillingService;
use App\Services\ProviderPricingService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FalAiBillingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.fal.api_key' => 'test-fal-key',
            'services.fal.platform_base_url' => 'https://api.fal.test',
        ]);
    }

    public function test_reads_official_endpoint_price_without_hardcoding_it(): void
    {
        Http::fake([
            'https://api.fal.test/v1/models/pricing*' => Http::response([
                'prices' => [['endpoint_id' => 'fal-ai/flux/schnell', 'unit_price' => 0.003, 'unit' => 'image', 'currency' => 'USD']],
            ]),
        ]);

        $result = (new ProviderPricingService(new FalAiBillingService(new AiProviderCredentials())))
            ->estimate(new AiModel(['provider' => 'fal', 'external_model_id' => 'fal-ai/flux/schnell']));

        $this->assertSame(0.003, $result['usd']);
        $this->assertSame('fal.ai pricing API', $result['source']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.fal.test/v1/models/pricing?endpoint_id=fal-ai%2Fflux%2Fschnell');
    }

    public function test_returns_unavailable_when_fal_does_not_report_a_price(): void
    {
        Http::fake(['https://api.fal.test/v1/models/pricing*' => Http::response(['prices' => []])]);

        $result = (new ProviderPricingService(new FalAiBillingService(new AiProviderCredentials())))
            ->estimate(new AiModel(['provider' => 'fal', 'external_model_id' => 'fal-ai/unknown']));

        $this->assertNull($result['usd']);
        $this->assertSame('قیمت Fal.ai در دسترس نیست', $result['source']);
    }

    public function test_reads_request_cost_from_billing_events(): void
    {
        Http::fake([
            'https://api.fal.test/v1/models/billing-events*' => Http::response([
                'billing_events' => [['request_id' => 'req-1', 'cost_total' => 0.0042, 'endpoint_id' => 'fal-ai/flux/schnell']],
            ]),
        ]);

        $event = (new FalAiBillingService(new AiProviderCredentials()))->billingEvent('req-1');

        $this->assertSame(0.0042, $event['cost_total']);
        $this->assertSame('req-1', $event['request_id']);
    }

    public function test_sums_official_usage_time_series_by_final_cost_not_quantity(): void
    {
        Http::fake([
            'https://api.fal.test/v1/models/usage*' => Http::response([
                'time_series' => [[
                    'bucket' => '2026-08-29T00:00:00Z',
                    'results' => [
                        ['endpoint_id' => 'fal-ai/flux/dev', 'quantity' => 4, 'unit_price' => 0.1, 'cost_total' => 0.32, 'cost' => 0.32],
                        ['endpoint_id' => 'fal-ai/flux/schnell', 'quantity' => 2, 'unit_price' => 0.003, 'cost_total' => 0.006, 'cost' => 0.006],
                    ],
                ]],
                'next_cursor' => null,
            ]),
        ]);

        $usage = (new FalAiBillingService(new AiProviderCredentials()))->usage('2026-08-29T00:00:00Z', '2026-08-30T00:00:00Z');

        $this->assertTrue($usage['available']);
        $this->assertSame(0.326, $usage['total_usage']);
    }

    public function test_prefers_final_cost_over_subtotal_and_supports_nano_usd(): void
    {
        $service = new FalAiBillingService(new AiProviderCredentials());

        $this->assertSame(0.32, $service->costFromBillingEvent([
            'cost_subtotal' => 0.4,
            'cost_discount' => 0.08,
            'cost_total' => 0.32,
        ]));
        $this->assertSame(0.0042, $service->costFromBillingEvent([
            'cost_estimate_nano_usd' => 4_200_000,
        ]));
    }
}
