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

    public function __construct(private ShotPromptBuilder $prompts) {}

    /** شات‌های فعال محصول برای نمایش به کاربر. */
    public function shotCards(Product $product): array
    {
        return $product->enabledProductShots()->map(fn (ProductShot $ps) => [
            'key' => $ps->shot->key,
            'name' => $ps->shot->name_fa,
            'description' => $ps->shot->description_fa,
            'category' => $ps->shot->category,
            'credits' => $ps->credits(),
            'is_default' => (bool) $ps->is_default,
            'sample_url' => $ps->sampleImageUrl(),
            'tags' => array_slice($ps->shot->tokenLabels(), 0, 3),
        ])->values()->all();
    }

    /** چهار تصویر برای کولاژ کارت «پک» (نمونه‌ی شات‌ها، در نبود آن کاور محصول). */
    public function collage(Product $product, int $limit = 4): array
    {
        $urls = $product->enabledProductShots()
            ->map(fn (ProductShot $ps) => $ps->sampleImageUrl())
            ->filter()
            ->values();

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
    public function createBatch(User $user, Product $product, array $uploads, array $shotKeys, string $aspectRatio): ShotBatch
    {
        $settings = ProductShotSetting::current();
        $allowedRatios = (array) config('product_shots.aspect_ratios', ['4:5', '1:1', '9:16']);
        if (! in_array($aspectRatio, $allowedRatios, true)) {
            $aspectRatio = (string) config('product_shots.default_aspect_ratio', '4:5');
        }

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

        $sourcePaths = [];
        $preflight = [];
        foreach (array_values($uploads) as $index => $upload) {
            $meta = $this->upload((string) ($upload['id'] ?? ''));
            if (! $meta || (int) ($meta['user_id'] ?? 0) !== (int) $user->id) {
                throw ValidationException::withMessages(['uploads' => 'عکس محصول پیدا نشد یا منقضی شده؛ دوباره بارگذاری کنید.']);
            }
            if ($index === 0 && ($meta['preflight']['verdict'] ?? 'green') === 'red') {
                throw ValidationException::withMessages(['uploads' => 'این عکس برای ساخت مناسب نیست؛ عکس واضح‌تری بفرستید.']);
            }
            $useFixed = ! empty($upload['use_fixed']) && ! empty($meta['fixed_path']);
            $sourcePaths[] = $useFixed ? $meta['fixed_path'] : $meta['path'];
            if ($index === 0) {
                $preflight = (array) ($meta['preflight'] ?? []);
                $preflight['used_fixed'] = $useFixed;
            }
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

        return DB::transaction(function () use ($user, $product, $selected, $aspectRatio, $sourcePaths, $preflight) {
            $batch = ShotBatch::create([
                'user_id' => $user->id,
                'product_id' => $product->id,
                'status' => 'pending',
                'aspect_ratio' => $aspectRatio,
                'source_paths' => $sourcePaths,
                'preflight' => $preflight,
                'shots_total' => $selected->count(),
                'credits_quoted' => $selected->sum(fn (ProductShot $ps) => $ps->credits()),
                'source' => 'app',
            ]);

            $selected->values()->each(function (ProductShot $ps, int $i) use ($batch) {
                ShotBatchItem::create([
                    'shot_batch_id' => $batch->id,
                    'shot_id' => $ps->shot_id,
                    'shot_key' => $ps->shot->key,
                    'shot_name_fa' => $ps->shot->name_fa,
                    'status' => 'pending',
                    'credits' => $ps->credits(),
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
            'credits_quoted' => $batch->credits_quoted,
            'credits_charged' => (int) $items->sum('credits_charged'),
            'credits_refunded' => (int) $items->sum('credits_refunded'),
            'source_url' => \App\Models\ShotLibrary::publicUrl($batch->source_paths[0] ?? null),
            'items' => $items->map(fn (ShotBatchItem $item) => $item->toClientArray())->values()->all(),
            'run_urls' => $items->mapWithKeys(fn (ShotBatchItem $item) => [
                $item->id => route('app.product-shots.items.run', [$batch->uuid, $item->id]),
            ])->all(),
            'download_url' => route('app.product-shots.batches.download', $batch->uuid),
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
