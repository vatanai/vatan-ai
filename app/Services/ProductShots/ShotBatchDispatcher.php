<?php

namespace App\Services\ProductShots;

use App\Jobs\GenerateProductShotItem;
use App\Models\ShotBatch;
use App\Models\ShotBatchItem;
use Illuminate\Support\Facades\Bus;

/** شات‌های هر پک را پشت‌سرهم در صف می‌گذارد تا مصرف حافظه و API کنترل شود. */
class ShotBatchDispatcher
{
    public function dispatchBatch(ShotBatch $batch): void
    {
        $jobs = $batch->items()
            ->where('status', 'pending')
            ->get(['id', 'shot_batch_id'])
            ->map(fn (ShotBatchItem $item) => new GenerateProductShotItem($item->id, (int) $batch->user_id))
            ->all();

        if ($jobs !== []) {
            Bus::chain($jobs)->dispatch();
        }
    }

    public function dispatchItem(ShotBatchItem $item): void
    {
        $item->forceFill(['status' => 'pending', 'error_message' => null, 'finished_at' => null])->save();
        GenerateProductShotItem::dispatch($item->id, (int) $item->batch->user_id);
    }
}
