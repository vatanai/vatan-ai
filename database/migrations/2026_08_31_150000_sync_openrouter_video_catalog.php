<?php

use App\Services\AiCatalogSyncService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ ویدیوی OpenRouter از endpoint جداگانه‌ای می‌آید. ابتدا آن را
     * همگام می‌کنیم و سپس محصول عمومی استودیو را روی یک مدل واقعیِ دارای
     * تصویر فریم اول و قیمت قابل‌محاسبه قرار می‌دهیم.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        try {
            app(AiCatalogSyncService::class)->syncOpenRouterVideos();
        } catch (Throwable $exception) {
            Log::warning('OpenRouter video catalog migration was skipped', ['message' => $exception->getMessage()]);
        }

        if (! Schema::hasTable('products')) {
            return;
        }

        $primary = DB::table('ai_models')
            ->where('provider', 'openrouter')
            ->where('output_modality', 'video')
            ->where('openrouter_model_id', 'x-ai/grok-imagine-video')
            ->where('is_active', true)
            ->first();
        $fallback = DB::table('ai_models')
            ->where('provider', 'openrouter')
            ->where('output_modality', 'video')
            ->where('openrouter_model_id', 'alibaba/wan-3.0')
            ->where('is_active', true)
            ->first();

        if (! $primary) {
            return;
        }

        $values = [
            'primary_model' => 'x-ai/grok-imagine-video',
            'ai_provider' => 'openrouter',
            'fallback_models' => json_encode($fallback ? ['alibaba/wan-3.0'] : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'fallback_model_providers' => json_encode($fallback ? ['openrouter'] : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ];

        DB::table('products')
            ->where('slug', 'ai-cinematic-short-video')
            ->update($values);
    }

    public function down(): void
    {
        // مدل‌های همگام‌شده حذف نمی‌شوند؛ ممکن است در سفارش‌های قبلی استفاده شده باشند.
    }
};
