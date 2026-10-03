<?php

namespace App\Jobs;

use App\Models\ShotBatchItem;
use App\Models\User;
use App\Services\ProductShots\ShotGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/** ساخت یک شات در پس‌زمینه؛ بستن صفحه‌ی کاربر اجرای پک را متوقف نمی‌کند. */
class GenerateProductShotItem implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 420;

    public function __construct(public int $itemId, public int $userId) {}

    public function handle(ShotGenerationService $generator): void
    {
        $item = ShotBatchItem::query()->find($this->itemId);
        $user = User::query()->find($this->userId);
        if (! $item || ! $user || $item->status === 'completed') {
            return;
        }

        $generator->runItem($item, $user);
    }

    public function failed(?Throwable $exception): void
    {
        $item = ShotBatchItem::query()->find($this->itemId);
        if (! $item || $item->status === 'completed') {
            return;
        }

        $item->forceFill([
            'status' => 'failed',
            'error_message' => 'ساخت این شات در صف پردازش متوقف شد؛ اعتباری که مصرف نشده باشد کسر نمی‌شود.',
            'finished_at' => now(),
        ])->save();
        $item->batch?->refreshStatus();

        if ($exception) {
            report(new \RuntimeException(
                'Product shot queue job failed: ' . Str::limit($exception->getMessage(), 500, ''),
                previous: $exception,
            ));
        }
    }
}
