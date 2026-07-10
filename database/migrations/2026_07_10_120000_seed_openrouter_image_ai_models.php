<?php

use Database\Seeders\AiModelSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * درج خودکار ۱۵ مدل تصویری OpenRouter هنگام دیپلوی.
 *
 * چون روی سرور اصلی معمولاً فقط `php artisan migrate --force` اجرا می‌شود،
 * این migration همان AiModelSeeder را صدا می‌زند تا مدل‌ها بدون نیاز به
 * اجرای دستی db:seed به سایت اصلی هم اضافه شوند.
 * Seeder کاملاً idempotent است؛ اجرای دوباره داده تکراری نمی‌سازد.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return; // جدول هنوز ساخته نشده؛ migration های قبلی آن را می‌سازند
        }

        (new AiModelSeeder())->run();
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) {
            return;
        }

        $ids = [
            'openai/gpt-image-2',
            'openai/gpt-image-1',
            'openai/gpt-image-1-mini',
            'openai/gpt-5.4-image-2',
            'google/gemini-3.1-flash-image',
            'google/gemini-3.1-flash-image-preview',
            'google/gemini-3.1-flash-lite-image',
            'google/gemini-3-pro-image',
            'sourceful/riverflow-v2.5-pro',
            'sourceful/riverflow-v2.5-fast',
            'sourceful/riverflow-v2-pro',
            'sourceful/riverflow-v2-fast',
            'x-ai/grok-imagine-image-quality',
            'recraft/recraft-v4.1-pro-vector',
            'recraft/recraft-v4.1-vector',
        ];

        \App\Models\AiModel::whereIn('openrouter_model_id', $ids)->delete();
    }
};
