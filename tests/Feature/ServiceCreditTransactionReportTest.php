<?php

namespace Tests\Feature;

use App\Models\AiProviderRequest;
use App\Models\Admin;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\ServiceCreditTransactionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class ServiceCreditTransactionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_saved_output_is_one_row_without_duplicate_request_or_order_rows(): void
    {
        $user = User::query()->create([
            'name' => 'کاربر گزارش',
            'phone' => '09120000000',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'processing_status' => 'completed',
            'final_credits' => 50,
            'ai_model' => 'test/model',
            'ai_provider' => 'test-provider',
            'completed_at' => now(),
        ]);
        $providerRequest = AiProviderRequest::query()->create([
            'provider' => 'test-provider',
            'order_id' => $order->id,
            'external_request_id' => 'request-one',
            'status' => 'success',
            'actual_cost_usd' => 0.02,
            'completed_at' => now(),
        ]);

        $images = collect(['outputs/one.webp', 'outputs/two.webp'])->map(fn (string $path) =>
            GeneratedImage::query()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'ai_provider_request_id' => $providerRequest->id,
                'image_path' => $path,
            ])
        );

        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000,
            'source' => 'تست',
            'online' => false,
            'at' => now(),
        ]);

        $request = Request::create('/admin/service-credits', 'GET', [
            'source' => 'user',
            'per_page' => 100,
        ]);
        $report = (new ServiceCreditTransactionReport($exchange))->build($request);
        $rows = collect($report['transactions']->items());

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            $images->map(fn (GeneratedImage $image) => 'image-'.$image->id)->all(),
            $rows->pluck('id')->all(),
        );
        $this->assertTrue($rows->every(fn (array $row) => $row['status_key'] === 'completed'));
        $this->assertTrue($rows->every(fn (array $row) => count($row['output_urls']) === 1));
    }

    public function test_saved_video_output_is_reported_with_cost_and_playback_url(): void
    {
        $user = User::query()->create([
            'name' => 'کاربر ویدیو',
            'phone' => '09120000001',
            'status' => 'active',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'processing_status' => 'completed',
            'final_credits' => 18,
            'ai_model' => 'test/video-model',
            'ai_provider' => 'test-provider',
            'completed_at' => now(),
        ]);
        $providerRequest = AiProviderRequest::query()->create([
            'provider' => 'test-provider',
            'order_id' => $order->id,
            'external_request_id' => 'video-request-one',
            'status' => 'success',
            'actual_cost_usd' => 0.07,
            'completed_at' => now(),
        ]);
        $video = GeneratedVideo::query()->create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'ai_provider_request_id' => $providerRequest->id,
            'status' => 'completed',
            'video_url' => 'https://example.test/generated.mp4',
            'cost' => 0.07,
            'completed_at' => now(),
        ]);

        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000,
            'source' => 'تست',
            'online' => false,
            'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', [
            'source' => 'user',
            'per_page' => 100,
        ]));
        $row = collect($report['transactions']->items())->firstWhere('id', 'video-'.$video->id);

        $this->assertNotNull($row);
        $this->assertSame('video', $row['media_type']);
        $this->assertSame(0.07, (float) $row['amount_usd']);
        $this->assertSame(18.0, (float) $row['credits']);
        $this->assertSame(['https://example.test/generated.mp4'], $row['output_urls']);
    }

    public function test_per_page_is_limited_to_supported_values(): void
    {
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->twice()->andReturn([
            'rate' => 600000,
            'source' => 'تست',
            'online' => false,
            'at' => now(),
        ]);
        $service = new ServiceCreditTransactionReport($exchange);

        $this->assertSame(50, $service->build(Request::create('/', 'GET', ['per_page' => 50]))['transactions']->perPage());
        $this->assertSame(20, $service->build(Request::create('/', 'GET', ['per_page' => 999]))['transactions']->perPage());
    }

    public function test_failed_provider_request_does_not_show_estimated_cost_as_a_charge(): void
    {
        $providerRequest = AiProviderRequest::query()->create([
            'provider' => 'fal',
            'external_request_id' => 'failed-fal-request',
            'status' => 'failed',
            'estimated_cost_usd' => 1,
            'error_message' => 'provider timeout',
            'submitted_at' => now(),
            'completed_at' => now(),
        ]);

        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $rows = collect((new ServiceCreditTransactionReport($exchange))
            ->build(Request::create('/', 'GET', ['source' => 'user', 'per_page' => 100]))['transactions']->items());
        $row = $rows->firstWhere('id', 'request-' . $providerRequest->id);

        $this->assertNotNull($row);
        $this->assertNull($row['amount_usd']);
        $this->assertNull($row['amount_toman']);
    }

    public function test_note_and_details_stay_inside_the_single_transaction_row(): void
    {
        $transaction = [
            'id' => 'image-1', 'source_key' => 'user', 'source_label' => 'ساخت کاربر',
            'date_jalali' => '۱۴۰۵/۰۶/۰۲  ۱۲:۰۰', 'date_gregorian' => '2026/08/24 12:00',
            'actor_label' => 'کاربر', 'user_name' => 'محسن', 'user_contact' => '09120000000',
            'product_name' => 'محصول تست', 'order_number' => 'ORD-1', 'reference' => 'IMG-1',
            'provider' => 'Replicate', 'model' => 'test/model', 'latency_seconds' => 12.4, 'retries' => 1,
            'status_key' => 'completed', 'status_label' => 'موفق', 'error' => null,
            'output_urls' => ['https://example.test/output.webp'], 'amount_usd' => 0.02,
            'amount_toman' => 1200, 'credits' => 20, 'detail_url' => 'https://example.test/orders/1',
            'note' => 'جزئیات تکمیلی همین خروجی',
        ];
        $transactions = new LengthAwarePaginator([$transaction], 1, 20, 1);

        $html = view('admin.service-credits.partials.usage-table', compact('transactions'))->render();

        $this->assertSame(1, substr_count($html, 'class="credit-report-row"'));
        $this->assertStringNotContainsString('credit-report-note', $html);
        $this->assertStringContainsString('جزئیات تکمیلی همین خروجی', $html);
    }

    public function test_ajax_table_request_returns_only_the_report_region_with_requested_page_size(): void
    {
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);
        $this->app->instance(ExchangeRateService::class, $exchange);
        $admin = Admin::query()->create([
            'name' => 'مدیر تست', 'email' => 'service-credit@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.service-credits.index', [
            'usage_table' => 1, 'source' => 'user', 'per_page' => 50,
        ]));

        $response->assertOk()
            ->assertSee('data-credit-per-page', false)
            ->assertSee('<option value="50" selected>', false)
            ->assertDontSee('مدیریت اعتبار و مصرف سرویس‌ها');
    }
}
