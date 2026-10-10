<?php

namespace Vatan\Seo\Connectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;

/**
 * اتصال به همان پروژه‌ی لاراولی که موتور روی آن نصب است (مثل وطن ای‌آی).
 * نام مدل‌ها و فیلدها از config('seo-engine.host') خوانده می‌شود.
 */
class LaravelLocalConnector implements SiteConnector
{
    public function __construct(private Site $site) {}

    public function label(): string
    {
        return 'لاراول (همین پروژه)';
    }

    protected function cfg(string $key, mixed $default = null): mixed
    {
        return config('seo-engine.host.'.$key, $default);
    }

    public function health(): array
    {
        $p = $this->cfg('product_model');
        $a = $this->cfg('article_model');
        $ok = ($p && class_exists($p)) || ($a && class_exists($a));
        return [$ok, $ok ? 'مدل‌های محصول و مقاله در دسترس هستند.' : 'مدل محصول/مقاله در تنظیمات میزبان پیدا نشد.'];
    }

    public function products(int $limit = 200): array
    {
        $model = $this->cfg('product_model');
        if (! $model || ! class_exists($model)) {
            return [];
        }
        $f = (array) $this->cfg('product_fields', []);
        $query = $model::query();
        $table = (new $model)->getTable();
        $columns = DB::getSchemaBuilder()->getColumnListing($table);
        if (! empty($f['status_column']) && in_array($f['status_column'], $columns, true) && ! empty($f['active_values'])) {
            $query->whereIn($f['status_column'], (array) $f['active_values']);
        }

        return $query->latest('id')->limit($limit)->get()->map(function ($p) use ($f) {
            $keywords = $p->{$f['keywords'] ?? 'meta_keywords'} ?? [];
            if (is_string($keywords)) {
                $keywords = preg_split('/[,،\n]+/u', $keywords, -1, PREG_SPLIT_NO_EMPTY);
            }
            return [
                'id' => $p->getKey(),
                'title' => trim((string) ($p->{$f['title'] ?? 'name'} ?? '')),
                'description' => Str::limit(trim(strip_tags((string) ($p->{$f['description'] ?? 'description'} ?? ''))), 600),
                'category' => is_scalar($c = $p->{$f['category'] ?? 'category'} ?? null) ? (string) $c : null,
                'keywords' => array_values(array_filter(array_map('trim', (array) $keywords))),
                'url' => $this->productUrl($p, $f),
            ];
        })->filter(fn ($p) => $p['title'] !== '')->values()->all();
    }

    protected function productUrl($product, array $f): ?string
    {
        $route = $this->cfg('product_route');
        if ($route && Route::has($route)) {
            try {
                return route($route, $product);
            } catch (\Throwable) {
                // ادامه با الگو
            }
        }
        $slug = $product->{$f['slug'] ?? 'slug'} ?? null;
        return $slug ? $this->site->url(str_replace('{slug}', $slug, (string) $this->cfg('product_url'))) : null;
    }

    public function articles(int $limit = 300): array
    {
        $model = $this->cfg('article_model');
        if (! $model || ! class_exists($model)) {
            return [];
        }
        return $model::query()->where('status', 'published')->latest('published_at')->limit($limit)
            ->get(['id', 'title', 'slug', 'seo_keywords'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => (string) $a->title,
                'url' => $this->articleUrl((string) $a->slug),
                'keywords' => (array) ($a->seo_keywords ?? []),
            ])->all();
    }

    protected function articleUrl(string $slug): string
    {
        $route = $this->cfg('article_route');
        if ($route && Route::has($route)) {
            try {
                return route($route, ['slug' => $slug]);
            } catch (\Throwable) {
            }
        }
        return $this->site->url(str_replace('{slug}', $slug, (string) $this->cfg('article_url')));
    }

    public function publish(ContentItem $item): array
    {
        $model = $this->cfg('article_model');
        if (! $model || ! class_exists($model)) {
            throw new RuntimeException('مدل مقاله‌ی میزبان پیدا نشد.');
        }
        $categoryId = $this->site->setting('publish.category_id') ?: $this->firstId($this->cfg('article_category_model'));
        $authorId = $this->site->setting('publish.author_id') ?: $this->firstId($this->cfg('article_author_model'));
        if (! $categoryId || ! $authorId) {
            throw new RuntimeException('برای انتشار، حداقل یک دسته‌بندی و یک نویسنده‌ی فعال در بخش مقالات لازم است.');
        }

        $slug = $this->uniqueSlug($model, $item->slug ?: Str::slug((string) $item->title) ?: 'article-'.$item->id);
        $blocks = (array) $item->blocks;
        $excerpt = $item->meta_description ?: Str::limit(collect($blocks)->firstWhere('type', 'lead')['content'] ?? '', 280);
        $words = (int) $item->word_count;
        $status = $this->cfg('publish_status', 'published') === 'draft' ? 'draft' : 'published';

        $attributes = [
            'article_category_id' => $categoryId,
            'article_author_id' => $authorId,
            'title' => $item->title,
            'slug' => $slug,
            'excerpt' => $excerpt ?: (string) $item->title,
            'content_blocks' => $blocks,
            'content_type' => 'guide',
            'status' => $status,
            'meta_title' => $item->meta_title,
            'meta_description' => $item->meta_description,
            'seo_keywords' => array_values(array_filter([$item->keyword?->keyword, ...((array) data_get($item->brief, 'secondary_keywords', []))])),
            'seo_auto_fill' => false,
            'is_indexable' => true,
            'allow_comments' => true,
            'reading_minutes' => max(1, (int) ceil($words / 220)),
            'published_at' => $status === 'published' ? now() : null,
        ];
        $instance = new $model;
        $columns = DB::getSchemaBuilder()->getColumnListing($instance->getTable());
        $article = $model::create(array_intersect_key($attributes, array_flip($columns)));

        return [(string) $article->getKey(), $this->articleUrl($slug)];
    }

    protected function firstId(?string $model): ?int
    {
        if (! $model || ! class_exists($model)) {
            return null;
        }
        $q = $model::query();
        try {
            $q->where('is_active', true);
            return $q->value('id') ?: $model::query()->value('id');
        } catch (\Throwable) {
            return $model::query()->value('id');
        }
    }

    protected function uniqueSlug(string $model, string $base): string
    {
        $base = trim($base, '-') ?: 'article';
        $slug = $base;
        $i = 2;
        while ($model::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
}
