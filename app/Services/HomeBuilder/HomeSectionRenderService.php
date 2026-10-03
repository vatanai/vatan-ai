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
    /** Scoped to prepareMany only; never retained between page renders. */
    private ?Collection $manualProducts = null;

    /** شناسه محصولاتی که سکشن‌های ویترین قبلی همین صفحه نمایش داده‌اند (فقط داخل prepareMany). */
    private ?Collection $seenProductIds = null;

    /** انواع ویترین که فهرست محصول دارند. */
    private const VITRINE_PRODUCT_TYPES = ['vt_hero', 'vt_row', 'vt_video_row', 'vt_masonry'];

    /** انواعی که منبع «انتخاب دستی» دارند و محصولاتشان یک‌جا واکشی می‌شود. */
    private const MANUAL_SOURCE_TYPES = ['product_slider', 'product_grid', 'collection', 'vt_hero', 'vt_row', 'vt_video_row', 'vt_masonry', 'vt_before_after'];

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
            'vt_hero', 'vt_row', 'vt_video_row', 'vt_masonry' => $this->prepareVitrineProducts($section),
            'vt_before_after' => $this->prepareBeforeAfter($section),
            'vt_tools', 'vt_occasions' => $this->prepareCategoryTiles($section),
            'vt_tabs' => $this->prepareVitrineTabs($section),
            'vt_cta_banner' => [
                'section' => $section,
                'products' => $this->resolveManualProducts($section, 3),
            ],
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
        $previous = $this->manualProducts;
        $previousSeen = $this->seenProductIds;
        $ids = $sections
            ->filter(fn (HomeSection $section) => $section->type === 'vt_cta_banner'
                || (in_array($section->type, self::MANUAL_SOURCE_TYPES, true)
                    && $section->setting('source', 'latest') === 'manual'))
            ->flatMap(function (HomeSection $section) {
                $limit = (int) $section->setting('limit', 8);

                return $this->manualProductIds($section, $limit > 0 ? min($limit, 48) : 8);
            })->unique()->values();

        try {
            $this->seenProductIds = collect();
            $this->manualProducts = $ids->isEmpty() ? collect() : Product::query()
                ->where('status', 'active')->hideUnavailableProductModes()->whereIn('id', $ids)->get()->keyBy('id');

            return $sections->map(fn (HomeSection $section) => $this->prepare($section));
        } finally {
            $this->manualProducts = $previous;
            $this->seenProductIds = $previousSeen;
        }
    }

    protected function resolveProducts(HomeSection $section, ?int $limitOverride = null): Collection
    {
        $query = Product::query()->where('status', 'active')->hideUnavailableProductModes();
        $isVideoLayout = $section->type === 'vt_video_row' || ($section->type === 'product_slider'
            && in_array($section->layout, ['video_loop', 'video_spotlight'], true));

        // سکشن‌های ویدیویی نباید با تغییر منبع در پنل، محصول عکس نمایش دهند.
        // این محدودیت در خود Query اعمال می‌شود تا هم برای منبع خودکار و هم دستی
        // از ورود داده‌ی نامرتبط به رندر جلوگیری شود.
        if ($isVideoLayout) {
            $query->whereIn('media_type', ['video', 'both']);
        }

        $source = (string) $section->setting('source', 'latest');
        $limit = (int) $section->setting('limit', 8);
        $maxLimit = str_starts_with($section->type, 'vt_') ? 48 : 24;
        $limit = $limitOverride ?? ($limit > 0 ? min($limit, $maxLimit) : 8);

        if ($source === 'manual') {
            $products = $this->resolveManualProducts($section, $limit);

            return $isVideoLayout
                ? $products->filter(fn (Product $product) => in_array($product->media_type, ['video', 'both'], true))->values()
                : $products;
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
            case 'random':
                $query->inRandomOrder();
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
        $ids = $this->manualProductIds($section, $limit);
        if ($ids->isEmpty()) {
            return collect();
        }

        $products = $this->manualProducts ?? collect();
        $missing = $ids->reject(fn (int $id) => $products->has($id))->values();
        if ($missing->isNotEmpty()) {
            $products = $products->union(Product::query()
                ->where('status', 'active')->hideUnavailableProductModes()->whereIn('id', $missing)->get()->keyBy('id'));
        }

        return $ids->map(fn (int $id) => $products->get($id))->filter()->values();
    }

    private function manualProductIds(HomeSection $section, int $limit): Collection
    {
        $picked = (array) $section->setting('product_ids', []);

        return collect($picked)
            ->map(fn ($item) => (int) (is_array($item) ? ($item['id'] ?? 0) : $item))
            ->filter()
            ->unique()
            ->take($limit)
            ->values();
    }

    // ══════════ ویترین ══════════

    protected function prepareVitrineProducts(HomeSection $section): array
    {
        $limit = max(1, min(48, (int) $section->setting('limit', 8)));
        $avoid = in_array($section->type, ['vt_row', 'vt_masonry'], true)
            && filter_var($section->setting('avoid_duplicates', true), FILTER_VALIDATE_BOOLEAN);

        $products = $avoid
            ? $this->withoutSeen($this->resolveProducts($section, min(48, $limit + $this->seenCount())))->take($limit)->values()
            : $this->resolveProducts($section)->values();

        if ($section->type === 'vt_video_row'
            && $products->count() < max(1, (int) $section->setting('min_items', 3))) {
            $products = collect();
        }

        $this->rememberSeen($products);

        $data = [
            'section' => $section,
            'products' => $products,
            // هیرو دکمه‌ی خودش را دارد و موزاییک دکمه‌ی «مشاهده همه» را پایین شبکه نشان می‌دهد.
            'viewAllUrl' => in_array($section->type, ['vt_hero', 'vt_masonry'], true) ? null : $this->linkService->viewAllUrl($section),
        ];

        if ($section->type === 'vt_masonry') {
            $data['totalProducts'] = Product::query()->where('status', 'active')->hideUnavailableProductModes()->count();
        }

        return $data;
    }

    /** کارت‌های قبل/بعد فقط برای محصولاتی ساخته می‌شوند که واقعاً تصویر «قبل» دارند. */
    protected function prepareBeforeAfter(HomeSection $section): array
    {
        $limit = max(1, min(9, (int) $section->setting('limit', 3)));

        if ($section->setting('source', 'with_before') === 'manual') {
            $candidates = $this->resolveManualProducts($section, 24);
        } else {
            $candidates = Product::query()
                ->where('status', 'active')
                ->hideUnavailableProductModes()
                ->whereNotNull('before_images')
                ->where('before_images', '!=', '[]')
                ->where('before_images', '!=', '')
                ->latest()
                ->limit(30)
                ->get();
        }

        $items = $candidates
            ->map(function (Product $product) {
                $before = $this->publicImageUrl(collect((array) $product->before_images)->filter()->first());

                return $before ? ['product' => $product, 'before' => $before, 'after' => $product->displayImageUrl()] : null;
            })
            ->filter()
            ->take($limit)
            ->values();

        $this->rememberSeen($items->pluck('product'));

        return ['section' => $section, 'items' => $items];
    }

    /** کاشی‌های دسته‌بندی (نوار ابزارها و مناسبت‌ها) با تصویر آخرین محصول و تعداد قالب هر دسته. */
    protected function prepareCategoryTiles(HomeSection $section): array
    {
        [$categories, $productsByCategory] = $this->vitrineCategoryProducts($section, 1);
        $overrides = $this->parseOverrides((string) $section->setting('tile_overrides', ''));
        $min = $section->type === 'vt_occasions' ? max(1, (int) $section->setting('min_products', 2)) : 1;

        $tiles = $categories->map(function (Category $category) use ($productsByCategory, $overrides, $min) {
            $products = $productsByCategory->get($category->id, collect());
            if ($products->count() < $min) {
                return null;
            }
            $override = $overrides[$category->name_fa] ?? [];

            return [
                'title' => $override[0] ?? $category->name_fa,
                'badge' => $override[1] ?? null,
                'count' => $products->count(),
                'image' => $products->first()?->displayImageUrl() ?? $this->publicImageUrl($category->image),
                'url' => $category->url(),
            ];
        })->filter()->values();

        return ['section' => $section, 'tiles' => $tiles];
    }

    /** ردیف تب‌دار ویترین: هر تب یک دسته‌ی انتخابی؛ تب‌های کم‌محصول مخفی می‌شوند. */
    protected function prepareVitrineTabs(HomeSection $section): array
    {
        $perTab = max(2, min(20, (int) $section->setting('products_per_tab', 10)));
        $min = max(1, (int) $section->setting('min_products_per_tab', 3));
        $avoid = filter_var($section->setting('avoid_duplicates', false), FILTER_VALIDATE_BOOLEAN);
        [$categories, $productsByCategory] = $this->vitrineCategoryProducts($section, $perTab + ($avoid ? $this->seenCount() : 0));
        $overrides = $this->parseOverrides((string) $section->setting('tab_overrides', ''));

        $tabs = $categories->map(function (Category $category) use ($productsByCategory, $perTab, $min, $avoid, $overrides) {
            $products = $productsByCategory->get($category->id, collect());
            if ($avoid) {
                $products = $this->withoutSeen($products);
            }
            $products = $products->take($perTab)->values();
            if ($products->count() < $min) {
                return null;
            }

            return [
                'key' => 'c' . $category->id,
                'label' => $overrides[$category->name_fa][0] ?? $category->name_fa,
                'products' => $products,
                'url' => $category->url(),
            ];
        })->filter()->values();

        $all = $tabs->flatMap(fn (array $tab) => $tab['products'])->unique('id')->take($perTab)->values();
        $this->rememberSeen($tabs->flatMap(fn (array $tab) => $tab['products']));

        return [
            'section' => $section,
            'tabs' => $tabs,
            'allProducts' => $all,
            'showAllTab' => filter_var($section->setting('show_all_tab', true), FILTER_VALIDATE_BOOLEAN) && $tabs->count() > 1,
            'viewAllUrl' => $this->linkService->viewAllUrl($section),
        ];
    }

    /**
     * دسته‌های انتخابی (به ترتیب انتخاب) + محصولات هر دسته (شامل زیرشاخه‌ها) با یک Query واحد.
     *
     * @return array{0: Collection<int, Category>, 1: Collection<int, Collection<int, Product>>}
     */
    protected function vitrineCategoryProducts(HomeSection $section, int $perCategory): array
    {
        $ids = collect((array) $section->setting('category_ids', []))
            ->map(fn ($id) => (int) (is_array($id) ? ($id['id'] ?? 0) : $id))
            ->filter()->unique()->take(12)->values();
        if ($ids->isEmpty()) {
            return [collect(), collect()];
        }

        $allCategories = Category::query()->active()->get(['id', 'parent_id']);
        $categories = Category::query()->active()->whereIn('id', $ids)->get()->keyBy('id');
        $categories = $ids->map(fn (int $id) => $categories->get($id))->filter()->values();
        $groups = $categories->mapWithKeys(fn (Category $category) => [
            $category->id => $this->descendantIdsFromCollection($category->id, $allCategories),
        ]);
        $allIds = $groups->flatten()->unique()->values()->all();
        if ($allIds === []) {
            return [$categories, collect()];
        }

        $products = Product::query()
            ->where('status', 'active')
            ->hideUnavailableProductModes()
            ->where(fn ($q) => $q->whereIn('category_id', $allIds)
                ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $allIds)))
            ->with(['categories:id'])
            ->latest()
            ->limit(800)
            ->get();

        $byCategory = collect();
        foreach ($categories as $category) {
            $groupIds = $groups->get($category->id);
            $byCategory[$category->id] = $products->filter(
                fn (Product $product) => $groupIds->contains((int) $product->category_id)
                    || $product->categories->contains(fn (Category $related) => $groupIds->contains((int) $related->id))
            )->values();
        }

        return [$categories, $byCategory];
    }

    /** هر خط: «نام دسته | مقدار ۱ | مقدار ۲» → [نام دسته => [مقدار ۱, مقدار ۲]] */
    protected function parseOverrides(string $text): array
    {
        $map = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 2 || $parts[0] === '') {
                continue;
            }
            $map[$parts[0]] = array_values(array_map(fn ($v) => $v === '' ? null : $v, array_slice($parts, 1)));
        }

        return $map;
    }

    protected function publicImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        if (str_starts_with($path, '/')) {
            return url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }

    protected function seenCount(): int
    {
        return $this->seenProductIds?->count() ?? 0;
    }

    protected function withoutSeen(Collection $products): Collection
    {
        if (! $this->seenProductIds || $this->seenProductIds->isEmpty()) {
            return $products->values();
        }

        return $products->reject(fn (Product $product) => $this->seenProductIds->contains($product->id))->values();
    }

    protected function rememberSeen(Collection $products): void
    {
        if ($this->seenProductIds === null) {
            return;
        }
        $this->seenProductIds = $this->seenProductIds
            ->concat($products->filter()->map(fn (Product $product) => $product->id))
            ->unique()
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
            ->hideUnavailableProductModes()
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
