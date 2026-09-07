<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\MarketingCampaign;
use App\Models\MarketingContent;
use App\Models\MarketingCostEvent;
use App\Models\MarketingEvent;
use App\Models\MarketingIntegration;
use App\Models\MarketingLink;
use App\Models\MarketingOperationRun;
use App\Models\MarketingScenario;
use App\Models\MarketingScenarioVersion;
use App\Services\MetaInstagramApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MarketingTechnologyModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_marketing_technology_pages_are_protected_and_render_independently(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست تکنولوژی مارکتینگ',
            'email' => 'marketing-technology@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->get(route('admin.marketing-technology.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.marketing-technology.index'))
            ->assertOk()
            ->assertSee('تکنولوژی مارکتینگ')
            ->assertSee('Meta API')
            ->assertSee('بدون تغییر در بخش فعلی رشد');

        foreach ([
            'content-calendar',
            'scenarios',
            'inbox',
            'reports',
            'costs',
            'integrations',
            'logs',
        ] as $section) {
            $this->get(route('admin.marketing-technology.'.$section))
                ->assertOk()
                ->assertSee('تکنولوژی مارکتینگ')
                ->assertSee('بزودی');
        }
    }

    public function test_marketing_technology_menu_is_present_without_replacing_growth_menu(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست منوی مارکتینگ',
            'email' => 'marketing-menu@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');
        $html = view('admin.partials.sidebar')->render();

        $this->assertSame(1, substr_count($html, 'تکنولوژی مارکتینگ'));
        $this->assertSame(1, substr_count($html, 'id="marketing-technology-submenu-new"'));
        $this->assertSame(1, substr_count($html, 'id="sales-growth-submenu-new"'));
        $this->assertStringContainsString('href="'.route('admin.growth.monitor').'"', $html);
    }

    public function test_scenarios_page_renders_the_latest_version_preview(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست نمایش سناریو',
            'email' => 'marketing-scenario-view@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $scenario = MarketingScenario::query()->create([
            'name' => 'سناریوی نمایش پورتره',
            'code' => 'portrait-view-test',
            'channel' => 'instagram',
            'status' => 'active',
            'active_version' => 1,
        ]);
        $scenario->versions()->create([
            'version' => 1,
            'trigger_keyword' => 'پورتره',
            'opening_message' => 'پیام تست برای نمایش نسخه‌ی فعال',
            'followup_url' => 'https://aivatan.com/app/explore',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.marketing-technology.scenarios'))
            ->assertOk()
            ->assertSee('سناریوی نمایش پورتره')
            ->assertSee('پیام تست برای نمایش نسخه‌ی فعال')
            ->assertSee('تنظیم شده');
    }

    public function test_marketing_technology_data_foundation_keeps_the_full_operation_chain(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست مدل داده',
            'email' => 'marketing-data@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        foreach (['marketing_campaigns', 'marketing_contents', 'marketing_scenarios', 'marketing_scenario_versions', 'marketing_links', 'marketing_operation_runs', 'marketing_events', 'marketing_cost_events'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "جدول {$table} ساخته نشده است.");
        }

        $campaign = MarketingCampaign::query()->create(['name' => 'کمپین تست پورتره', 'code' => 'portrait-test', 'created_by' => $admin->id]);
        $scenario = MarketingScenario::query()->create(['name' => 'سناریوی تست کامنت', 'code' => 'portrait-comment-test', 'created_by' => $admin->id]);
        $version = $scenario->versions()->create(['version' => 1, 'trigger_keyword' => 'پورتره', 'opening_message' => 'پیام تست']);
        $content = MarketingContent::query()->create(['marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'title' => 'ریلز تست', 'created_by' => $admin->id]);
        $link = MarketingLink::query()->create(['marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'code' => 'portrait-test-link', 'title' => 'لینک تست', 'destination_url' => 'https://aivatan.com/app/explore']);
        $run = MarketingOperationRun::query()->create(['run_uuid' => (string) Str::uuid(), 'operation_type' => 'scenario.test', 'idempotency_key' => 'portrait-test-run']);
        $event = MarketingEvent::query()->create(['event_uuid' => (string) Str::uuid(), 'event_type' => 'comment.received', 'channel' => 'instagram', 'marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'marketing_scenario_id' => $scenario->id, 'marketing_operation_run_id' => $run->id, 'marketing_link_id' => $link->id, 'occurred_at' => now()]);
        MarketingCostEvent::query()->create(['marketing_operation_run_id' => $run->id, 'marketing_campaign_id' => $campaign->id, 'marketing_content_id' => $content->id, 'provider' => 'test', 'service' => 'scenario', 'units' => 1, 'unit_cost_usd' => 0, 'fx_rate_toman' => 0, 'incurred_at' => now()]);

        $this->assertTrue($campaign->contents()->whereKey($content)->exists());
        $this->assertTrue($scenario->versions()->whereKey($version)->exists());
        $this->assertTrue($content->links()->whereKey($link)->exists());
        $this->assertTrue($event->run()->whereKey($run)->exists());
        $this->assertSame('comment.received', $event->fresh()->event_type);
    }

    public function test_content_calendar_and_scenario_forms_create_versioned_records_and_queue_operations(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست عملیات',
            'email' => 'marketing-operations@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        $this->post(route('admin.marketing-technology.scenarios.store'), [
            'name' => 'سناریوی پورتره',
            'code' => 'portrait-comment',
            'channel' => 'instagram',
            'trigger_type' => 'comment_keyword',
            'status' => 'active',
            'trigger_keyword' => 'پورتره',
            'opening_message' => 'لینک ساخت برای شما آماده است.',
            'followup_url' => 'https://aivatan.com/app/explore?q=portrait',
        ])->assertRedirect();

        $scenario = MarketingScenario::query()->where('code', 'portrait-comment')->firstOrFail();
        $this->assertSame(1, $scenario->versions()->count());

        $this->post(route('admin.marketing-technology.content-calendar.store'), [
            'title' => 'ریلز تست پورتره',
            'marketing_scenario_id' => $scenario->id,
            'content_type' => 'reel',
            'status' => 'ready',
            'keyword' => 'پورتره',
            'caption' => 'کپشن تست',
        ])->assertRedirect();

        $content = MarketingContent::query()->where('title', 'ریلز تست پورتره')->firstOrFail();
        $this->post(route('admin.marketing-technology.content-calendar.queue', $content))->assertRedirect();

        $this->assertSame('ready', $content->fresh()->status);
        $this->assertTrue(MarketingOperationRun::query()->where('marketing_content_id', $content->id)->where('status', 'queued')->exists());
    }

    public function test_reports_render_real_internal_and_marketing_event_metrics_with_channel_filter(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست گزارش',
            'email' => 'marketing-reports@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $campaign = MarketingCampaign::query()->create(['name' => 'کمپین گزارش', 'code' => 'report-test', 'created_by' => $admin->id]);
        $scenario = MarketingScenario::query()->create(['name' => 'سناریوی گزارش', 'code' => 'report-scenario', 'created_by' => $admin->id]);

        MarketingContent::query()->create([
            'marketing_campaign_id' => $campaign->id,
            'marketing_scenario_id' => $scenario->id,
            'title' => 'محتوای گزارش',
            'channel' => 'instagram',
            'status' => 'published',
            'published_at' => now(),
        ]);
        collect([
            ['event_uuid' => (string) Str::uuid(), 'event_type' => 'comment.received', 'channel' => 'instagram', 'marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'occurred_at' => now()],
            ['event_uuid' => (string) Str::uuid(), 'event_type' => 'dm.received', 'channel' => 'instagram', 'marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'occurred_at' => now()],
            ['event_uuid' => (string) Str::uuid(), 'event_type' => 'link.clicked', 'channel' => 'instagram', 'marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'occurred_at' => now()],
            ['event_uuid' => (string) Str::uuid(), 'event_type' => 'link.clicked', 'channel' => 'telegram', 'marketing_campaign_id' => $campaign->id, 'marketing_scenario_id' => $scenario->id, 'occurred_at' => now()],
        ])->each(fn (array $event) => MarketingEvent::query()->create($event));

        $this->actingAs($admin, 'admin');
        $this->get(route('admin.marketing-technology.reports', ['channel' => 'instagram']))
            ->assertOk()
            ->assertSee('گزارش و تحلیل')
            ->assertSee('بینش خودکار')
            ->assertSee('۲');

        $this->get(route('admin.marketing-technology.reports', ['channel' => 'telegram']))
            ->assertOk()
            ->assertSee('گزارش و تحلیل')
            ->assertSee('۱');
    }

    public function test_cost_center_calculates_and_persists_manual_costs_and_flags_missing_amount(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست هزینه',
            'email' => 'marketing-costs@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.marketing-technology.costs'))
            ->assertOk()
            ->assertSee('مرکز هزینه')
            ->assertSee('فرمول محاسبه');

        $this->post(route('admin.marketing-technology.costs.store'), [
            'provider' => 'OpenRouter',
            'service' => 'تولید کپشن',
            'units' => 2,
            'unit' => 'درخواست',
            'unit_cost_usd' => 0.5,
            'fx_rate_toman' => 200000,
            'status' => 'estimated',
            'incurred_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_cost_events', [
            'provider' => 'OpenRouter',
            'cost_toman' => 200000,
            'status' => 'estimated',
        ]);

        $this->post(route('admin.marketing-technology.costs.store'), [
            'provider' => 'نامشخص',
            'service' => 'هزینه بدون نرخ',
            'units' => 1,
            'status' => 'estimated',
            'incurred_at' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('marketing_cost_events', [
            'provider' => 'نامشخص',
            'status' => 'needs_review',
            'cost_toman' => 0,
        ]);
    }

    public function test_meta_integration_is_encrypted_and_read_only_connection_test_works(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست متا',
            'email' => 'marketing-meta@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $this->actingAs($admin, 'admin');

        $this->get(route('admin.marketing-technology.integrations'))
            ->assertOk()
            ->assertSee('اتصال‌ها و سلامت سرویس')
            ->assertSee('توکن دسترسی');

        $token = 'test-meta-token-123';
        $this->post(route('admin.marketing-technology.integrations.meta.store'), [
            'name' => 'اینستاگرام تست',
            'instagram_user_id' => '178900000001',
            'page_id' => '1290249447505058',
            'access_token' => $token,
        ])->assertRedirect();

        $integration = MarketingIntegration::query()->firstOrFail();
        $this->assertSame($token, $integration->credentials['access_token']);
        $this->assertFalse(str_contains((string) DB::table('marketing_integrations')->whereKey($integration->id)->value('credentials'), $token));

        Http::fake([
            'https://graph.instagram.com/v24.0/178900000001*' => Http::response(['id' => '178900000001', 'username' => 'vatan.test', 'account_type' => 'BUSINESS'], 200),
            'https://graph.instagram.com/v24.0/178900000001/media*' => Http::response(['data' => []], 200),
        ]);

        $this->post(route('admin.marketing-technology.integrations.meta.test', $integration))->assertRedirect();
        $this->assertSame('healthy', $integration->fresh()->status);

        Http::fake([
            'https://graph.instagram.com/v24.0/178900000001/messages' => Http::response(['message_id' => 'message-1'], 200),
        ]);
        $result = app(MetaInstagramApiService::class)->sendPrivateReply($integration->fresh(), 'comment-1', 'لینک برای شما آماده است.');
        $this->assertTrue($result['ok']);
        Http::assertSent(fn ($request) => $request->url() === 'https://graph.instagram.com/v24.0/178900000001/messages'
            && $request->data()['recipient']['comment_id'] === 'comment-1'
            && $request->data()['message']['text'] === 'لینک برای شما آماده است.');
    }

    public function test_meta_webhook_verification_and_idempotent_event_ingestion_work(): void
    {
        Config::set('services.meta.webhook_verify_token', 'webhook-test-token');
        Config::set('services.meta.app_secret', 'app-secret');

        $this->get(route('webhooks.meta.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'webhook-test-token',
            'hub.challenge' => 'challenge-123',
        ]))->assertOk()->assertSee('challenge-123');

        $payload = json_encode(['entry' => [['changes' => [['field' => 'comments', 'value' => ['id' => 'comment-123', 'from' => ['id' => 'actor-1'], 'text' => 'پورتره']]]]]], JSON_UNESCAPED_UNICODE);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'app-secret');
        $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $signature];

        $this->call('POST', route('webhooks.meta.receive'), [], [], [], $headers, $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'stored' => 1]);
        $this->call('POST', route('webhooks.meta.receive'), [], [], [], $headers, $payload)
            ->assertOk()
            ->assertJson(['ok' => true, 'stored' => 0]);

        $this->assertDatabaseHas('marketing_events', ['event_type' => 'comment.received', 'external_id' => 'comment-123', 'channel' => 'instagram']);
    }

    public function test_inbox_and_operation_logs_render_and_failed_operations_can_be_retried(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست صندوق',
            'email' => 'marketing-inbox@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $run = MarketingOperationRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'operation_type' => 'content.prepare',
            'status' => 'failed',
            'attempt' => 1,
            'idempotency_key' => 'retry-test-run',
            'error_code' => 'TEST_ERROR',
            'error_message' => 'خطای آزمایشی',
        ]);
        MarketingEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => 'dm.received',
            'channel' => 'instagram',
            'processing_status' => 'received',
            'external_id' => 'inbox-test-message',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin, 'admin');
        $this->get(route('admin.marketing-technology.inbox'))
            ->assertOk()
            ->assertSee('صندوق گفتگوها')
            ->assertSee('دایرکت دریافتی');
        $this->get(route('admin.marketing-technology.logs'))
            ->assertOk()
            ->assertSee('لاگ عملیات')
            ->assertSee('تلاش مجدد');
        $this->post(route('admin.marketing-technology.logs.retry', $run))->assertRedirect();

        $this->assertDatabaseHas('marketing_operation_runs', ['id' => $run->id, 'status' => 'queued', 'attempt' => 2, 'error_code' => null]);
    }
}
