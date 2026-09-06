<?php

namespace App\Services\HomeBuilder;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * لایه‌ی واکشی و آماده‌سازی داده هر Section برای رندر در فرانت.
 * تمام Query های محصول/دسته‌بندی اینجا متمرکز شده‌اند تا partialهای رندر (resources/views/app/home-builder/sections/*)
 * فقط روی داده‌ی آماده کار کنند و هیچ Query مستقیمی در Blade نوشته نشود (جلوگیری از N+1 و پراکندگی منطق).
 */
class HomeSectionRenderService
{
    public function __construct(protected HomeSectionLinkService $linkService)
    {
    }

    /**
     * @return array{section: HomeSection, products?: Collection, categories?: Collection}
     */
    public function prepare(HomeSection $section): array
    {
        return match ($section->type) {
            'product_slider', 'product_grid', 'collection' => [
                'section' => $section,
                'products' => $this->productsForDisplay($section),
                'viewAllUrl' => $this->linkService->viewAllUrl($section),
            ],
            'category_slider' => $this->prepareCategorySlider($section),
            default => [
                'section' => $section,
            ],
        };
    }

    /**
     * برای Layout «tabs» علاوه‌بر دسته‌بندی‌ها، محصولات هر دسته هم لازم است.
     * تمام محصولات لازم برای همه‌ی تب‌ها با یک Query واحد واکشی و در PHP گروه‌بندی می‌شوند
     * (بدون N+1 — طبق استاندارد پروژه).
     */
    protected function prepareCategorySlider(HomeSection $section): array
    {
        $categories = $this->resolveCategories($section);

        $data = [
            'section' => $section,
            'categories' => $categories,
            'viewAllUrl' => $this->linkService->viewAllUrl($section),
        ];

        if ($section->layout === 'tabs' && $categories->isNotEmpty()) {
            $productsByCategory = $this->resolveTabsProductsByCategory($section, $categories);
            if ($this->shouldFillEmptySpaces($section)) {
                $perTab = max(1, min(20, (int) $section->setting('products_per_tab', 8)));
                $productsByCategory = $productsByCategory->map(
                    fn (Collection $products) => $this->repeatProductsToTarget($products, $perTab)
                );
            }
            $data['productsByCategory'] = $productsByCategory;
            $allTabProducts = $productsByCategory
                ->flatten(1)
                ->unique('id')
                ->values();
            $data['allTabProducts'] = $this->shouldFillEmptySpaces($section)
                ? $this->repeatProductsToTarget(
                    $allTabProducts,
                    max(1, min(20, (int) $section->setting('products_per_tab', 8)))
                )
                : $allTabProducts;
        }

        return $data;
    }

    /**
     * آماده‌سازی همه‌ی Sectionهای یک صفحه در یک عبور (بدون Query تکراری برای هر ردیف جداگانه فراخوانی خارج از این متد).
     *
     * @param  Collection<int, HomeSection>  $sections
     * @return Collection<int, array>
     */
    public function prepareMany(Collection $sections): Collection
    {
        return $sections->map(fn (HomeSection $section) => $this->prepare($section));
    }

    protected function resolveProducts(HomeSection $section): Collection
    {
        $query = Product::query()->where('status', 'active');

        $source = (string) $section->setting('source', 'latest');
        $limit = (int) $section->setting('limit', 8);
        $limit = $limit > 0 ? min($limit, 24) : 8;

        if ($source === 'manual') {
            return $this->resolveManualProducts($section, $limit);
        }

        switch ($source) {
            case 'trending':
                $query->where('is_trending', true);
                break;

            case 'featured':
                $query->where('is_featured', true);
                break;

            case 'video':
                $query->whereIn('media_type', ['video', 'both']);
                break;

            case 'category':
                $categoryId = $section->setting('category_id');
                $categoryValue = $section->setting('category_value');
                $subcategoryValue = $section->setting('subcategory_value');

                if (! empty($categoryId)) {
                    $categoryIds = $this->categoryAndDescendantIds((int) $categoryId);
                    $query->where(function ($builder) use ($categoryIds) {
                        $builder->whereIn('category_id', $categoryIds)
                            ->orWhereHas('categories', function ($q) use ($categoryIds) {
                                $q->whereIn('categories.id', $categoryIds);
                            });
                    });
                }
                if (! empty($categoryValue)) {
                    $query->where('category', $categoryValue);
                }
                if (! empty($subcategoryValue)) {
                    $query->where('subcategory', $subcategoryValue);
                }
                break;

            case 'latest':
            default:
                break;
        }

        switch ((string) $section->setting('sort', 'latest')) {
            case 'popular':
                $query->withCount('generatedImages')->orderByDesc('generated_images_count');
                break;
            case 'expensive':
                $query->orderByDesc('credit_cost');
                break;
            case 'cheap':
                $query->orderBy('credit_cost');
                break;
            default:
                $query->latest();
        }

        return $query->limit($limit)->get();
    }

    /**
     * در حالت پرکننده، محصول تازه یا انتخاب اصلی همیشه در ابتدای Collection می‌ماند
     * و فقط کمبود انتهای مدل نمایشی با چرخش همان محصولات جبران می‌شود.
     */
    protected function productsForDisplay(HomeSection $section): Collection
    {
        $products = $this->resolveProducts($section)->values();
        if (! $this->shouldFillEmptySpaces($section) || $products->isEmpty()) {
            return $products;
        }

        return $this->repeatProductsToTarget($products, $this->fillTarget($section));
    }

    protected function shouldFillEmptySpaces(HomeSection $section): bool
    {
        return filter_var($section->setting('fill_empty_spaces', false), FILTER_VALIDATE_BOOLEAN);
    }

    protected function fillTarget(HomeSection $section): int
    {
        $limit = max(1, min(24, (int) $section->setting('limit', 8)));

        if ($section->type === 'product_grid') {
            return match ($section->layout) {
                'bento' => 6,
                'family_duo' => 2,
                'editorial' => $this->roundUpToMultiple($limit, 3, true),
                'hover_showcase', 'hover_library' => max(1, min(5, (int) $section->setting('hover_grid_cols', 4)))
                    * max(1, min(8, (int) $section->setting('hover_grid_rows', 4))),
                'two_col' => $this->roundUpToMultiple($limit, 2),
                'four_col' => $this->roundUpToMultiple($limit, 4),
                default => $this->roundUpToMultiple($limit, 6),
            };
        }

        if ($section->type === 'product_slider'
            && (string) $section->setting('display_mode', 'scroll') === 'grid'
            && in_array($section->layout, ['default', 'compact'], true)) {
            $cols = max(2, min(4, (int) $section->setting('grid_cols', 3)));
            $multiple = $cols === 3 ? 6 : $cols;

            return $this->roundUpToMultiple($limit, $multiple);
        }

        return $limit;
    }

    protected function roundUpToMultiple(int $value, int $multiple, bool $mustBeOdd = false): int
    {
        $rounded = (int) (ceil($value / $multiple) * $multiple);
        while ($mustBeOdd && $rounded % 2 === 0) {
            $rounded += $multiple;
        }

        return $rounded;
    }

    protected function repeatProductsToTarget(Collection $products, int $target): Collection
    {
        $source = $products->values();
        if ($source->isEmpty() || $source->count() >= $target) {
            return $source;
        }

        $filled = $source->all();
        $sourceCount = $source->count();
        for ($index = $sourceCount; $index < $target; $index++) {
            $filled[] = $source[$index % $sourceCount];
        }

        return collect($filled);
    }

    /**
     * منبع «انتخاب دستی» — فقط محصولاتی که ادمین جستجو/انتخاب کرده، با حفظ همان ترتیب انتخاب.
     * settings.product_ids آرایه‌ای از آبجکت‌های {id, name} است (برای بازنمایی چیپ‌ها در فرم ادمین).
     */
    protected function resolveManualProducts(HomeSection $section, int $limit): Collection
    {
        $picked = (array) $section->setting('product_ids', []);
        $ids = collect($picked)
            ->map(fn ($item) => (int) (is_array($item) ? ($item['id'] ?? 0) : $item))
            ->filter()
            ->unique()
            ->take($limit)
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return $ids
            ->map(fn (int $id) => $products->get($id))
            ->filter()
            ->values();
    }

    protected function resolveCategories(HomeSection $section): Collection
    {
        $limit = (int) $section->setting('limit', 10);
        $limit = $limit > 0 ? min($limit, 30) : 10;

        return Category::query()
            ->active()
            ->roots()
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * محصولات مربوط به هر دسته‌بندی برای Layout «tabs» — یک Query واحد روی همه‌ی دسته‌های
     * دریافت‌شده، سپس گروه‌بندی در PHP بر اساس رابطه‌ی categories() هر محصول.
     *
     * @param  Collection<int, Category>  $categories
     * @return Collection<int, Collection<int, Product>>
     */
    protected function resolveTabsProductsByCategory(HomeSection $section, Collection $categories): Collection
    {
        $perTab = (int) $section->setting('products_per_tab', 8);
        $perTab = $perTab > 0 ? min($perTab, 20) : 8;

        $allCategories = Category::query()->active()->get(['id', 'parent_id']);
        $categoryGroups = $categories->mapWithKeys(function (Category $category) use ($allCategories) {
            return [$category->id => $this->descendantIdsFromCollection($category->id, $allCategories)];
        });
        $categoryIds = $categoryGroups->flatten()->unique()->values()->all();

        // سقف ایمن روی کل Query (نه فقط هر تب) تا در کاتالوگ‌های بزرگ حجم واکشی نامحدود نشود.
        $safetyLimit = min(500, max(50, $perTab * count($categoryIds) * 3));

        $products = Product::query()
            ->where('status', 'active')
            ->whereHas('categories', function ($q) use ($categoryIds) {
                $q->whereIn('categories.id', $categoryIds);
            })
            ->with(['categories:id'])
            ->latest()
            ->limit($safetyLimit)
            ->get();

        $byCategory = collect();
        foreach ($categories as $category) {
            $groupIds = $categoryGroups->get($category->id, collect([$category->id]));
            $byCategory[$category->id] = $products
                ->filter(fn (Product $product) => $groupIds->contains((int) $product->category_id)
                    || $product->categories->contains(fn (Category $related) => $groupIds->contains((int) $related->id)))
                ->take($perTab)
                ->values();
        }

        return $byCategory;
    }

    /** شناسه خود دسته و تمام زیرشاخه‌های آن، برای نمایش صحیح دسته‌های درختی در Home. */
    protected function categoryAndDescendantIds(int $categoryId): array
    {
        $categories = Category::query()->active()->get(['id', 'parent_id']);

        return $this->descendantIdsFromCollection($categoryId, $categories)->all();
    }

    protected function descendantIdsFromCollection(int $categoryId, Collection $categories): Collection
    {
        $ids = collect([$categoryId]);
        $frontier = collect([$categoryId]);

        while ($frontier->isNotEmpty()) {
            $children = $categories->whereIn('parent_id', $frontier)->pluck('id')->diff($ids)->values();
            if ($children->isEmpty()) break;
            $ids = $ids->concat($children)->unique()->values();
            $frontier = $children;
        }

        return $ids;
    }
}
