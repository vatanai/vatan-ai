<?php

namespace App\Jobs;

use App\Models\TelegramUser;
use App\Services\TelegramReferralMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTelegramReferralMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $telegramUserId)
    {
    }

    public function handle(TelegramReferralMessageService $messages): void
    {
        $telegramUser = TelegramUser::query()->with('user')->find($this->telegramUserId);
        if (! $telegramUser || $telegramUser->is_blocked || ! $telegramUser->user_id) {
            return;
        }

        $messages->sync($telegramUser, (string) $telegramUser->telegram_id);
    }
}
