<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/** ویترین اپ هوم: انواع vt_*، حذف تکرار، چیدمان migration و پیش‌نمایش داشبورد. */
class HomeVitrineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function category(string $name, ?int $parent = null): Category
    {
        return Category::query()->create([
            'name' => $name, 'name_fa' => $name, 'name_en' => 'c' . Str::random(5), 'slug' => Str::slug(Str::random(8)),
            'parent_id' => $parent, 'is_active' => true, 'sort_order' => 0,
        ]);
    }

    private function product(string $slug, ?Category $category = null, array $extra = []): Product
    {
        $product = Product::query()->create(array_merge([
            'name_fa' => 'محصول ' . $slug, 'name_en' => $slug, 'slug' => $slug,
            'category' => 'TEST', 'status' => 'active', 'thumbnail' => 'https://cdn.test/' . $slug . '.webp',
            'primary_model' => 'test-model', 'prompt_template' => 'تست', 'category_id' => $category?->id,
        ], $extra));
        $flags = array_intersect_key($extra, array_flip(['is_trending', 'is_new', 'is_featured']));
        if ($flags) {
            $product->forceFill($flags)->save();
        }
        if ($category) {
            $product->categories()->attach($category->id);
        }

        return $product;
    }

    private function section(string $type, array $settings, string $layout = 'default', ?string $title = null, int $position = 1): HomeSection
    {
        return HomeSection::query()->create([
            'page_key' => 'app_home', 'type' => $type, 'layout' => $layout, 'title_fa' => $title,
            'settings' => $settings, 'status' => 'published', 'position' => $position,
            'responsive' => ['desktop' => true, 'tablet' => true, 'mobile' => true],
        ]);
    }

    private function clearHome(): void
    {
        DB::table('home_sections')->delete();
        // محصولات نمونه‌ی migrationهای داده‌ای از نتیجه‌ی تست کنار گذاشته می‌شوند.
        DB::table('products')->update(['status' => 'inactive']);
    }

    public function test_every_vitrine_template_compiles(): void
    {
        $compiler = app('blade.compiler');
        foreach (glob(resource_path('views/app/home-builder/sections/{vt_*,partials/vt-*}.blade.php'), GLOB_BRACE) as $file) {
            $compiled = $compiler->compileString(file_get_contents($file));
            token_get_all($compiled, TOKEN_PARSE);
            $this->assertNotEmpty($compiled, $file);
        }
        $this->assertFileExists(public_path('css/home-vitrine.css'));
        $this->assertFileExists(public_path('js/home-vitrine.js'));
        foreach (array_keys(config('home_builder.types')) as $type) {
            $this->assertContains($type, HomeSection::TYPES);
            $this->assertFileExists(resource_path("views/app/home-builder/sections/{$type}.blade.php"));
        }
    }

    public function test_home_renders_all_vitrine_sections_and_moves_hero_to_top(): void
    {
        $this->clearHome();
        $shoes = $this->category('کیف و کفش');
        $gold = $this->category('طلا');
        $birthday = $this->category('تولد');
        foreach (range(1, 4) as $i) {
            $this->product("shoe-$i", $shoes, ['is_trending' => $i === 1]);
            $this->product("gold-$i", $gold);
            $this->product("bday-$i", $birthday);
        }
        $this->product('only-one', $this->category('کم'));
        $before = $this->product('with-before', $shoes, ['before_images' => ['https://cdn.test/before.webp']]);
        foreach (range(1, 3) as $i) {
            $this->product("vid-$i", $shoes, ['media_type' => 'video', 'preview_video_url' => "https://cdn.test/v$i.mp4"]);
        }

        $this->section('vt_tools', ['category_ids' => [$shoes->id, $gold->id], 'tile_overrides' => 'طلا | طلا و جواهر | جدید'], 'default', 'ابزارها', 1);
        $this->section('vt_hero', ['placement' => 'top', 'source' => 'manual', 'product_ids' => [['id' => $before->id, 'name' => 'x']], 'heading' => 'تیتر هیرو ویترین'], 'default', null, 2);
        $this->section('vt_row', ['source' => 'trending', 'limit' => 8], 'default', 'ترندهای امروز', 3);
        $this->section('vt_tabs', ['category_ids' => [$shoes->id, $gold->id, $this->category('خالی')->id], 'min_products_per_tab' => 3], 'default', 'صنف', 4);
        $this->section('vt_before_after', ['source' => 'with_before', 'limit' => 3], 'default', 'قبل بعد', 5);
        $this->section('vt_video_row', ['source' => 'video', 'min_items' => 3], 'default', 'ویدیوها', 6);
        $this->section('vt_cta_banner', ['heading' => 'بنر دعوت تست', 'cta_label' => 'شروع', 'cta_link' => '/app/products', 'product_ids' => [['id' => $before->id]]], 'default', null, 7);
        $this->section('vt_row', ['source' => 'category', 'category_id' => $gold->id, 'limit' => 6, 'avoid_duplicates' => false], 'marquee', 'مارکی', 8);
        $this->section('vt_occasions', ['category_ids' => [$birthday->id], 'min_products' => 2], 'default', 'مناسبت‌ها', 9);
        $this->section('vt_masonry', ['source' => 'latest', 'limit' => 12, 'avoid_duplicates' => false], 'default', 'همه ایده‌ها', 10);

        $html = $this->get('/app/home')->assertOk()->getContent();

        $this->assertStringContainsString('تیتر هیرو ویترین', $html);
        $this->assertLessThan(strpos($html, 'id="home-search-form"'), strpos($html, 'تیتر هیرو ویترین'), 'هیرو باید قبل از جست‌وجو باشد');
        $this->assertStringNotContainsString('home-greeting-title', $html);
        $this->assertStringContainsString('طلا و جواهر', $html);
        $this->assertStringContainsString('class="vt-tabbar"', $html);
        $this->assertStringNotContainsString('>خالی <', $html);
        $this->assertStringContainsString('https://cdn.test/before.webp', $html);
        $this->assertStringContainsString('data-vt-video-src="https://cdn.test/v1.mp4"', $html);
        $this->assertStringContainsString('بنر دعوت تست', $html);
        $this->assertStringContainsString('vt-marquee-clone', $html);
        $this->assertStringContainsString('مناسبت‌ها', $html);
        $this->assertStringContainsString('مشاهده همه', $html);
        $this->assertStringContainsString('css/home-vitrine.css?v=', $html);
        // نوار ابزارها چیپ‌های کوچک زیر جست‌وجو را حذف می‌کند
        $this->assertStringNotContainsString('ig-left', $html);
    }

    public function test_video_row_hides_when_not_enough_videos_and_rows_skip_duplicates(): void
    {
        $this->clearHome();
        $cat = $this->category('عمومی');
        $a = $this->product('dup-a', $cat, ['is_trending' => true]);
        $b = $this->product('dup-b', $cat, ['is_trending' => true]);
        $this->product('dup-c', $cat);
        $this->product('vid-only', $cat, ['media_type' => 'video']);

        $this->section('vt_row', ['source' => 'trending', 'limit' => 8], 'default', 'ردیف اول', 1);
        $this->section('vt_row', ['source' => 'latest', 'limit' => 8, 'avoid_duplicates' => true], 'default', 'ردیف دوم', 2);
        $this->section('vt_video_row', ['source' => 'video', 'min_items' => 3], 'default', 'ویدیوهای کم', 3);

        $service = app(\App\Services\HomeBuilder\HomeSectionRenderService::class);
        $items = $service->prepareMany(HomeSection::query()->published()->ordered()->get());

        $first = $items[0]['products']->pluck('id');
        $second = $items[1]['products']->pluck('id');
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $first->all());
        $this->assertEmpty($first->intersect($second));
        $this->assertTrue($items[2]['products']->isEmpty());
        $this->assertStringNotContainsString('ویدیوهای کم', $this->get('/app/home')->getContent());
    }

    public function test_layout_migration_hides_old_sections_and_rolls_back(): void
    {
        $this->clearHome();
        $old = $this->section('product_slider', ['source' => 'latest'], 'peek', 'سکشن قدیمی', 5);
        $draft = HomeSection::query()->create(['page_key' => 'app_home', 'type' => 'text', 'status' => 'draft', 'position' => 9, 'settings' => []]);
        $spot = $this->section('product_slider', ['source' => 'video'], 'video_spotlight', 'قصه‌ها را به حرکت درآور', 2);
        $this->category('عکس محصول');
        $this->category('تولد');

        $migration = require database_path('migrations/home-builder/2026_10_03_000001_apply_vitrine_home_layout.php');
        $migration->up();
        $migration->up(); // idempotent

        $this->assertSame('hidden', $old->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('published', $spot->fresh()->status, 'با کمتر از ۴ ویدیو، سکشن ویدیوی فعلی می‌ماند');
        $published = HomeSection::query()->where('page_key', 'app_home')->published()->ordered()->get();
        $this->assertSame(
            ['vt_hero', 'vt_tools', 'vt_row', 'vt_tabs', 'vt_before_after', 'product_slider', 'vt_cta_banner', 'vt_row', 'vt_occasions', 'vt_tabs', 'vt_masonry'],
            $published->pluck('type')->all()
        );
        $this->assertSame(1, HomeSection::query()->where('type', 'vt_video_row')->where('status', 'draft')->count());
        $this->get('/app/home')->assertOk();

        $migration->down();
        $this->assertSame('published', $old->fresh()->status);
        $this->assertSame(5, $old->fresh()->position);
        $this->assertSame('قصه‌ها را به حرکت درآور', $spot->fresh()->title_fa);
        $this->assertSame(2, $spot->fresh()->position);
        $this->assertSame(0, HomeSection::query()->where('type', 'like', 'vt_%')->count());
        $this->assertArrayNotHasKey('_vitrine_prev', $old->fresh()->settings);
    }

    public function test_dashboard_previews_every_vitrine_type(): void
    {
        DB::table('products')->update(['status' => 'inactive']);
        $cat = $this->category('پیش‌نمایش');
        foreach (range(1, 4) as $i) {
            $this->product("pv-$i", $cat, ['before_images' => ['https://cdn.test/b.webp'], 'media_type' => $i < 3 ? 'video' : 'image', 'preview_video_url' => 'https://cdn.test/p.mp4']);
        }
        $admin = Admin::query()->create(['name' => 'مدیر', 'email' => 'vt@test.local', 'password' => 'password', 'role' => 'leader', 'is_active' => true]);
        $this->actingAs($admin, 'admin');

        foreach (config('home_builder.types') as $type => $info) {
            if (! str_starts_with($type, 'vt_')) {
                continue;
            }
            foreach (array_keys($info['layouts']) as $layout) {
                $html = $this->get(route('admin.home-builder.showcase.preview', ['type' => $type, 'layout' => $layout]))
                    ->assertOk()->getContent();
                $this->assertStringContainsString('vt-sec', $html, "{$type}:{$layout}");
            }
        }
    }

    public function test_admin_manual_picker_finds_drafts_but_public_home_still_hides_them(): void
    {
        $this->clearHome();
        $active = $this->product('searchable-active', null, ['name_fa' => 'محصول جستجوی تست', 'status' => 'active']);
        $draft = $this->product('searchable-draft', null, ['name_fa' => 'محصول جستجوی تست پیش‌نویس', 'status' => 'draft']);
        $inactive = $this->product('searchable-inactive', null, ['name_fa' => 'محصول جستجوی تست غیرفعال', 'status' => 'inactive']);
        $admin = Admin::query()->create(['name' => 'مدیر جستجو', 'email' => 'search@test.local', 'password' => 'password', 'role' => 'leader', 'is_active' => true]);

        $products = $this->actingAs($admin, 'admin')
            ->getJson(route('admin.home-builder.products.search', ['q' => 'جستجوی تست']))
            ->assertOk()
            ->json('products');

        $this->assertEqualsCanonicalizing([$active->id, $draft->id], collect($products)->pluck('id')->all());
        $this->assertSame('draft', collect($products)->firstWhere('id', $draft->id)['status']);
        $this->assertNotContains($inactive->id, collect($products)->pluck('id')->all());

        $section = $this->section('vt_row', [
            'source' => 'manual',
            'product_ids' => [
                ['id' => $active->id, 'name' => $active->name_fa],
                ['id' => $draft->id, 'name' => $draft->name_fa],
            ],
            'limit' => 8,
        ]);
        $prepared = app(\App\Services\HomeBuilder\HomeSectionRenderService::class)->prepare($section);
        $this->assertSame([$active->id], $prepared['products']->pluck('id')->all());
    }
}
