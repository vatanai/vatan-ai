<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Generation;
use App\Models\GrowthAttribution;
use App\Models\GrowthContent;
use App\Models\GrowthDataSource;
use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Services\GrowthAnalyticsService;
use App\Services\GrowthDataIngestionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GrowthModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->string('password_reveal')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $migration = require database_path('migrations/2026_08_18_020000_create_growth_tracking_tables.php');
        $migration->up();
        $attributionMigration = require database_path('migrations/2026_08_18_020100_create_growth_attributions_table.php');
        $attributionMigration->up();
        $dataSourceMigration = require database_path('migrations/2026_08_18_020200_create_growth_data_source_tables.php');
        $dataSourceMigration->up();
    }

    public function test_growth_pages_are_part_of_the_protected_admin_dashboard(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تست رشد',
            'email' => 'growth-admin@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin');

        foreach ([
            route('admin.growth.monitor'),
            route('admin.growth.overview'),
            route('admin.growth.channels', 'instagram'),
            route('admin.growth.contents'),
            route('admin.growth.links.index'),
            route('admin.growth.links.create'),
            route('admin.growth.links.analytics'),
            route('admin.growth.section', 'attribution'),
            route('admin.growth.section', 'settings'),
            route('admin.growth.data-sources.index'),
        ] as $url) {
            $this->get($url)->assertOk()->assertSee('مرکز رشد وطن');
        }
    }

    public function test_daily_content_metrics_keep_raw_data_and_respect_primary_source(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر داده رشد',
            'email' => 'growth-data@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $manualSource = GrowthDataSource::where('slug', 'instagram-manual')->firstOrFail();

        $this->actingAs($admin, 'admin')->post(route('admin.growth.contents.store'), [
            'title' => 'محتوای تست منبع داده',
            'channel' => 'instagram',
            'status' => 'active',
            'data_source_id' => $manualSource->id,
            'metric_date' => today()->toDateString(),
            'impressions' => 1200,
            'engagements' => 180,
            'comments' => 30,
            'shares' => 12,
            'likes' => 140,
            'saves' => 25,
        ])->assertRedirect();

        $content = GrowthContent::where('title', 'محتوای تست منبع داده')->firstOrFail();
        self::assertSame(1200, (int) $content->impressions);
        self::assertDatabaseHas('growth_raw_records', [
            'growth_data_source_id' => $manualSource->id,
            'normalization_status' => 'normalized',
        ]);
        self::assertDatabaseHas('growth_content_daily_metrics', [
            'growth_content_id' => $content->id,
            'growth_data_source_id' => $manualSource->id,
            'views' => 1200,
            'likes' => 140,
        ]);

        $apiSource = GrowthDataSource::create([
            'name' => 'رابط تست اینستاگرام',
            'slug' => 'instagram-api-test',
            'source_type' => 'api',
            'channel' => 'instagram',
            'ingestion_method' => 'api',
            'data_types' => ['views'],
            'is_active' => true,
            'priority' => 5,
        ]);
        $manualSource->mappings()->where('growth_metric', 'views')->update(['is_primary' => false]);
        $apiSource->mappings()->create([
            'source_field' => 'views_count',
            'growth_metric' => 'views',
            'transform' => 'integer',
            'is_primary' => true,
            'fallback_order' => 1,
            'is_active' => true,
        ]);

        app(GrowthDataIngestionService::class)->recordContentMetrics(
            $content,
            $apiSource,
            ['views_count' => '2,400'],
            today(),
            $admin->id,
            'api',
        );

        self::assertSame(2400, (int) $content->fresh()->impressions);
        self::assertSame(180, (int) $content->fresh()->engagements);
        $this->get(route('admin.growth.data-sources.index', ['source' => $apiSource->slug]))
            ->assertOk()->assertSee('رابط تست اینستاگرام')->assertSee('آخرین داده‌های خام');
    }

    public function test_click_and_page_open_are_stored_as_two_independent_events(): void
    {
        $link = GrowthLink::query()->create([
            'title' => 'لینک تست رهگیری',
            'slug' => 'growth-test-link',
            'destination_url' => 'https://example.test/destination',
            'channel' => 'instagram',
            'campaign' => 'test-campaign',
            'is_active' => true,
        ]);

        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (iPhone) AppleWebKit Safari/605.1.15',
            'Referer' => 'https://instagram.com/',
        ])->get(route('growth.redirect', $link));

        $response->assertRedirect();
        $click = GrowthEvent::query()->where('event_type', GrowthEvent::TYPE_CLICK)->firstOrFail();
        self::assertStringContainsString('vtn_click='.$click->event_uuid, $response->headers->get('Location'));
        self::assertSame('mobile', $click->device_type);
        self::assertSame('instagram', $click->source);

        $this->get(route('growth.page-open', [
            'click_id' => $click->event_uuid,
            'page_url' => 'https://example.test/destination?vtn_click='.$click->event_uuid,
        ]))->assertNoContent();

        $this->get(route('growth.page-open', [
            'click_id' => $click->event_uuid,
            'page_url' => 'https://example.test/destination?vtn_click='.$click->event_uuid,
        ]))->assertNoContent();

        self::assertSame(1, GrowthEvent::query()->where('event_type', GrowthEvent::TYPE_CLICK)->count());
        self::assertSame(1, GrowthEvent::query()->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count());
        self::assertDatabaseHas('growth_events', [
            'event_type' => GrowthEvent::TYPE_PAGE_OPEN,
            'parent_event_uuid' => $click->event_uuid,
        ]);
    }

    public function test_closed_24_hour_windows_are_persisted_without_removing_raw_events(): void
    {
        $link = GrowthLink::query()->create([
            'title' => 'لینک تست پنجره',
            'slug' => 'growth-window-test',
            'destination_url' => 'https://example.test/window',
            'channel' => 'telegram',
            'is_active' => true,
        ]);
        $occurredAt = now('Asia/Tehran')->subDay()->startOfDay()->addHour()->utc();

        GrowthEvent::query()->create([
            'growth_link_id' => $link->id,
            'event_uuid' => '11111111-1111-4111-8111-111111111111',
            'event_type' => GrowthEvent::TYPE_CLICK,
            'visitor_id' => '22222222-2222-4222-8222-222222222222',
            'source' => 'telegram',
            'device_type' => 'desktop',
            'is_new_visitor' => true,
            'occurred_at' => $occurredAt,
        ]);

        $analytics = app(GrowthAnalyticsService::class);
        $analytics->syncClosedWindows($link);
        $analytics->syncClosedWindows($link);

        self::assertSame(1, $link->snapshots()->count());
        self::assertSame(1, $link->events()->count());
        self::assertSame(1, (int) $link->snapshots()->first()->total_clicks);
        self::assertSame(1, (int) $link->snapshots()->first()->failed_opens);
    }

    public function test_generation_is_attributed_without_changing_the_generation_record(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('input_image');
            $table->string('output_image')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        $link = GrowthLink::query()->create([
            'title' => 'لینک تست تبدیل',
            'slug' => 'growth-conversion-test',
            'destination_url' => 'https://example.test/create',
            'channel' => 'youtube',
            'is_active' => true,
        ]);
        $visitorId = '33333333-3333-4333-8333-333333333333';
        GrowthEvent::query()->create([
            'growth_link_id' => $link->id,
            'event_uuid' => '44444444-4444-4444-8444-444444444444',
            'event_type' => GrowthEvent::TYPE_CLICK,
            'visitor_id' => $visitorId,
            'source' => 'youtube',
            'occurred_at' => now(),
        ]);

        $request = Request::create('/app/create', 'POST', [], ['vtn_growth_visitor' => $visitorId]);
        app()->instance('request', $request);

        $generation = Generation::query()->create([
            'product_id' => 10,
            'input_image' => 'test/input.jpg',
            'status' => 'pending',
        ]);
        $generation->update(['status' => 'completed', 'output_image' => 'test/output.jpg']);

        self::assertSame('completed', $generation->fresh()->status);
        self::assertSame(1, GrowthAttribution::where('stage', 'generation_started')->count());
        self::assertSame(1, GrowthAttribution::where('stage', 'generation_completed')->count());
        self::assertDatabaseHas('growth_attributions', [
            'generation_id' => $generation->id,
            'growth_link_id' => $link->id,
            'visitor_id' => $visitorId,
        ]);
    }
}
