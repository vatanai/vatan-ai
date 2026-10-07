<?php

namespace App\Services\ProductShots;

use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ProductShotSetting;
use App\Models\ShotBatch;
use App\Models\ShotBatchItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * داده‌ی صفحه‌ی «بساز» پک و ساخت batch از عکس‌های تأییدشده و شات‌های انتخابی.
 */
class ShotPackService
{
    public const UPLOAD_CACHE_PREFIX = 'product-shots:upload:';
    public const UPLOAD_TTL_SECONDS = 21600; // ۶ ساعت

    public function __construct(
        private ShotPromptBuilder $prompts,
        private ShotImageStore $images,
        private ShotVisionService $vision,
    ) {}

    /** شات‌های فعال محصول برای نمایش به کاربر. */
    public function shotCards(Product $product): array
    {
        $qualityLevels = (array) config('product_shots.quality_levels', []);

        return $product->enabledProductShots()->map(fn (ProductShot $ps) => [
            'key' => $ps->shot->key,
            'name' => $ps->shot->name_fa,
            'description' => $ps->shot->description_fa,
            'category' => $ps->shot->category,
            'credits' => $ps->credits(),
            'quality_credits' => collect(array_keys($qualityLevels))->mapWithKeys(fn ($quality) => [$quality => $ps->credits($quality)])->all(),
            'is_default' => (bool) $ps->is_default,
            'sample_url' => $ps->sampleImageUrl(),
            'tags' => array_slice($ps->shot->tokenLabels(), 0, 3),
            'default_aspect_ratio' => $ps->defaultRatio(),
            'allowed_aspect_ratios' => $ps->allowedRatios(),
            'aspect_ratio_user_selectable' => (bool) $ps->aspect_ratio_user_selectable,
        ])->values()->all();
    }

    /** چهار تصویر برای کولاژ کارت «پک» (نمونه‌ی شات‌ها، در نبود آن کاور محصول). */
    public function collage(Product $product, int $limit = 4): array
    {
        // بعد از تبدیل مدل‌ها به URL باید Collection عمومی باشد؛ Collection الکوئنت
        // در unique() انتظار مدل دارد و برای رشته‌ی URL خطا می‌دهد.
        $urls = collect($product->enabledProductShots()
            ->map(fn (ProductShot $ps) => $ps->sampleImageUrl())
            ->filter()
            ->values()
            ->all());

        foreach ((array) $product->sample_outputs as $path) {
            if ($urls->count() >= $limit) {
                break;
            }
            $url = \App\Models\ShotLibrary::publicUrl((string) $path);
            if ($url) {
                $urls->push($url);
            }
        }
        if ($urls->isEmpty()) {
            $urls->push($product->displayImageUrl());
        }

        return $urls->unique()->take($limit)->values()->all();
    }

    public function rememberUpload(string $uploadId, array $meta): void
    {
        Cache::put(self::UPLOAD_CACHE_PREFIX . $uploadId, $meta, self::UPLOAD_TTL_SECONDS);
    }

    public function upload(string $uploadId): ?array
    {
        $meta = Cache::get(self::UPLOAD_CACHE_PREFIX . $uploadId);

        return is_array($meta) ? $meta : null;
    }

    /**
     * @param array<int, array{id:string, use_fixed?:bool}> $uploads اولی = عکس اصلی
     * @param array<int, string> $shotKeys
     */
    public function createBatch(User $user, Product $product, array $uploads, array $shotKeys, string $qualityLevel, array $shotRatios = []): ShotBatch
    {
        $settings = ProductShotSetting::current();
        $qualityLevels = array_keys((array) config('product_shots.quality_levels', ['standard' => 'استاندارد']));
        if (! in_array($qualityLevel, $qualityLevels, true)) $qualityLevel = (string) config('product_shots.default_quality', 'standard');

        $available = $product->enabledProductShots()->keyBy(fn (ProductShot $ps) => $ps->shot->key);
        $shotKeys = array_values(array_unique(array_map('strval', $shotKeys)));
        $selected = collect($shotKeys)->map(fn ($k) => $available->get($k))->filter()->values();
        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['shots' => 'حداقل یک شات انتخاب کنید.']);
        }
        $max = max(1, (int) $settings->max_shots_per_run);
        if ($selected->count() > $max) {
            throw ValidationException::withMessages(['shots' => "حداکثر {$max} شات در هر ساخت قابل انتخاب است."]);
        }

        $creditsQuoted = (int) $selected->sum(fn (ProductShot $ps) => $ps->credits($qualityLevel));
        $availableCredits = max(0, (int) $user->tokens);
        if ($creditsQuoted > $availableCredits) {
            $remaining = $availableCredits;
            $affordable = 0;
            foreach ($selected->sortBy(fn (ProductShot $ps) => $ps->credits($qualityLevel)) as $productShot) {
                $cost = $productShot->credits($qualityLevel);
                if ($cost > $remaining) {
                    break;
                }
                $remaining -= $cost;
                $affordable++;
            }
            $message = $affordable > 0
                ? "اعتبار شما برای ساخت {$affordable} عکس کافی است. تعداد شات‌ها را کم کنید یا اعتبار بیشتری بگیرید."
                : 'اعتبار شما برای ساخت هیچ‌کدام از شات‌های انتخابی کافی نیست؛ ابتدا اعتبار بیشتری بگیرید.';
            throw ValidationException::withMessages(['shots' => $message]);
        }

        $sourcePaths = [];
        $preflight = [];
        foreach (array_values($uploads) as $index => $upload) {
            $meta = $this->upload((string) ($upload['id'] ?? ''));
            if (! $meta || (int) ($meta['user_id'] ?? 0) !== (int) $user->id || (int) ($meta['product_id'] ?? 0) !== (int) $product->id) {
                throw ValidationException::withMessages(['uploads' => 'عکس محصول پیدا نشد یا منقضی شده؛ دوباره بارگذاری کنید.']);
            }
            if (($meta['preflight']['verdict'] ?? 'green') === 'red') {
                $message = trim((string) ($meta['preflight']['suggestion_fa'] ?? '')) ?: 'یکی از عکس‌ها برای ساخت مناسب نیست؛ عکس واضح‌تری بفرستید.';
                throw ValidationException::withMessages(['uploads' => $message]);
            }
            $useFixed = ! empty($upload['use_fixed']) && ! empty($meta['fixed_path']);
            $sourcePaths[] = $useFixed ? $meta['fixed_path'] : $meta['path'];
            $preflight['images'][$index] = (array) ($meta['preflight'] ?? []);
            $preflight['images'][$index]['used_fixed'] = $useFixed;
        }
        if ($sourcePaths === []) {
            throw ValidationException::withMessages(['uploads' => 'یک عکس از محصول بارگذاری کنید.']);
        }
        $maxSources = 1 + (int) config('product_shots.max_extra_angles', 2);
        $sourcePaths = array_slice($sourcePaths, 0, $maxSources);

        // هر batch نسخه‌ی خودش را از عکس‌ها دارد تا پاک‌سازی پس از اتمام یک پک،
        // ساخت دوباره با همان آپلود را خراب نکند.
        $batchDir = trim((string) config('product_shots.upload_dir', 'uploads/product-shots'), '/') . '/batches/' . \Illuminate\Support\Str::uuid();
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $sourcePaths = array_values(array_filter(array_map(function (string $path, int $i) use ($disk, $batchDir) {
            if (! $disk->exists($path)) {
                return null;
            }
            $target = $batchDir . '/' . $i . '.' . (pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
            $disk->copy($path, $target);

            return $target;
        }, $sourcePaths, array_keys($sourcePaths))));
        if ($sourcePaths === []) {
            throw ValidationException::withMessages(['uploads' => 'عکس محصول پیدا نشد یا منقضی شده؛ دوباره بارگذاری کنید.']);
        }

        $productConfiguration = (array) data_get($product->shot_settings, 'preflight', []);
        $setReport = $this->vision->preflightSet($sourcePaths, $settings, $productConfiguration);
        if (($setReport['verdict'] ?? 'green') === 'red') {
            throw ValidationException::withMessages(['uploads' => $setReport['suggestion_fa'] ?: 'مجموعه تصاویر برای ساخت مناسب نیست.']);
        }
        $preflight['set'] = $setReport;
        $preflight['product_description'] = $setReport['product_description'] ?? collect($preflight['images'] ?? [])->pluck('product_description')->filter()->first();

        $sheetPath = null;
        $sheetEnabled = array_key_exists('product_sheet_enabled', $productConfiguration)
            ? (bool) $productConfiguration['product_sheet_enabled']
            : (bool) $settings->product_sheet_enabled;
        if ($sheetEnabled) {
            $sheet = $this->images->createProductSheet(
                $sourcePaths,
                $batchDir . '/sheet',
                (int) ($productConfiguration['product_sheet_size'] ?? $settings->product_sheet_size ?? config('product_shots.product_sheet_size', 2048)),
            );
            $sheetPath = $sheet['path'] ?? null;
        }

        return DB::transaction(function () use ($user, $product, $selected, $qualityLevel, $shotRatios, $sourcePaths, $sheetPath, $preflight, $creditsQuoted) {
            $batch = ShotBatch::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'status' => 'pending',
                'aspect_ratio' => 'mixed',
                'source_paths' => $sourcePaths,
                'product_sheet_path' => $sheetPath,
                'quality_level' => $qualityLevel,
                'preflight' => $preflight,
                'shots_total' => $selected->count(),
                'credits_quoted' => $creditsQuoted,
                'source' => 'app',
            ]);

            $selected->values()->each(function (ProductShot $ps, int $i) use ($batch, $qualityLevel, $shotRatios) {
                $allowed = $ps->allowedRatios();
                $requested = (string) ($shotRatios[$ps->shot->key] ?? '');
                $ratio = $ps->aspect_ratio_user_selectable && in_array($requested, $allowed, true) ? $requested : $ps->defaultRatio();
                ShotBatchItem::create([
                    'shot_batch_id' => $batch->id,
                    'shot_id' => $ps->shot_id,
                    'shot_key' => $ps->shot->key,
                    'shot_name_fa' => $ps->shot->name_fa,
                    'status' => 'pending',
                    'credits' => $ps->credits($qualityLevel),
                    'quality_level' => $qualityLevel,
                    'aspect_ratio' => $ratio,
                    'sort' => $i,
                ]);
            });

            return $batch->load('items');
        });
    }

    public function batchPayload(ShotBatch $batch): array
    {
        $batch->loadMissing('items');
        $items = $batch->items;

        return [
            'uuid' => $batch->uuid,
            'status' => $batch->status,
            'aspect_ratio' => $batch->aspect_ratio,
            'quality_level' => $batch->quality_level,
            'credits_quoted' => $batch->credits_quoted,
            'credits_charged' => (int) $items->sum('credits_charged'),
            'credits_refunded' => (int) $items->sum('credits_refunded'),
            'balance' => (int) ($batch->user?->tokens ?? 0),
            'source_url' => \App\Models\ShotLibrary::publicUrl($batch->source_paths[0] ?? null),
            'items' => $items->map(fn (ShotBatchItem $item) => $item->toClientArray())->values()->all(),
            'run_urls' => $items->mapWithKeys(fn (ShotBatchItem $item) => [
                $item->id => route('app.product-shots.items.run', [$batch->uuid, $item->id]),
            ])->all(),
            'download_url' => route('app.product-shots.batches.download', $batch->uuid),
            'show_url' => route('app.product-shots.batches.show', $batch->uuid),
        ];
    }

    /** مجموع هزینه‌ی واقعی API امروز (دلار) برای سقف روزانه. */
    public function todayCostUsd(): float
    {
        return (float) ShotBatchItem::query()
            ->whereDate('finished_at', today())
            ->sum('cost_usd');
    }

    public function enabledShots(Product $product): Collection
    {
        return $product->enabledProductShots();
    }
}
