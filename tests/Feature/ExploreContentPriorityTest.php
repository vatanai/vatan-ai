<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductShotSetting;
use App\Services\Explore\ExploreFeedService;
use App\Services\Explore\TrendsService;
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
        ProductShotSetting::current()->update(['enabled' => true, 'audience' => 'public']);
        ProductShotSetting::forgetCache();
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
            ['size-1x1', 'size-wide', 'size-big', 'size-wide', 'size-1x1', 'size-wide', 'size-big', 'size-wide'],
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
