<?php

namespace App\Services\Explore;

use App\Models\Category;
use App\Models\FeedCampaign;
use App\Models\FeedContentItem;
use App\Models\FeedContentScore;
use App\Models\FeedPinnedItem;
use App\Models\FeedSetting;
use App\Models\FeedSurface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * موتور فید — قلب سیستم اکسپلور هوشمند.
 * ترکیب محصولات + کمپین‌ها، رتبه‌بندی با Boost دستی + تصادفی‌بودن کنترل‌شده،
 * تزریق آیتم‌های سنجاق‌شده در موقعیت ثابت، و تخصیص اندازه‌ی کاشی طبق تنظیمات ادمین.
 *
 * طراحی طوری است که با یک ورودی $surfaceKey متفاوت (مثلاً 'home' یا 'trending')
 * دقیقاً همین موتور، بدون هیچ تغییری، قابل استفاده‌ی مجدد است.
 */
class ExploreFeedService
{
    /**
     * بستر را برمی‌گرداند، اگر وجود نداشت با عنوان پیش‌فرض می‌سازد (بدون نیاز به Seeder دستی).
     */
    public function getOrCreateSurface(string $key, string $titleFa = 'اکسپلور'): FeedSurface
    {
        return FeedSurface::firstOrCreate(
            ['key' => $key],
            ['title_fa' => $titleFa, 'is_active' => true]
        );
    }

    /**
     * نسخه‌ی فعال تنظیمات یک بستر را برمی‌گرداند، اگر وجود نداشت با سبک «کلاسیک» می‌سازد.
     */
    public function getOrCreateActiveSetting(FeedSurface $surface): FeedSetting
    {
        $setting = FeedSetting::where('feed_surface_id', $surface->id)
            ->where('is_active_version', true)
            ->latest('id')
            ->first();

        if ($setting) {
            return $setting;
        }

        return FeedSetting::create([
            'feed_surface_id'    => $surface->id,
            'layout_style'       => 'excel_11',
            'tile_weights'       => FeedSetting::LAYOUT_PRESETS['excel_11'],
            'randomness_level'   => 35,
            'campaign_ratio'     => 5,
            'include_filters'    => [],
            'exclude_filters'    => [],
            'is_active_version'  => true,
        ]);
    }

    public function activeLayoutStyle(string $surfaceKey = 'explore'): string
    {
        $surface = $this->getOrCreateSurface($surfaceKey);

        return FeedSetting::effectiveLayoutStyle($this->getOrCreateActiveSetting($surface)->layout_style);
    }

    /**
     * ساخت فید نهایی برای یک بستر — خروجی مستقیماً برای رندر گرید کاشی آماده است.
     *
     * @return array<int, array{video: bool, src: string, name: string, tag: string, size: string, link: string}>
     */
    public function buildFeed(string $surfaceKey = 'explore', ?int $limit = 48, array $filters = []): array
    {
        $surface = $this->getOrCreateSurface($surfaceKey);
        $setting = $this->getOrCreateActiveSetting($surface);
        $query = $this->normaliseSearchTerm((string) ($filters['query'] ?? ''));
        $isFiltered = $query !== '';
        $newProductRatio = max(0, min(100, (int) ($filters['new_product_ratio'] ?? 0)));

        // در جستجو Pin و کمپین نباید نتیجه‌ی نامرتبط وارد خروجی کنند.
        $pinLimit = $limit ?? PHP_INT_MAX;
        $pinnedSlots = $isFiltered ? [] : $this->resolvePinnedSlots($surface, $setting, $pinLimit);
        $usedProductIds = collect($pinnedSlots)->pluck('_product_id')->filter()->all();

        $rankedProducts = $this->rankedProductPool(
            $surface,
            $setting,
            $limit,
            $usedProductIds,
            $query,
            $newProductRatio
        );
        $activeCampaigns = $isFiltered
            ? collect()
            : FeedCampaign::query()->activeNow()->orderByDesc('weight')->get();

        // limit=NULL یعنی تمام محصولات فعال. ظرفیت کمپین‌ها جدا اضافه می‌شود تا هیچ محصولی حذف نشود.
        $minimumDynamicLimit = count($pinnedSlots) + count($rankedProducts) + $activeCampaigns->count();
        $lastPinnedPosition = $pinnedSlots ? (max(array_keys($pinnedSlots)) + 1) : 0;
        $resolvedLimit = $limit ?? max($minimumDynamicLimit, $lastPinnedPosition);
        $stream = $this->interleaveCampaigns($rankedProducts, $activeCampaigns, $setting->campaign_ratio, $resolvedLimit);

        // ── ساخت آرایه‌ی نهایی به‌طول $limit: اول جای Pin‌ها ثابت، بقیه از Stream پر می‌شود ──
        $final = array_fill(0, $resolvedLimit, null);
        foreach ($pinnedSlots as $position => $slot) {
            if ($position >= 0 && $position < $resolvedLimit) {
                $final[$position] = $slot;
            }
        }

        $streamIndex = 0;
        for ($i = 0; $i < $resolvedLimit; $i++) {
            if ($final[$i] !== null) {
                continue;
            }
            if ($streamIndex >= count($stream)) {
                break;
            }
            $final[$i] = $stream[$streamIndex];
            $streamIndex++;
        }

        $final = array_values(array_filter($final));

        return $this->assignTileSizes($final, $setting);
    }

    /**
     * آیتم‌های Pin شده‌ی این بستر را resolve می‌کند (موقعیت => داده‌ی نمایشی + product_id برای حذف از استخر عادی).
     */
    protected function resolvePinnedSlots(FeedSurface $surface, FeedSetting $setting, int $limit): array
    {
        $pins = FeedPinnedItem::with('contentItem.content')
            ->where('feed_surface_id', $surface->id)
            ->where('position', '<=', $limit)
            ->get();

        $slots = [];
        foreach ($pins as $pin) {
            $contentItem = $pin->contentItem;
            if (! $contentItem || ! $contentItem->is_active || ! $contentItem->content) {
                continue;
            }
            if ($contentItem->content_type === 'product'
                && ! $this->productPassesAudienceFilters((int) $contentItem->content_id, $setting)) {
                continue;
            }
            $resolved = $this->resolveDisplayData($contentItem->content_type, $contentItem->content);
            if (! $resolved) {
                continue;
            }
            $resolved['_pinned'] = true;
            if ($contentItem->content_type === 'product') {
                $resolved['_product_id'] = $contentItem->content_id;
            }
            $slots[$pin->position - 1] = $resolved;
        }

        return $slots;
    }

    /**
     * استخر محصولات فعال را با ترکیب تازگی/ویژه‌بودن + Boost دستی + تصادفی‌بودن کنترل‌شده مرتب می‌کند.
     */
    protected function rankedProductPool(
        FeedSurface $surface,
        FeedSetting $setting,
        ?int $limit,
        array $excludeIds = [],
        string $query = '',
        int $newProductRatio = 0
    ): array
    {
        $productsQuery = Product::query()
            ->where('status', 'active')
            ->when(count($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->when($query !== '', fn (Builder $builder) => $this->applyProductSearch($builder, $query))
            ->orderByDesc('is_featured')
            ->orderByDesc('is_trending')
            ->orderByDesc('created_at');

        $this->applyAudienceFilters($productsQuery, $setting);

        // فید محدود برای بسترهای دیگر همان استخر قبلی را دارد؛ اکسپلور با limit=NULL همه را می‌گیرد.
        if ($limit !== null) {
            $productsQuery->limit(max($limit * 3, 60));
        }

        $products = $productsQuery->get();

        if ($products->isEmpty()) {
            return [];
        }

        // ── نقشه‌ی Boost دستی (از طریق feed_content_items → feed_content_scores) ──
        $itemIds = FeedContentItem::query()
            ->where('content_type', 'product')
            ->whereIn('content_id', $products->pluck('id'))
            ->pluck('id', 'content_id'); // [product_id => feed_content_item_id]

        $boostMap = [];
        if ($itemIds->isNotEmpty()) {
            $scores = FeedContentScore::query()
                ->where('feed_surface_id', $surface->id)
                ->whereIn('feed_content_item_id', $itemIds->values())
                ->pluck('manual_boost', 'feed_content_item_id');

            foreach ($itemIds as $productId => $contentItemId) {
                if (isset($scores[$contentItemId])) {
                    $boostMap[$productId] = (int) $scores[$contentItemId];
                }
            }
        }

        $randomness = max(0, min(100, (int) $setting->randomness_level));
        $count = $products->count();

        $ranked = $products->values()->map(function ($product, $index) use ($count, $boostMap, $randomness) {
            $baseScore = $count - $index; // هرچی جلوتر در Query، امتیاز پایه بالاتر
            $boost = $boostMap[$product->id] ?? 0;
            $jitter = $randomness > 0 ? random_int(0, $randomness) : 0;
            return [
                'product' => $product,
                'sort_key' => $baseScore + ($boost * 2) + $jitter,
            ];
        })->sortByDesc('sort_key')->values();

        $ranked = $this->mixNewProducts($ranked, $newProductRatio);

        return $ranked->map(function ($row) {
            $data = $this->resolveDisplayData('product', $row['product']);
            $data['_product_id'] = $row['product']->id;
            return $data;
        })->filter()->values()->all();
    }

    /**
     * همه‌ی دسته‌بندی‌های فعال و همه‌ی تگ/دسته‌های ثبت‌شده روی محصولات فعال.
     * خروجی در هر درخواست از دیتابیس ساخته می‌شود؛ بنابراین موارد آینده خودکار ظاهر می‌شوند.
     *
     * @return array{0: array<int, array{label:string, query:string, kind:string}>, 1: array<int, array{label:string, query:string, kind:string}>}
     */
    public function discoverableTerms(): array
    {
        $terms = collect();

        Category::query()->active()->orderBy('sort_order')->orderBy('id')
            ->get(['name_fa', 'name', 'name_en'])
            ->each(function (Category $category) use ($terms) {
                $label = trim((string) ($category->name_fa ?: $category->name ?: $category->name_en));
                if ($label !== '') {
                    $terms->push(['label' => $label, 'query' => $label, 'kind' => 'category']);
                }
            });

        Product::query()->where('status', 'active')->get(['category', 'subcategory', 'tags'])
            ->each(function (Product $product) use ($terms) {
                foreach ([$product->category, $product->subcategory] as $category) {
                    $label = trim((string) $category);
                    if ($label !== '') {
                        $terms->push(['label' => $label, 'query' => $label, 'kind' => 'category']);
                    }
                }
                foreach ((array) $product->tags as $tag) {
                    $label = trim(ltrim((string) $tag, '#'));
                    if ($label !== '') {
                        $terms->push(['label' => '#' . $label, 'query' => $label, 'kind' => 'tag']);
                    }
                }
            });

        $unique = $terms->unique(fn (array $term) => mb_strtolower($term['query']))->values();
        $rows = [[], []];
        foreach ($unique as $index => $term) {
            $rows[$index % 2][] = $term;
        }

        return $rows;
    }

    /** جستجوی کامل محصول بر اساس نام، توضیحات، سئو، تگ و دسته‌بندی‌های مستقیم/چندگانه. */
    protected function applyProductSearch(Builder $query, string $term): Builder
    {
        $words = collect(preg_split('/\s+/u', $term))
            ->map(fn ($word) => trim((string) $word))
            ->filter()
            ->values();

        return $query->where(function (Builder $search) use ($words) {
            foreach ($words as $word) {
                $like = '%' . $word . '%';
                $search->orWhere('name_fa', 'like', $like)
                    ->orWhere('name_en', 'like', $like)
                    ->orWhere('description_fa', 'like', $like)
                    ->orWhere('description_en', 'like', $like)
                    ->orWhere('meta_title', 'like', $like)
                    ->orWhere('meta_description', 'like', $like)
                    ->orWhere('meta_keywords', 'like', $like)
                    ->orWhere('category', 'like', $like)
                    ->orWhere('subcategory', 'like', $like)
                    ->orWhere('tags', 'like', $like)
                    ->orWhereHas('categories', function (Builder $categories) use ($like) {
                        $categories->where('name_fa', 'like', $like)
                            ->orWhere('name_en', 'like', $like)
                            ->orWhere('name', 'like', $like);
                    });
            }
        });
    }

    /**
     * محصولات جدید را با سهم هدف در ابتدای جریان پخش می‌کند، اما در نهایت هیچ محصولی حذف نمی‌شود.
     */
    protected function mixNewProducts(Collection $ranked, int $ratio): Collection
    {
        if ($ratio <= 0 || $ranked->isEmpty()) {
            return $ranked->values();
        }

        $new = $ranked
            ->filter(fn (array $row) => (bool) $row['product']->is_new)
            ->sortByDesc(fn (array $row) => $row['product']->created_at?->getTimestamp() ?? 0)
            ->values();
        $regular = $ranked->reject(fn (array $row) => (bool) $row['product']->is_new)->values();
        if ($new->isEmpty()) {
            return $ranked->values();
        }
        if ($regular->isEmpty()) {
            return $new;
        }

        $mixed = collect();
        $newIndex = 0;
        $regularIndex = 0;
        $usedNew = 0;
        $position = 0;

        while ($newIndex < $new->count() || $regularIndex < $regular->count()) {
            // ceil باعث می‌شود در صورت وجود محصول جدید، نخستین جایگاه هم از همان گروه باشد.
            $targetNewCount = (int) ceil(($position + 1) * ($ratio / 100));
            $takeNew = $newIndex < $new->count()
                && ($regularIndex >= $regular->count() || $usedNew < $targetNewCount);

            if ($takeNew) {
                $mixed->push($new[$newIndex++]);
                $usedNew++;
            } else {
                $mixed->push($regular[$regularIndex++]);
            }
            $position++;
        }

        return $mixed;
    }

    protected function normaliseSearchTerm(string $term): string
    {
        return trim(ltrim($term, "# \t\n\r\0\x0B"));
    }

    protected function productPassesAudienceFilters(int $productId, FeedSetting $setting): bool
    {
        $query = Product::query()->whereKey($productId)->where('status', 'active');
        $this->applyAudienceFilters($query, $setting);

        return $query->exists();
    }

    /**
     * قوانین «نمایش بده / نمایش نده» اکسپلور را روی Query محصولات اعمال می‌کند.
     * در بخش ورودی، گزینه‌های هر گروه OR و گروه‌های مختلف AND هستند؛ در بخش
     * خروجی، تطبیق با هر گزینه برای حذف محصول کافی است.
     */
    protected function applyAudienceFilters(Builder $query, FeedSetting $setting): Builder
    {
        $include = is_array($setting->include_filters) ? $setting->include_filters : [];
        $exclude = is_array($setting->exclude_filters) ? $setting->exclude_filters : [];

        $includeCategoryIds = $this->expandCategoryIds((array) ($include['categories'] ?? []));
        if ($includeCategoryIds !== []) {
            $query->where(function (Builder $categoryQuery) use ($includeCategoryIds) {
                $categoryQuery->whereIn('category_id', $includeCategoryIds)
                    ->orWhereHas('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $includeCategoryIds));
            });
        }

        $includeTags = $this->cleanStringFilter((array) ($include['tags'] ?? []));
        if ($includeTags !== []) {
            $query->where(function (Builder $tagQuery) use ($includeTags) {
                foreach ($includeTags as $tag) {
                    $tagQuery->orWhereJsonContains('tags', $tag);
                }
            });
        }

        $includeTraits = array_values(array_intersect(
            ['featured', 'normal', 'new', 'trending'],
            (array) ($include['traits'] ?? [])
        ));
        if ($includeTraits !== []) {
            $query->where(function (Builder $traitQuery) use ($includeTraits) {
                foreach ($includeTraits as $trait) {
                    match ($trait) {
                        'featured' => $traitQuery->orWhere('is_featured', true),
                        'normal' => $traitQuery->orWhere('is_featured', false),
                        'new' => $traitQuery->orWhere('is_new', true),
                        'trending' => $traitQuery->orWhere('is_trending', true),
                    };
                }
            });
        }

        $includeMedia = array_values(array_intersect(['photo', 'video'], (array) ($include['media'] ?? [])));
        if ($includeMedia !== []) {
            $query->where(function (Builder $mediaQuery) use ($includeMedia) {
                if (in_array('photo', $includeMedia, true)) {
                    $mediaQuery->orWhereIn('media_type', ['photo', 'both']);
                }
                if (in_array('video', $includeMedia, true)) {
                    $mediaQuery->orWhereIn('media_type', ['video', 'both']);
                }
            });
        }

        $includeProducts = $this->cleanIntegerFilter((array) ($include['products'] ?? []));
        if ($includeProducts !== []) {
            $query->whereIn('products.id', $includeProducts);
        }

        $excludeCategoryIds = $this->expandCategoryIds((array) ($exclude['categories'] ?? []));
        if ($excludeCategoryIds !== []) {
            $query->where(function (Builder $directCategoryQuery) use ($excludeCategoryIds) {
                $directCategoryQuery->whereNull('category_id')->orWhereNotIn('category_id', $excludeCategoryIds);
            })
                ->whereDoesntHave('categories', fn (Builder $relation) => $relation->whereIn('categories.id', $excludeCategoryIds));
        }

        foreach ($this->cleanStringFilter((array) ($exclude['tags'] ?? [])) as $tag) {
            $query->where(function (Builder $tagQuery) use ($tag) {
                $tagQuery->whereNull('tags')->orWhereJsonDoesntContain('tags', $tag);
            });
        }

        $excludeTraits = array_values(array_intersect(
            ['featured', 'normal', 'new', 'trending'],
            (array) ($exclude['traits'] ?? [])
        ));
        foreach ($excludeTraits as $trait) {
            match ($trait) {
                'featured' => $query->where('is_featured', false),
                'normal' => $query->where('is_featured', true),
                'new' => $query->where('is_new', false),
                'trending' => $query->where('is_trending', false),
            };
        }

        $excludeMedia = array_values(array_intersect(['photo', 'video'], (array) ($exclude['media'] ?? [])));
        if (in_array('photo', $excludeMedia, true)) {
            $query->whereNotIn('media_type', ['photo', 'both']);
        }
        if (in_array('video', $excludeMedia, true)) {
            $query->whereNotIn('media_type', ['video', 'both']);
        }

        $excludeProducts = $this->cleanIntegerFilter((array) ($exclude['products'] ?? []));
        if ($excludeProducts !== []) {
            $query->whereNotIn('products.id', $excludeProducts);
        }

        return $query;
    }

    protected function expandCategoryIds(array $ids): array
    {
        $selected = $this->cleanIntegerFilter($ids);
        if ($selected === []) {
            return [];
        }

        $parents = Category::query()->pluck('parent_id', 'id');
        $expanded = array_fill_keys($selected, true);
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($parents as $id => $parentId) {
                if ($parentId !== null && isset($expanded[(int) $parentId]) && ! isset($expanded[(int) $id])) {
                    $expanded[(int) $id] = true;
                    $changed = true;
                }
            }
        }

        return array_map('intval', array_keys($expanded));
    }

    protected function cleanIntegerFilter(array $values): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $values), fn (int $id) => $id > 0)));
    }

    protected function cleanStringFilter(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($value) => trim(ltrim((string) $value, '#')),
            $values
        ))));
    }

    /**
     * کمپین‌های فعال را با نسبت تعیین‌شده در تنظیمات، داخل استخر محصولات تزریق می‌کند.
     */
    protected function interleaveCampaigns(array $products, $campaigns, int $campaignRatio, int $limit): array
    {
        if ($campaigns->isEmpty() || $campaignRatio <= 0) {
            return $products;
        }

        $every = max(1, (int) round(100 / $campaignRatio));
        $stream = [];
        $campaignIndex = 0;
        $productIndex = 0;
        $slot = 0;

        while (count($stream) < $limit && ($productIndex < count($products) || $campaignIndex < $campaigns->count())) {
            $isCampaignSlot = ($slot > 0 && $slot % $every === 0 && $campaignIndex < $campaigns->count());

            if ($isCampaignSlot) {
                $campaign = $campaigns[$campaignIndex % $campaigns->count()];
                $data = $this->resolveDisplayData('campaign', $campaign);
                if ($data) {
                    $data['_campaign'] = true;
                    $stream[] = $data;
                }
                $campaignIndex++;
            } elseif ($productIndex < count($products)) {
                $stream[] = $products[$productIndex];
                $productIndex++;
            } else {
                break;
            }

            $slot++;
        }

        return $stream;
    }

    /**
     * داده‌ی نمایشی یکنواخت را از روی نوع محتوا می‌سازد (محصول/کمپین/دسته).
     */
    protected function resolveDisplayData(string $type, $model): ?array
    {
        if (! $model) {
            return null;
        }

        if ($type === 'product') {
            /** @var Product $model */
            $isVideo = in_array($model->media_type, ['video', 'both'], true)
                && filled($model->preview_video_url);
            return [
                'type' => 'product',
                'video' => $isVideo,
                'src' => $isVideo ? $this->productVideoUrl($model) : $model->displayImageUrl(),
                'poster' => $model->displayImageUrl(),
                'name' => $model->name_fa,
                'tag' => $model->category ?: $model->subcategory ?: 'وطن AI',
                'link' => route('app.product', $model->route_slug),
                '_allowed_sizes' => $this->productAllowedSizes($model),
            ];
        }

        if ($type === 'campaign') {
            /** @var FeedCampaign $model */
            return [
                'type' => 'campaign',
                'video' => false,
                'src' => $model->image ? asset('storage/' . $model->image) : asset('assets/img/placeholder.webp'),
                'poster' => null,
                'name' => $model->title_fa,
                'tag' => 'ویژه',
                'link' => $model->link ?: '#',
                '_allowed_sizes' => ['size-wide'],
            ];
        }

        return null;
    }

    protected function productVideoUrl(Product $product): string
    {
        $path = trim((string) $product->preview_video_url);
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    /**
     * اندازه‌ی هر کاشی را از استخر وزن‌دار (طبق تنظیمات ادمین) تصادفی تخصیص می‌دهد.
     * آیتم‌های Pin شده/کمپین با شانس بیشتر اندازه‌ی بزرگ‌تر می‌گیرند تا برجسته دیده شوند.
     */
    protected function assignTileSizes(array $tiles, FeedSetting $setting): array
    {
        $weights = $setting->tile_weights ?: FeedSetting::LAYOUT_PRESETS['classic'];
        $pool = [];
        foreach ($weights as $size => $weight) {
            for ($k = 0; $k < max(0, (int) $weight); $k++) {
                $pool[] = $size;
            }
        }
        if (empty($pool)) {
            $pool = ['size-1x1'];
        }

        $prominentPool = ['size-big', 'size-wide', 'size-tall'];

        $assigned = array_map(function ($tile) use ($pool, $prominentPool) {
            $allowed = $tile['_allowed_sizes'] ?? null; // NULL = بدون محدودیت (مثلاً کمپین‌ها)

            if (! empty($tile['_campaign'])) {
                $choices = ['size-wide'];
            } elseif (! empty($tile['_pinned'])) {
                $choices = $prominentPool;
                if (is_array($allowed) && $allowed) {
                    $inter = array_values(array_intersect($prominentPool, $allowed));
                    $choices = $inter ?: $allowed;
                }
            } else {
                $choices = $pool;
                if (is_array($allowed) && $allowed) {
                    $inter = array_values(array_intersect($pool, $allowed));
                    $choices = $inter ?: $allowed;
                }
            }

            $tile['size'] = $choices[array_rand($choices)];
            return $tile;
        }, $tiles);

        // در فیدهای به‌اندازه‌ی کافی بزرگ، هر اندازه‌ای که وزن فعال دارد حداقل
        // یک نماینده خواهد داشت؛ انتخاب فقط از محصولی انجام می‌شود که آن اندازه را مجاز کرده باشد.
        if (count($assigned) >= 8) {
            foreach (['size-tall', 'size-big', 'size-wide'] as $requiredSize) {
                if (($weights[$requiredSize] ?? 0) <= 0
                    || collect($assigned)->contains(fn (array $tile) => $tile['size'] === $requiredSize)) {
                    continue;
                }

                foreach ($assigned as &$candidate) {
                    $allowed = $candidate['_allowed_sizes'] ?? null;
                    $canUseSize = ! is_array($allowed) || in_array($requiredSize, $allowed, true);
                    if (($candidate['type'] ?? null) === 'product'
                        && $candidate['size'] === 'size-1x1'
                        && $canUseSize) {
                        $candidate['size'] = $requiredSize;
                        break;
                    }
                }
                unset($candidate);
            }
        }

        return array_map(function (array $tile) {
            $tile['allowed_sizes'] = $tile['_allowed_sizes'] ?? [
                'size-1x1', 'size-wide', 'size-tall', 'size-big',
            ];
            unset($tile['_pinned'], $tile['_campaign'], $tile['_product_id'], $tile['_allowed_sizes']);
            return $tile;
        }, $assigned);
    }

    /**
     * حالت‌های کاشیِ مجازِ یک محصول را به کلیدهای اندازه‌ی گرید نگاشت می‌کند.
     * NULL یعنی محدودیتی ندارد (همه اندازه‌ها مجاز).
     */
    protected function productAllowedSizes(Product $product): ?array
    {
        $map = ['1x1' => 'size-1x1', '2x2' => 'size-big', '1x2' => 'size-tall', '2x1' => 'size-wide'];
        $tiles = $product->explore_tiles;
        if (! is_array($tiles) || empty($tiles)) {
            return null;
        }
        $out = [];
        foreach ($tiles as $t) {
            if (isset($map[$t])) { $out[] = $map[$t]; }
        }
        return $out ?: null;
    }

    /**
     * برای استفاده‌ی پنل ادمین: پیدا کردن یا ساختن ردیف feed_content_items مربوط به یک محصول
     * (فقط وقتی ادمین بخواهد آن محصول را Pin یا Boost کند لازم می‌شود — نه به‌صورت سراسری).
     */
    public function ensureContentItem(string $type, int $contentId): FeedContentItem
    {
        return FeedContentItem::firstOrCreate([
            'content_type' => $type,
            'content_id' => $contentId,
        ], [
            'is_active' => true,
        ]);
    }
}
