<?php

namespace App\Jobs;

use App\Models\TelegramProductDraft;
use App\Services\TelegramProductDraftService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessTelegramProductDraftJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 90;

    public function __construct(public string $draftId)
    {
    }

    public function handle(TelegramProductDraftService $service): void
    {
        $draft = TelegramProductDraft::query()->find($this->draftId);
        if ($draft) {
            $service->process($draft);
        }
    }
}
