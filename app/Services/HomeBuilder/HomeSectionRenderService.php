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
    /**
     * @return array{section: HomeSection, products?: Collection, categories?: Collection}
     */
    public function prepare(HomeSection $section): array
    {
        return match ($section->type) {
            'product_slider', 'product_grid', 'collection' => [
                'section' => $section,
                'products' => $this->resolveProducts($section),
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
        ];

        if ($section->layout === 'tabs' && $categories->isNotEmpty()) {
            $data['productsByCategory'] = $this->resolveTabsProductsByCategory($section, $categories);
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
                    $query->whereHas('categories', function ($q) use ($categoryId) {
                        $q->where('categories.id', $categoryId);
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

        $categoryIds = $categories->pluck('id')->all();

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
        foreach ($categoryIds as $categoryId) {
            $byCategory[$categoryId] = $products
                ->filter(fn (Product $product) => $product->categories->contains('id', $categoryId))
                ->take($perTab)
                ->values();
        }

        return $byCategory;
    }
}
