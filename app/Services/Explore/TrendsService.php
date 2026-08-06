<?php

namespace App\Services\Explore;

use App\Models\Category;
use App\Models\Generation;
use App\Models\Product;
use App\Models\TrendBanner;
use App\Models\TrendOccasion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrendsService
{
    public function buildPage(): array
    {
        return [
            'trendProducts' => $this->popularProducts(function (Builder $query) {
                $query->where('is_trending', true);
            }, 500)->map(fn (Product $product) => $this->card($product))->values(),
            'trendBanners' => TrendBanner::query()
                ->where('is_active', true)
                ->orderBy('row_number')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ];
    }

    protected function topProductForPeriod(int $days, array $excludeIds = []): ?Product
    {
        $since = now()->subDays($days);
        $rankedIds = Generation::query()
            ->select('product_id')
            ->selectRaw('COUNT(*) AS period_generations')
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->groupBy('product_id')
            ->orderByDesc('period_generations')
            ->when($excludeIds !== [], fn (Builder $query) => $query->whereNotIn('product_id', $excludeIds))
            ->pluck('product_id')
            ->all();

        if ($rankedIds !== []) {
            $products = $this->productQuery()->whereIn('id', $rankedIds)->get()->keyBy('id');
            foreach ($rankedIds as $productId) {
                if ($products->has($productId)) {
                    return $products->get($productId);
                }
            }
        }

        return $this->popularProducts(function (Builder $query) {
            $query->where('is_trending', true);
        }, 1, $excludeIds)->first()
            ?: $this->popularProducts(null, 1, $excludeIds)->first();
    }

    protected function categoryTabs(): Collection
    {
        return Category::query()
            ->active()
            ->whereHas('products', fn (Builder $query) => $query->where('status', 'active'))
            ->withCount(['products' => fn (Builder $query) => $query->where('status', 'active')])
            ->orderByDesc('products_count')
            ->orderBy('sort_order')
            ->limit(8)
            ->get()
            ->map(function (Category $category) {
                $products = $this->popularProducts(function (Builder $query) use ($category) {
                    $query->where(function (Builder $categoryQuery) use ($category) {
                        $categoryQuery->where('category_id', $category->id)
                            ->orWhereHas('categories', fn (Builder $relation) => $relation->whereKey($category->id));
                    })->where('is_trending', true);
                }, 4);

                if ($products->count() < 4) {
                    $products = $products
                        ->concat($this->popularProducts(function (Builder $query) use ($category) {
                            $query->where(function (Builder $categoryQuery) use ($category) {
                                $categoryQuery->where('category_id', $category->id)
                                    ->orWhereHas('categories', fn (Builder $relation) => $relation->whereKey($category->id));
                            });
                        }, 4, $products->pluck('id')->all()))
                        ->unique('id')
                        ->take(4)
                        ->values();
                }

                return [
                    'id' => $category->id,
                    'title' => $category->name_fa ?: $category->name,
                    'products' => $products->map(fn (Product $product) => $this->card($product))->values(),
                ];
            })
            ->filter(fn (array $tab) => $tab['products']->isNotEmpty())
            ->values();
    }

    protected function occasionTabs(): Collection
    {
        return TrendOccasion::query()
            ->active()
            ->with('category')
            ->orderByDesc('sort_order')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function (TrendOccasion $occasion) {
                $products = $this->popularProducts(function (Builder $query) use ($occasion) {
                    $this->applyOccasionFilter($query, $occasion);
                    $query->where('is_trending', true);
                }, 4);

                if ($products->count() < 4) {
                    $products = $products
                        ->concat($this->popularProducts(function (Builder $query) use ($occasion) {
                            $this->applyOccasionFilter($query, $occasion);
                        }, 4, $products->pluck('id')->all()))
                        ->unique('id')
                        ->take(4)
                        ->values();
                }

                return [
                    'id' => $occasion->id,
                    'title' => $occasion->title_fa,
                    'products' => $products->map(fn (Product $product) => $this->card($product))->values(),
                ];
            })
            ->filter(fn (array $tab) => $tab['products']->isNotEmpty())
            ->values();
    }

    protected function applyOccasionFilter(Builder $query, TrendOccasion $occasion): void
    {
        $query->where(function (Builder $occasionQuery) use ($occasion) {
            if ($occasion->category_id) {
                $occasionQuery->where('category_id', $occasion->category_id)
                    ->orWhereHas('categories', fn (Builder $relation) => $relation->whereKey($occasion->category_id));
            }

            $term = trim((string) ($occasion->query ?: $occasion->title_fa));
            if ($term !== '') {
                $like = '%' . $term . '%';
                $occasionQuery->orWhere('name_fa', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('description_fa', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('subcategory', 'like', $like)
                    ->orWhere('tags', 'like', $like)
                    ->orWhereHas('categories', function (Builder $categories) use ($like) {
                        $categories->where('name_fa', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('name_en', 'like', $like);
                    });
            }
        });
    }

    protected function popularProducts(?callable $filter, int $limit, array $excludeIds = []): Collection
    {
        $query = $this->productQuery()
            ->when($excludeIds !== [], fn (Builder $builder) => $builder->whereNotIn('id', $excludeIds));

        if ($filter) {
            $filter($query);
        }

        return $query
            ->orderByDesc('downloads_count')
            ->orderByDesc('generations_count')
            ->orderByDesc('is_featured')
            ->orderByDesc('is_trending')
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function productQuery(): Builder
    {
        return Product::query()
            ->where('status', 'active')
            ->withCount(['downloads', 'generations']);
    }

    protected function card(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        $isVideo = in_array($product->media_type, ['video', 'both'], true)
            && filled($product->preview_video_url);

        return [
            'id' => $product->id,
            'name' => $product->name_fa ?: $product->name_en,
            'tag' => $product->subcategory ?: $product->category ?: 'وطن AI',
            'src' => $isVideo ? $this->videoUrl($product->preview_video_url) : $product->displayImageUrl(),
            'poster' => $product->displayImageUrl(),
            'video' => $isVideo,
            'link' => route('app.product', $product->route_slug) . '?source=trends',
            'downloads' => (int) ($product->downloads_count ?? 0),
        ];
    }

    protected function videoUrl(?string $path): string
    {
        $path = trim((string) $path);
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return asset(str_starts_with($path, 'storage/') ? $path : 'storage/' . ltrim($path, '/'));
    }
}
