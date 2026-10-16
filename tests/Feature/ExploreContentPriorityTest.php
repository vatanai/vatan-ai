<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\FeedSetting;
use App\Models\FeedSurface;
use App\Models\Product;
use App\Models\ProductShotSetting;
use App\Services\Explore\ExploreFeedService;
use App\Services\Explore\TrendsService;
use App\Services\ProductShots\ProductShotFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExploreContentPriorityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('products')->update(['status' => 'inactive']);
        ProductShotFeature::resetSchemaCache();
        ProductShotSetting::current()->update(['enabled' => true, 'audience' => 'public']);
        $this->assertTrue(app(ProductShotFeature::class)->availableFor(null));
    }

    public function test_explore_uses_sixty_five_percent_product_priority_and_the_exact_tile_cycle(): void
    {
        $this->seedProducts(20, 20);

        $tiles = app(ExploreFeedService::class)->buildFeed('explore', null, [
            'new_product_ratio' => 0,
        ]);
        $firstTwenty = collect($tiles)->take(20);

        $this->assertSame(13, $firstTwenty->where('tag', 'پروداکتی')->count());
        $this->assertSame(7, $firstTwenty->where('tag', 'پرتره')->count());
        $this->assertSame(
            ['size-1x1', 'size-wide', 'size-tall', 'size-big', 'size-1x1', 'size-wide', 'size-tall', 'size-big'],
            $firstTwenty->take(8)->pluck('size')->all(),
        );
    }

    public function test_trends_uses_sixty_five_percent_product_priority(): void
    {
        $this->seedProducts(20, 20);

        $cards = app(TrendsService::class)->buildPage(20, 0, 1)['trendProducts'];

        $this->assertCount(20, $cards);
        $this->assertSame(13, $cards->where('product_mode', Product::MODE_PRODUCT)->count());
        $this->assertSame(7, $cards->where('product_mode', Product::MODE_PORTRAIT)->count());
    }

    public function test_desktop_and_tablet_excel_pattern_matches_the_six_by_six_reference_and_repeats(): void
    {
        $this->seedProducts(30, 30);

        $pattern = FeedSetting::EXCEL_RESPONSIVE_GRIDS['desktop'];
        $this->assertSame(6, $pattern['columns']);
        $this->assertSame(6, $pattern['rows']);
        $this->assertSame([
            ['a', 'b', 'b', 'c', 'd', 'e'],
            ['f', 'g', 'g', 'h', 'd', 'i'],
            ['f', 'g', 'g', 'j', 'j', 'k'],
            ['l', 'm', 'n', 'o', 'p', 'p'],
            ['q', 'm', 'r', 's', 't', 't'],
            ['u', 'u', 'v', 's', 't', 't'],
        ], $pattern['cells']);

        $this->assertSame(3, FeedSetting::EXCEL_RESPONSIVE_GRIDS['mobile']['columns']);
        $this->assertSame(10, FeedSetting::EXCEL_RESPONSIVE_GRIDS['mobile']['rows']);
    }

    public function test_explore_and_trends_ratio_controls_save_and_drive_their_feed_mix(): void
    {
        $this->seedProducts(20, 20);
        $admin = Admin::query()->forceCreate([
            'name' => 'مدیر آزمون',
            'email' => 'explore-ratio-' . uniqid() . '@example.test',
            'password' => bcrypt('test-password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->get(route('admin.explore.index'))
            ->assertOk()->assertSee('data-mode-ratio-slider', false);
        $this->actingAs($admin, 'admin')->post(route('admin.explore.settings.update'), [
            'layout_style' => 'excel_11',
            'randomness_level' => 35,
            'campaign_ratio' => 0,
            'product_ratio' => 30,
        ])->assertRedirect();

        $exploreSurface = FeedSurface::query()->where('key', 'explore')->firstOrFail();
        $this->assertSame(30, (int) $exploreSurface->activeSetting()->product_ratio);
        $exploreCards = collect(app(ExploreFeedService::class)->buildFeed('explore', null, ['new_product_ratio' => 0]))->take(20);
        $this->assertSame(6, $exploreCards->where('tag', 'پروداکتی')->count());
        $this->assertSame(14, $exploreCards->where('tag', 'پرتره')->count());

        foreach ([0, 100] as $ratio) {
            $this->actingAs($admin, 'admin')->post(route('admin.explore.settings.update'), [
                'layout_style' => 'excel_11',
                'randomness_level' => 35,
                'campaign_ratio' => 0,
                'product_ratio' => $ratio,
            ])->assertRedirect();
            $firstTwenty = collect(app(ExploreFeedService::class)->buildFeed('explore', null, ['new_product_ratio' => 0]))->take(20);
            $expectedProductCount = $ratio === 100 ? 20 : 0;
            $this->assertSame($expectedProductCount, $firstTwenty->where('tag', 'پروداکتی')->count());
            $this->assertSame(20 - $expectedProductCount, $firstTwenty->where('tag', 'پرتره')->count());
        }

        $this->actingAs($admin, 'admin')->get(route('admin.trends.index'))
            ->assertOk()->assertSee('data-mode-ratio-slider', false);
        $this->actingAs($admin, 'admin')->post(route('admin.trends.product-ratio.update'), [
            'product_ratio' => 70,
        ])->assertRedirect();

        $trendsSurface = FeedSurface::query()->where('key', 'trending')->firstOrFail();
        $this->assertSame(70, (int) $trendsSurface->activeSetting()->product_ratio);
        $trendCards = app(TrendsService::class)->buildPage(20, 0, 1)['trendProducts'];
        $this->assertSame(14, $trendCards->where('product_mode', Product::MODE_PRODUCT)->count());
        $this->assertSame(6, $trendCards->where('product_mode', Product::MODE_PORTRAIT)->count());

        foreach ([100, 0] as $ratio) {
            $this->actingAs($admin, 'admin')->post(route('admin.trends.product-ratio.update'), [
                'product_ratio' => $ratio,
            ])->assertRedirect();
            $firstTwenty = app(TrendsService::class)->buildPage(20, 0, 1)['trendProducts'];
            $expectedProductCount = $ratio === 100 ? 20 : 0;
            $this->assertSame($expectedProductCount, $firstTwenty->where('product_mode', Product::MODE_PRODUCT)->count());
            $this->assertSame(20 - $expectedProductCount, $firstTwenty->where('product_mode', Product::MODE_PORTRAIT)->count());
        }
    }

    private function seedProducts(int $productCount, int $portraitCount): void
    {
        foreach (range(1, $productCount) as $index) {
            $this->product("priority-product-$index", Product::MODE_PRODUCT, 'پروداکتی');
        }
        foreach (range(1, $portraitCount) as $index) {
            $this->product("priority-portrait-$index", Product::MODE_PORTRAIT, 'پرتره');
        }
    }

    private function product(string $slug, string $mode, string $category): Product
    {
        return Product::query()->create([
            'name_fa' => $slug,
            'name_en' => $slug,
            'slug' => $slug,
            'category' => $category,
            'status' => 'active',
            'thumbnail' => "https://cdn.test/$slug.webp",
            'primary_model' => 'test-model',
            'prompt_template' => 'test prompt',
            'product_mode' => $mode,
            'explore_tiles' => ['1x1'],
        ]);
    }
}
