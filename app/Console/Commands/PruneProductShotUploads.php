<?php

namespace App\Console\Commands;

use App\Models\ShotBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * پاک‌سازی عکس‌های ورودی «استودیو محصول»:
 *  - آپلودهای مرحله‌ی فیلتر کیفیت قدیمی‌تر از مهلت نگهداری؛
 *  - نسخه‌ی عکس‌های batchهای تمام‌شده/رهاشده قدیمی‌تر از مهلت.
 * خروجی‌ها (generated/*) دست نمی‌خورند؛ چرخه‌ی عمر آن‌ها مثل بقیه‌ی خروجی‌های کاربر است.
 */
class PruneProductShotUploads extends Command
{
    protected $signature = 'product-shots:prune {--dry-run : فقط گزارش، بدون حذف}';

    protected $description = 'حذف عکس‌های ورودی موقت استودیو محصول پس از مهلت نگهداری';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $dir = trim((string) config('product_shots.upload_dir', 'uploads/product-shots'), '/');
        $cutoff = now()->subHours(max(1, (int) config('product_shots.source_retention_hours', 48)));
        $dry = (bool) $this->option('dry-run');
        $deleted = 0;

        foreach ($disk->allFiles($dir) as $path) {
            if (str_starts_with($path, $dir . '/batches/')) {
                continue;
            }
            if ($disk->lastModified($path) < $cutoff->getTimestamp()) {
                $deleted++;
                if (! $dry) {
                    $disk->delete($path);
                }
            }
        }

        ShotBatch::query()
            ->whereNull('sources_deleted_at')
            ->where('updated_at', '<', $cutoff)
            ->chunkById(100, function ($batches) use ($disk, $dry, &$deleted) {
                foreach ($batches as $batch) {
                    foreach ((array) $batch->source_paths as $path) {
                        if ($disk->exists((string) $path)) {
                            $deleted++;
                            if (! $dry) {
                                $disk->delete((string) $path);
                            }
                        }
                    }
                    if (! $dry) {
                        $batch->forceFill(['sources_deleted_at' => now()])->save();
                    }
                }
            });

        $this->info(($dry ? '[آزمایشی] ' : '') . "{$deleted} فایل ورودی پاک شد.");

        return self::SUCCESS;
    }
}
