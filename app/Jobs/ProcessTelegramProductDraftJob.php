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

    /**
     * اگه جاب بعد از تلاش‌های مجاز شکست بخوره یا تایم‌اوت بشه (مثلاً به‌خاطر کمبود منابع سرور یا
     * کندی OpenRouter)، درفت برای همیشه توی وضعیت processing گیر نکنه؛ یه خطای قابل‌فهم ثبت می‌شه
     * تا هم توی پنل ادمین دیده بشه، هم بات تلگرام به‌جای گیر کردن، پیام خطا نشون بده.
     */
    public function failed(\Throwable $exception): void
    {
        $draft = TelegramProductDraft::query()->find($this->draftId);
        $draft?->forceFill([
            'state' => 'failed',
            'error_message' => \Illuminate\Support\Str::limit($exception->getMessage(), 1000, ''),
        ])->save();
    }
}
