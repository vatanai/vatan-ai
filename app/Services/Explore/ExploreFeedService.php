<?php

namespace App\Services\Explore;

use App\Models\FeedCampaign;
use App\Models\FeedContentItem;
use App\Models\FeedContentScore;
use App\Models\FeedPinnedItem;
use App\Models\FeedSetting;
use App\Models\FeedSurface;
use App\Models\Product;

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
            'layout_style'       => 'classic',
            'tile_weights'       => FeedSetting::LAYOUT_PRESETS['classic'],
            'randomness_level'   => 35,
            'campaign_ratio'     => 5,
            'is_active_version'  => true,
        ]);
    }

    /**
     * ساخت فید نهایی برای یک بستر — خروجی مستقیماً برای رندر گرید کاشی آماده است.
     *
     * @return array<int, array{video: bool, src: string, name: string, tag: string, size: string, link: string}>
     */
    public function buildFeed(string $surfaceKey = 'explore', int $limit = 48): array
    {
        $surface = $this->getOrCreateSurface($surfaceKey);
        $setting = $this->getOrCreateActiveSetting($surface);

        $pinnedSlots = $this->resolvePinnedSlots($surface, $limit);
        $usedProductIds = collect($pinnedSlots)->pluck('_product_id')->filter()->all();

        $rankedProducts = $this->rankedProductPool($surface, $setting, $limit, $usedProductIds);
        $activeCampaigns = FeedCampaign::query()->activeNow()->orderByDesc('weight')->get();

        $stream = $this->interleaveCampaigns($rankedProducts, $activeCampaigns, $setting->campaign_ratio, $limit);

        // ── ساخت آرایه‌ی نهایی به‌طول $limit: اول جای Pin‌ها ثابت، بقیه از Stream پر می‌شود ──
        $final = array_fill(0, $limit, null);
        foreach ($pinnedSlots as $position => $slot) {
            if ($position >= 0 && $position < $limit) {
                $final[$position] = $slot;
            }
        }

        $streamIndex = 0;
        for ($i = 0; $i < $limit; $i++) {
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
    protected function resolvePinnedSlots(FeedSurface $surface, int $limit): array
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
    protected function rankedProductPool(FeedSurface $surface, FeedSetting $setting, int $limit, array $excludeIds = []): array
    {
        $poolSize = max($limit * 3, 60);

        $products = Product::query()
            ->where('status', 'active')
            ->when(count($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
            ->orderByDesc('is_featured')
            ->orderByDesc('is_trending')
            ->orderByDesc('created_at')
            ->limit($poolSize)
            ->get();

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

        return $ranked->map(function ($row) {
            $data = $this->resolveDisplayData('product', $row['product']);
            $data['_product_id'] = $row['product']->id;
            return $data;
        })->filter()->values()->all();
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
            return [
                'type' => 'product',
                'video' => in_array($model->media_type, ['video', 'both'], true),
                'src' => $model->thumbnail ? asset('storage/' . $model->thumbnail) : asset('assets/img/placeholder.webp'),
                'name' => $model->name_fa,
                'tag' => $model->category?->name ?? $model->category ?? 'وطن AI',
                'link' => route('app.product', $model->slug),
            ];
        }

        if ($type === 'campaign') {
            /** @var FeedCampaign $model */
            return [
                'type' => 'campaign',
                'video' => false,
                'src' => $model->image ? asset('storage/' . $model->image) : asset('assets/img/placeholder.webp'),
                'name' => $model->title_fa,
                'tag' => 'ویژه',
                'link' => $model->link ?: '#',
            ];
        }

        return null;
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

        return array_map(function ($tile) use ($pool, $prominentPool) {
            if (! empty($tile['_pinned']) || ! empty($tile['_campaign'])) {
                $tile['size'] = $prominentPool[array_rand($prominentPool)];
            } else {
                $tile['size'] = $pool[array_rand($pool)];
            }
            unset($tile['_pinned'], $tile['_campaign'], $tile['_product_id']);
            return $tile;
        }, $tiles);
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
