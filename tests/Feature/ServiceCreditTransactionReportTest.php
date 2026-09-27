<?php

namespace Tests\Feature;

use App\Models\AiProviderRequest;
use App\Models\Admin;
use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\Order;
use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditTransaction;
use App\Models\User;
use App\Models\UserGalleryItem;
use App\Services\ExchangeRateService;
use App\Services\ServiceCreditTransactionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ServiceCreditTransactionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_build_is_one_row_even_with_multiple_outputs(): void
    {
        Storage::fake('public');
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

        collect(['outputs/one.webp', 'outputs/two.webp'])->each(function (string $path) use ($user, $order, $providerRequest): void {
            Storage::disk('public')->put($path, 'image');
            GeneratedImage::query()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
                'ai_provider_request_id' => $providerRequest->id,
                'image_path' => $path,
            ]);
        });

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

        $this->assertCount(1, $rows);
        $this->assertSame('order-'.$order->id, $rows->first()['id']);
        $this->assertSame('completed', $rows->first()['status_key']);
        $this->assertCount(2, $rows->first()['output_urls']);
        $this->assertSame(0.02, (float) $rows->first()['amount_usd']);
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
        $row = collect($report['transactions']->items())->firstWhere('id', 'order-'.$order->id);

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

    public function test_report_can_filter_by_models_that_exist_in_build_history(): void
    {
        Order::query()->create([
            'status' => 'completed', 'processing_status' => 'completed', 'ai_model' => 'model/alpha',
        ]);
        $matchingOrder = Order::query()->create([
            'status' => 'completed', 'processing_status' => 'completed', 'ai_model' => 'model/beta',
        ]);
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', [
            'source' => 'user', 'model' => 'model/beta', 'per_page' => 100,
        ]), false);

        $rows = collect($report['transactions']->items());
        $this->assertCount(1, $rows);
        $this->assertSame('order-'.$matchingOrder->id, $rows->first()['id']);
        $this->assertTrue($report['models']->contains(fn (array $model): bool => $model['key'] === 'model/alpha'));
        $this->assertTrue($report['models']->contains(fn (array $model): bool => $model['key'] === 'model/beta'));
    }

    public function test_credit_center_metrics_are_built_without_loading_report_media_or_pagination(): void
    {
        Order::query()->create([
            'status' => 'completed', 'processing_status' => 'completed', 'ai_model' => 'model/center',
            'ai_provider' => 'fal', 'completed_at' => now()->subMinute(),
        ]);
        Order::query()->create([
            'status' => 'failed', 'processing_status' => 'failed', 'ai_model' => 'model/center',
            'ai_provider' => 'fal', 'completed_at' => now(),
        ]);

        $metrics = (new ServiceCreditTransactionReport(Mockery::mock(ExchangeRateService::class)))
            ->overviewMetrics(600000, 1);

        $this->assertCount(1, $metrics['timeline']);
        $this->assertSame(2, $metrics['activitySummary']['count']);
        $this->assertSame(1, $metrics['activitySummary']['success']);
        $this->assertSame(1, $metrics['activitySummary']['failed']);
        $this->assertTrue($metrics['providerStats']->contains(fn (array $provider): bool => $provider['key'] === 'fal'));
    }

    public function test_removed_build_transactions_page_redirects_to_credit_center(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر مرکز اعتبار', 'email' => 'credit-center@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.service-credits.build-transactions'))
            ->assertRedirect(route('admin.service-credits.providers'));
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

    public function test_retries_are_one_build_and_only_actual_costs_are_added(): void
    {
        $order = Order::query()->create([
            'status' => 'completed', 'processing_status' => 'completed', 'final_credits' => 12,
        ]);
        foreach ([0.01, 0.02] as $index => $cost) {
            AiProviderRequest::query()->create([
                'provider' => 'fal', 'order_id' => $order->id,
                'external_request_id' => 'retry-'.$index, 'status' => $index ? 'completed' : 'failed',
                'actual_cost_usd' => $cost, 'estimated_cost_usd' => 1,
            ]);
        }
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', ['source' => 'user']));
        $this->assertSame(1, $report['summary']['count']);
        $this->assertSame(1, $report['summary']['success']);
        $this->assertEqualsWithDelta(0.03, $report['summary']['usd'], 0.000001);
        $this->assertEqualsWithDelta(1800, $report['summary']['toman'], 0.001);
    }

    public function test_charge_is_not_counted_as_usage_cost(): void
    {
        $account = ServiceCreditAccount::query()->firstOrFail();
        foreach (['charge' => 10, 'usage' => 2] as $type => $amount) {
            ServiceCreditTransaction::query()->create([
                'service_credit_account_id' => $account->id, 'type' => $type,
                'amount' => $amount, 'occurred_at' => now(),
            ]);
        }
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', ['source' => 'ledger']));
        $this->assertSame(2, $report['summary']['count']);
        $this->assertSame(2.0, $report['summary']['usd']);
        $this->assertSame(120000.0, $report['summary']['toman']);
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

        $response = $this->actingAs($admin, 'admin')->get(route('admin.service-credits.transactions', [
            'fragment' => 1, 'source' => 'user', 'per_page' => 50,
        ]));

        $response->assertOk()
            ->assertSee('data-credit-per-page', false)
            ->assertSee('<option value="50" selected>', false)
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_transactions_page_contains_only_the_report_and_its_title(): void
    {
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);
        $this->app->instance(ExchangeRateService::class, $exchange);
        $admin = Admin::query()->create([
            'name' => 'مدیر تست', 'email' => 'credit-view@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('admin.service-credits.transactions'));
        $response->assertOk()
            ->assertSee('ساخت و تراکنش‌ها')
            ->assertSee('گزارش کامل مصرف و تراکنش‌ها')
            ->assertDontSee('تایم‌لاین رخدادهای اخیر')
            ->assertDontSee('سلامت سرویس‌ها');
    }

    public function test_existing_order_input_uses_an_authenticated_preview_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/personal/order-input.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
        $user = User::query()->create([
            'name' => 'کاربر عکس ورودی', 'phone' => '09120000008', 'status' => 'active',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'processing_status' => 'completed',
            'input_payload' => ['source_upload_paths' => ['uploads/personal/order-input.png']],
            'completed_at' => now(),
        ]);
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', [
            'source' => 'user', 'per_page' => 100,
        ]));
        $row = collect($report['transactions']->items())->firstWhere('id', 'order-'.$order->id);
        $previewUrl = route('admin.service-credits.order-input', [$order, 0]);

        $this->assertNotNull($row);
        $this->assertSame($previewUrl, $row['input_media'][0]['preview_url']);
        $this->get($previewUrl)->assertRedirect(route('admin.login'));

        $admin = Admin::query()->create([
            'name' => 'مدیر ورودی‌ها', 'email' => 'input-preview@example.test', 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin')->get($previewUrl)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_database_pagination_is_not_limited_to_five_hundred_source_rows(): void
    {
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);
        $now = now();
        collect(range(1, 505))->chunk(100)->each(function ($numbers) use ($now): void {
            DB::table('orders')->insert($numbers->map(fn (int $number): array => [
                'order_number' => 'SCALE-'.$number,
                'build_uuid' => (string) Str::uuid(),
                'status' => 'completed',
                'payment_status' => 'paid',
                'processing_status' => 'completed',
                'original_credits' => 0,
                'discount_credits' => 0,
                'final_credits' => 0,
                'refunded_credits' => 0,
                'attempts' => 0,
                'source' => 'test',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', [
            'source' => 'user', 'per_page' => 20, 'page' => 26,
        ]), false);

        $this->assertSame(505, $report['transactions']->total());
        $this->assertSame(505, $report['summary']['count']);
        $this->assertCount(5, $report['transactions']->items());
    }

    public function test_build_uuid_is_shared_by_order_requests_and_outputs(): void
    {
        $user = User::query()->create(['name' => 'کاربر شناسه ساخت', 'phone' => '09123334444', 'status' => 'active']);
        $order = Order::query()->create([
            'user_id' => $user->id, 'status' => 'processing', 'processing_status' => 'processing',
        ]);
        $providerRequest = AiProviderRequest::query()->create([
            'provider' => 'fal', 'order_id' => $order->id,
            'external_request_id' => 'shared-build-request', 'status' => 'queued',
        ]);
        $image = GeneratedImage::query()->create([
            'order_id' => $order->id, 'ai_provider_request_id' => $providerRequest->id,
            'image_path' => 'missing/shared.webp',
        ]);
        $video = GeneratedVideo::query()->create([
            'order_id' => $order->id, 'ai_provider_request_id' => $providerRequest->id,
            'status' => 'queued',
        ]);
        $galleryItem = UserGalleryItem::query()->create([
            'user_id' => $user->id, 'order_id' => $order->id, 'source_type' => 'input_image',
            'original_path' => 'users/input.webp', 'disk' => 'user_gallery', 'size' => 0,
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertNotNull($order->build_uuid);
        $this->assertSame($order->build_uuid, $providerRequest->build_uuid);
        $this->assertSame($order->build_uuid, $image->build_uuid);
        $this->assertSame($order->build_uuid, $video->build_uuid);
        $this->assertSame($order->build_uuid, $galleryItem->build_uuid);
    }

    public function test_missing_local_output_never_creates_a_broken_preview_request(): void
    {
        Storage::fake('public');
        $image = GeneratedImage::query()->create(['image_path' => 'missing/output.webp']);
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->once()->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        $report = (new ServiceCreditTransactionReport($exchange))->build(Request::create('/', 'GET', [
            'source' => 'user', 'per_page' => 100,
        ]));
        $row = collect($report['transactions']->items())->firstWhere('id', 'image-'.$image->id);

        $this->assertNotNull($row);
        $this->assertSame([], $row['output_urls']);
        $this->assertNull($row['output_preview_url']);
        $this->assertTrue($row['media_missing']);
        $this->assertStringContainsString('موجود نیست', $row['note']);
    }
}
