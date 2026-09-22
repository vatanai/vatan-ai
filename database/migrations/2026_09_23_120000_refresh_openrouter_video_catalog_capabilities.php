<?php

use App\Services\AiCatalogSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ ویدیوی OpenRouter باید هنگام استقرار با قرارداد زنده‌ی provider
     * تطبیق داده شود تا قیمت، پارامترهای مجاز و قابلیت ورودی مدل‌ها قدیمی نماند.
     * خطای موقت شبکه نباید کل migrationهای برنامه را متوقف کند؛ اجرای دستی
     * `ai:sync-catalog openrouter` بعداً همین همگام‌سازی را تکرار می‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        try {
            app(AiCatalogSyncService::class)->syncOpenRouterVideos();
        } catch (Throwable $exception) {
            Log::warning('OpenRouter video capability refresh was skipped', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // داده‌ی کاتالوگ به نسخه‌ی قبلی برگردانده نمی‌شود؛ ممکن است در سفارش‌ها
        // یا گزارش‌های مالی قبلی استفاده شده باشد.
    }
};
