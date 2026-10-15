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
            $this->pushResult($service, $draft);
        }
    }

    /**
     * اتصال مستقیم (بدون n8n): نتیجه خودش برای مدیر ارسال می‌شود و پولینگ لازم نیست.
     * در مسیر n8n هیچ کاری نمی‌کند.
     */
    private function pushResult(TelegramProductDraftService $service, TelegramProductDraft $draft): void
    {
        if (data_get($draft->fresh()?->input_payload, 'transport') !== 'direct') {
            return;
        }
        try {
            $response = $service->resultResponse($draft->fresh());
            app(\App\Services\TelegramProduct\TelegramProductBotRenderer::class)->deliver($response, ['chat_id' => (string) $draft->chat_id]);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('telegram product result push failed', ['draft' => $draft->id, 'error' => $exception->getMessage()]);
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
        if ($draft) {
            $this->pushResult(app(TelegramProductDraftService::class), $draft);
        }
    }
}
