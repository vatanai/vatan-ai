<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\ArticleCategory;
use App\Models\Category;
use App\Models\Product;
use App\Models\SitePage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SitemapService
{
    public function entries(): Collection
    {
        $urls = collect([
            ['loc' => route('site.home.root'), 'section' => 'core', 'page_key' => 'landing', 'title' => 'صفحه اصلی وطن', 'description' => 'آشنایی با وطن و شروع ساخت محتوای خلاقانه با هوش مصنوعی.', 'icon' => 'fa-house'],
            ['loc' => route('pricing.index'), 'section' => 'core', 'title' => 'تعرفه‌ها', 'description' => 'مقایسه پلن‌ها و انتخاب مسیر مناسب برای استفاده از وطن.', 'icon' => 'fa-tags'],
            ['loc' => route('site.about'), 'section' => 'core', 'title' => 'درباره وطن', 'description' => 'آشنایی بیشتر با پلتفرم و مسیر توسعه وطن.', 'icon' => 'fa-circle-info'],
            ['loc' => route('privacy'), 'section' => 'core', 'title' => 'حریم خصوصی و قوانین', 'description' => 'اطلاعات مربوط به حریم خصوصی و استفاده از خدمات وطن.', 'icon' => 'fa-shield-halved'],
            ['loc' => route('articles.index'), 'section' => 'articles', 'page_key' => 'articles', 'title' => 'همه مقالات', 'description' => 'آموزش‌ها، ایده‌ها و تازه‌های هوش مصنوعی.', 'icon' => 'fa-newspaper'],
            ['loc' => route('products.index'), 'section' => 'discover', 'title' => 'همه محصولات', 'description' => 'مرور محصولات آماده برای ساخت عکس و ویدیو.', 'icon' => 'fa-grid-2'],
            ['loc' => route('app.explore'), 'section' => 'discover', 'page_key' => 'explore', 'title' => 'اکسپلور', 'description' => 'کشف ایده‌ها و محصولات محبوب جامعه وطن.', 'icon' => 'fa-compass'],
            ['loc' => route('app.trends'), 'section' => 'discover', 'page_key' => 'trends', 'title' => 'ترندها', 'description' => 'محبوب‌ترین و تازه‌ترین مسیرهای ساخت در وطن.', 'icon' => 'fa-arrow-trend-up'],
        ])->filter(fn (array $url) => $this->isSitemapPage($url['page_key'] ?? null));

        $articles = Article::indexable()->latest('updated_at')
            ->get(['slug', 'title', 'excerpt', 'canonical_url', 'featured_image', 'og_image', 'updated_at']);

        $articleCategories = ArticleCategory::query()
            ->where('is_active', true)
            ->where('is_indexable', true)
            ->whereHas('articles', fn ($articles) => $articles->indexable(), '>=', 3)
            ->latest('updated_at')
            ->get();

        $authors = ArticleAuthor::query()
            ->where('is_active', true)
            ->whereHas('articles', fn ($articles) => $articles->indexable())
            ->latest('updated_at')
            ->get();

        $productCategoryIds = collect();
        if (Schema::hasTable('category_product')) {
            $productCategoryIds = DB::table('category_product')
                ->join('products', 'products.id', '=', 'category_product.product_id')
                ->where('products.status', 'active')
                ->whereNull('products.deleted_at')
                ->distinct()
                ->pluck('category_product.category_id');
        }
        if (Schema::hasColumn('products', 'category_id')) {
            $productCategoryIds = $productCategoryIds->merge(
                Product::query()->where('status', 'active')->whereNotNull('category_id')->pluck('category_id')
            )->unique()->values();
        }

        $productCategories = Category::query()
            ->active()
            ->whereIn('id', $productCategoryIds)
            ->latest('updated_at')
            ->get();

        $products = Product::query()
            ->where('status', 'active')
            ->latest('updated_at')
            ->get(['slug', 'product_code', 'name_fa', 'description_fa', 'updated_at']);

        foreach ($articleCategories as $category) {
            $urls->push([
                'loc' => $category->publicUrl(),
                'section' => 'articles',
                'title' => $category->name,
                'description' => $category->description ?: 'مقالات مرتبط با این موضوع در مرکز محتوای وطن.',
                'icon' => 'fa-folder-open',
                'lastmod' => $category->updated_at,
            ]);
        }

        foreach ($authors as $author) {
            $urls->push([
                'loc' => $author->publicUrl(),
                'section' => 'articles',
                'title' => 'مقالات ' . $author->name,
                'description' => $author->bio ?: 'مقالات منتشرشده توسط ' . $author->name . '.',
                'icon' => 'fa-user-pen',
                'lastmod' => $author->updated_at,
            ]);
        }

        foreach ($articles as $article) {
            $loc = $article->canonicalUrl();
            if (! $this->isSitemapUrl($loc)) {
                continue;
            }

            $images = collect([$article->imageUrl($article->og_image ?: $article->featured_image)])
                ->filter(fn (?string $image) => $image && $this->isSitemapUrl($image))
                ->values()->all();

            $urls->push([
                'loc' => $loc,
                'section' => 'articles',
                'title' => $article->title,
                'description' => $article->excerpt,
                'icon' => 'fa-file-lines',
                'lastmod' => $article->updated_at,
                'images' => $images,
            ]);
        }

        foreach ($productCategories as $category) {
            $loc = $category->canonicalUrl();
            if ($this->isSitemapUrl($loc)) {
                $urls->push([
                    'loc' => $loc,
                    'section' => 'products',
                    'title' => $category->name_fa ?: $category->name,
                    'description' => $category->metaDescription(),
                    'icon' => 'fa-layer-group',
                    'lastmod' => $category->updated_at,
                ]);
            }
        }

        foreach ($products as $product) {
            $urls->push([
                'loc' => route('app.product', ['product' => $product->route_slug]),
                'section' => 'products',
                'title' => $product->name_fa,
                'description' => $product->description_fa ?: 'مشاهده جزئیات و شروع ساخت با این محصول وطن.',
                'icon' => 'fa-wand-magic-sparkles',
                'lastmod' => $product->updated_at,
            ]);
        }

        return $urls
            ->filter(fn (array $url) => $this->isSitemapUrl($url['loc']))
            ->unique('loc')
            ->values();
    }

    public function sections(): array
    {
        $entries = $this->entries();
        $definitions = [
            ['key' => 'core', 'title' => 'صفحات اصلی', 'description' => 'مسیرهای اصلی برای آشنایی و شروع استفاده از وطن.', 'icon' => 'fa-sparkles'],
            ['key' => 'discover', 'title' => 'کشف و ساخت', 'description' => 'محصولات و فضاهای الهام‌بخش برای پیدا کردن ایده بعدی.', 'icon' => 'fa-compass'],
            ['key' => 'articles', 'title' => 'مقالات و آموزش‌ها', 'description' => 'محتوای آموزشی و به‌روز برای استفاده بهتر از هوش مصنوعی.', 'icon' => 'fa-newspaper'],
            ['key' => 'products', 'title' => 'محصولات و دسته‌بندی‌ها', 'description' => 'فهرست محصولات فعال وطن و دسته‌بندی‌های قابل استفاده.', 'icon' => 'fa-cubes'],
        ];

        return collect($definitions)->map(function (array $definition) use ($entries): array {
            $links = $entries->where('section', $definition['key'])->values();

            return [
                ...$definition,
                'count' => $links->count(),
                'links' => $links,
            ];
        })->filter(fn (array $section) => $section['count'] > 0)->values()->all();
    }

    private function isSitemapUrl(?string $url): bool
    {
        if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $sitemapOrigin = parse_url(route('site.home.root'));
        $candidateOrigin = parse_url($url);

        return ($sitemapOrigin['scheme'] ?? null) === ($candidateOrigin['scheme'] ?? null)
            && ($sitemapOrigin['host'] ?? null) === ($candidateOrigin['host'] ?? null)
            && ($sitemapOrigin['port'] ?? null) === ($candidateOrigin['port'] ?? null);
    }

    private function isSitemapPage(?string $pageKey): bool
    {
        if (! $pageKey || ! Schema::hasTable('site_pages')) {
            return true;
        }

        $page = SitePage::query()->where('key', $pageKey)->first();

        return ! $page || ($page->isAvailable() && $page->is_indexable && ! $page->requires_auth && ! $page->maintenance_mode);
    }
}
