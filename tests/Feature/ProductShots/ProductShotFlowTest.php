<?php

namespace Tests\Feature\ProductShots;

use App\Models\GeneratedImage;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShotBatchItem;
use App\Services\ProductShots\ProductShotFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProductShotFlowTest extends TestCase
{
    use RefreshDatabase;
    use ProductShotTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        ProductShotFeature::resetSchemaCache();
    }

    private function startBatch($user, Product $product, ?array $shots = null): array
    {
        $pre = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('serum.jpg', 900, 1100),
        ], ['Accept' => 'application/json'])->assertOk()->json();

        $shots ??= $product->enabledProductShots()->map(fn ($ps) => $ps->shot->key)->all();

        return $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), [
            'uploads' => [['id' => $pre['upload_id']]],
            'shots' => $shots,
            'aspect_ratio' => '9:16',
        ])->assertCreated()->json('batch');
    }

    private function runShot(array $batch, int $index, $user)
    {
        $itemId = $batch['items'][$index]['id'];

        return $this->actingAs($user)->postJson($batch['run_urls'][$itemId]);
    }

    public function test_flag_off_hides_everything(): void
    {
        $user = $this->makeUser();
        $product = $this->makeShotProduct();

        $this->actingAs($user)->get(route('app.create', ['product' => $product->route_slug]))->assertNotFound();
        $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('a.jpg'),
        ])->assertNotFound();
        $this->actingAs($user)->get(route('app.product', $product->route_slug))->assertNotFound();
        $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), [])->assertNotFound();
    }

    public function test_admins_only_audience_blocks_regular_users(): void
    {
        $this->enableShots('admins');
        $user = $this->makeUser();
        $product = $this->makeShotProduct();

        $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('a.jpg'),
        ])->assertNotFound();
    }

    public function test_whitelist_by_phone_opens_feature(): void
    {
        $user = $this->makeUser(100, ['phone' => '09121234567']);
        $this->enableShots('whitelist', ['whitelist_phones' => ['+989121234567']]);

        $this->assertTrue(app(ProductShotFeature::class)->availableFor($user));
        $this->assertFalse(app(ProductShotFeature::class)->availableFor($this->makeUser()));
    }

    public function test_env_master_switch_overrides_panel(): void
    {
        $this->enableShots('public');
        config(['product_shots.enabled' => false]);

        $this->assertFalse(app(ProductShotFeature::class)->availableFor($this->makeUser()));
    }

    public function test_full_pack_charges_each_successful_shot_once(): void
    {
        $this->enableShots('public');
        $this->mockRouter();
        $this->mockVision();
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(3);

        $batch = $this->startBatch($user, $product);
        $this->assertCount(3, $batch['items']);
        $this->assertSame(30, $batch['credits_quoted']);

        foreach ([0, 1, 2] as $i) {
            $res = $this->runShot($batch, $i, $user)->assertOk()->json();
            $this->assertSame('completed', $res['item']['status']);
            $this->assertNotNull($res['item']['image_url']);
        }

        $this->assertSame(70, (int) $user->fresh()->tokens);
        $this->assertSame(3, Order::query()->where('source', 'product_shot')->where('status', 'completed')->count());
        $this->assertSame(3, GeneratedImage::query()->where('user_id', $user->id)->count());
        $this->assertSame('completed', $this->getJson(route('app.product-shots.batches.show', $batch['uuid']))->json('batch.status'));

        // ورودی پس از اتمام کامل پک پاک می‌شود
        $sources = \App\Models\ShotBatch::query()->first()->source_paths;
        Storage::disk('public')->assertMissing($sources[0]);

        // اجرای دوباره‌ی شات کامل‌شده هزینه‌ی دوباره ندارد
        $this->runShot($batch, 0, $user);
        $this->assertSame(70, (int) $user->fresh()->tokens);
    }

    public function test_one_failed_shot_refunds_only_itself_and_can_retry(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->mockRouter([null, new RuntimeException('provider timeout'), null, null]);
        $this->mockVision();
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(3);
        $batch = $this->startBatch($user, $product);

        $this->runShot($batch, 0, $user)->assertOk();
        $failed = $this->runShot($batch, 1, $user)->assertStatus(422)->json();
        $this->assertSame('failed', $failed['item']['status']);
        $this->assertTrue($failed['item']['can_retry']);
        $this->assertSame(10, $failed['item']['credits_refunded']);
        $this->runShot($batch, 2, $user)->assertOk();

        $this->assertSame(80, (int) $user->fresh()->tokens);
        $this->assertSame('partial', \App\Models\ShotBatch::query()->first()->status);
        $order = Order::query()->where('processing_status', 'failed')->firstOrFail();
        $this->assertSame(10, (int) $order->refunded_credits);

        // تلاش دوباره همان شات
        $this->runShot($batch, 1, $user)->assertOk();
        $this->assertSame(70, (int) $user->fresh()->tokens);
        $this->assertSame('completed', \App\Models\ShotBatch::query()->first()->status);
    }

    public function test_insufficient_credits_fails_without_charge_or_attempt(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->mockRouter();
        $this->mockVision();
        $user = $this->makeUser(15);
        $product = $this->makeShotProduct(2);
        $batch = $this->startBatch($user, $product);

        $this->runShot($batch, 0, $user)->assertOk();
        $res = $this->runShot($batch, 1, $user)->assertStatus(402)->json();

        $this->assertSame('INSUFFICIENT_CREDITS', $res['error_code']);
        $this->assertSame(5, (int) $user->fresh()->tokens);
        $this->assertSame(0, (int) ShotBatchItem::query()->find($batch['items'][1]['id'])->attempts);
    }

    public function test_qc_failure_triggers_one_free_retry(): void
    {
        $this->enableShots('public');
        $this->mockRouter([], 0.02);
        $this->mockVision([
            ['same_product' => false, 'fidelity_score' => 2, 'issues' => ['wrong color']],
            ['same_product' => true, 'fidelity_score' => 5, 'issues' => []],
        ]);
        $user = $this->makeUser(50);
        $product = $this->makeShotProduct(1);
        $batch = $this->startBatch($user, $product);

        $res = $this->runShot($batch, 0, $user)->assertOk()->json();
        $item = ShotBatchItem::query()->firstOrFail();

        $this->assertSame(40, (int) $user->fresh()->tokens);
        $this->assertSame(1, $item->qc_retries);
        $this->assertTrue($item->qc['passed']);
        $this->assertEqualsWithDelta(0.04, $item->cost_usd, 0.0001);
        $this->assertSame(5, $res['item']['qc']['score']);
        $this->assertCount(1, Storage::disk('public')->files('generated'));
    }

    public function test_daily_cost_cap_blocks_without_charge(): void
    {
        $this->enableShots('public', ['qc_enabled' => false, 'daily_cost_cap_usd' => 0.05]);
        $this->mockRouter([], 0.06);
        $this->mockVision();
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(2);
        $batch = $this->startBatch($user, $product);

        $this->runShot($batch, 0, $user)->assertOk();
        $res = $this->runShot($batch, 1, $user)->assertStatus(422)->json();

        $this->assertSame('DAILY_COST_CAP', $res['error_code']);
        $this->assertSame(90, (int) $user->fresh()->tokens);
    }

    public function test_red_preflight_blocks_batch_and_costs_nothing(): void
    {
        $this->enableShots('public');
        $this->mockVision([], ['usable' => false, 'issues' => ['blur'], 'crop_box' => null, 'product_description' => '', 'suggestion_fa' => 'عکس تار است']);
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(2);

        $pre = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('blurry.jpg', 900, 900),
        ], ['Accept' => 'application/json'])->assertOk()->json();
        $this->assertSame('red', $pre['verdict']);

        $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), [
            'uploads' => [['id' => $pre['upload_id']]],
            'shots' => ['beauty-hero-sun-travertine'],
        ])->assertStatus(422);
        $this->assertSame(100, (int) $user->fresh()->tokens);
    }

    public function test_preflight_without_vision_still_works_locally(): void
    {
        $this->enableShots('public', ['preflight_enabled' => false]);
        $user = $this->makeUser();
        $product = $this->makeShotProduct(1);

        $pre = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('small.jpg', 300, 300),
        ], ['Accept' => 'application/json'])->assertOk()->json();

        $this->assertSame('yellow', $pre['verdict']);
        $this->assertSame('local', $pre['checked_by']);
    }

    public function test_other_users_cannot_see_or_run_a_batch(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->mockRouter();
        $this->mockVision();
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $batch = $this->startBatch($owner, $this->makeShotProduct(1));

        $this->actingAs($other)->getJson(route('app.product-shots.batches.show', $batch['uuid']))->assertNotFound();
        $this->runShot($batch, 0, $other)->assertNotFound();
        $this->assertSame(100, (int) $owner->fresh()->tokens);
    }

    public function test_max_shots_per_run_is_enforced(): void
    {
        $this->enableShots('public', ['max_shots_per_run' => 2]);
        $this->mockVision();
        $user = $this->makeUser();
        $product = $this->makeShotProduct(3);

        $pre = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('a.jpg', 900, 900),
        ], ['Accept' => 'application/json'])->json();

        $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), [
            'uploads' => [['id' => $pre['upload_id']]],
            'shots' => $product->enabledProductShots()->map(fn ($ps) => $ps->shot->key)->all(),
        ])->assertStatus(422);
    }

    public function test_listing_scope_hides_shot_products_until_available(): void
    {
        $portrait = $this->makePortraitProduct();
        $shot = $this->makeShotProduct(1);

        $ids = Product::query()->hideUnavailableProductModes()->pluck('id')->all();
        $this->assertContains($portrait->id, $ids);
        $this->assertNotContains($shot->id, $ids);

        $this->enableShots('public');
        $ids = Product::query()->hideUnavailableProductModes()->pluck('id')->all();
        $this->assertContains($shot->id, $ids);
    }

    public function test_existing_products_default_to_portrait_mode(): void
    {
        $portrait = $this->makePortraitProduct();

        $this->assertSame('portrait', $portrait->fresh()->product_mode);
        $this->assertFalse($portrait->fresh()->isShotProduct());
    }

    public function test_pack_page_renders_for_available_users(): void
    {
        $this->enableShots('public');
        $user = $this->makeUser(55);
        $product = $this->makeShotProduct(3);

        $html = $this->actingAs($user)->get(route('app.create', ['product' => $product->route_slug]))
            ->assertOk()
            ->assertSee('data-product-pack', false)
            ->assertSee('product-pack-config', false)
            ->getContent();

        preg_match('#<script type="application/json" id="product-pack-config">(.*?)</script>#s', $html, $m);
        $config = json_decode($m[1] ?? '{}', true);
        $this->assertCount(3, $config['shots']);
        $this->assertSame(55, $config['balance']);
        $this->assertSame(['4:5', '1:1', '9:16'], $config['aspect_ratios']);

        // صفحه‌ی جزئیات محصول به صفحه‌ی ساخت پک می‌رود
        $this->actingAs($user)->get(route('app.product', $product->route_slug))
            ->assertRedirect(route('app.create', ['product' => $product->route_slug]));
    }

    public function test_same_upload_can_build_a_second_pack_after_cleanup(): void
    {
        $this->enableShots('public', ['qc_enabled' => false]);
        $this->mockRouter();
        $this->mockVision();
        $user = $this->makeUser(100);
        $product = $this->makeShotProduct(1);

        $pre = $this->actingAs($user)->post(route('app.product-shots.preflight', $product->slug), [
            'image' => UploadedFile::fake()->image('serum.jpg', 900, 1100),
        ], ['Accept' => 'application/json'])->json();
        $payload = ['uploads' => [['id' => $pre['upload_id']]], 'shots' => ['beauty-hero-sun-travertine']];

        $first = $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), $payload)->json('batch');
        $this->runShot($first, 0, $user)->assertOk();
        $second = $this->actingAs($user)->postJson(route('app.product-shots.batches.store', $product->slug), $payload)->json('batch');
        $this->runShot($second, 0, $user)->assertOk();

        $this->assertSame(80, (int) $user->fresh()->tokens);
    }

    public function test_prune_command_removes_old_staged_uploads(): void
    {
        Storage::disk('public')->put('uploads/product-shots/2026/01/01/old.jpg', 'x');
        touch(Storage::disk('public')->path('uploads/product-shots/2026/01/01/old.jpg'), now()->subDays(3)->getTimestamp());
        Storage::disk('public')->put('uploads/product-shots/2026/01/01/new.jpg', 'x');

        $this->artisan('product-shots:prune')->assertSuccessful();

        Storage::disk('public')->assertMissing('uploads/product-shots/2026/01/01/old.jpg');
        Storage::disk('public')->assertExists('uploads/product-shots/2026/01/01/new.jpg');
    }

    public function test_legacy_portrait_build_page_is_identical_with_flag_on_and_off(): void
    {
        $portrait = $this->makePortraitProduct();
        $user = $this->makeUser();

        $off = $this->actingAs($user)->get(route('app.create', ['product' => $portrait->route_slug]));
        $off->assertOk();
        $this->enableShots('public');
        $on = $this->actingAs($user)->get(route('app.create', ['product' => $portrait->route_slug]));
        $on->assertOk();

        $normalize = fn (string $html) => preg_replace('/(csrf-token" content="|name="_token" value=")[^"]+/', '$1X', $html);
        $this->assertSame($normalize($off->getContent()), $normalize($on->getContent()));
        $this->assertStringNotContainsString('data-product-pack', $on->getContent());
    }

    public function test_catalog_shows_pack_cards_and_business_tab_only_when_available(): void
    {
        $this->makePortraitProduct(['name_fa' => 'پرتره‌ی قدیمی']);
        $pack = $this->makeShotProduct(2, ['name_fa' => 'پک سرم تست']);

        $off = $this->get(route('products.index'))->assertOk();
        $off->assertSee('پرتره‌ی قدیمی')->assertDontSee('پک سرم تست')->assertDontSee('pack-line-tabs', false);

        $this->enableShots('public');
        $on = $this->get(route('products.index'))->assertOk();
        $on->assertSee('pack-line-tabs', false)->assertSee('پک سرم تست')->assertSee('pack-card', false);

        $business = $this->get(route('products.index', ['line' => 'business']))->assertOk();
        $business->assertSee('پک سرم تست')->assertDontSee('پرتره‌ی قدیمی');
    }
}
