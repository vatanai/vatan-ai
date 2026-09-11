<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مسیر پیش‌فرض محصول فنیِ پشت استودیوی عمومی را با همان اولویت سرویس‌ها
     * هماهنگ می‌کند. خود محصول ویدیویی در رابط استودیو نمایش داده نمی‌شود؛
     * استودیو فقط از تنظیمات آن برای قیمت‌گذاری و اجرای صف استفاده می‌کند.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('ai_models')) {
            return;
        }

        $product = DB::table('products')
            ->where('slug', 'ai-cinematic-short-video')
            ->where('status', 'active')
            ->first();
        if (!$product) {
            return;
        }

        $preferredOpenRouter = [
            'x-ai/grok-imagine-video',
            'alibaba/wan-3.0-prime',
            'alibaba/wan-3.0',
            'bytedance/seedance-2.5',
            'bytedance/seedance-2.0-mini',
        ];
        $models = DB::table('ai_models')
            ->where('is_active', true)
            ->where('output_modality', 'video')
            ->where('task_type', 'image_to_video')
            ->whereIn('provider', ['openrouter', 'replicate', 'fal'])
            ->whereNotNull('openrouter_model_id')
            ->where('openrouter_model_id', '<>', '')
            ->get(['provider', 'openrouter_model_id']);

        $byProviderAndId = $models->keyBy(fn ($model): string => $model->provider . '|' . $model->openrouter_model_id);
        $primaryId = (string) $product->primary_model;
        $primaryProvider = (string) $product->ai_provider;
        $primaryKey = $primaryProvider . '|' . $primaryId;
        if (!$byProviderAndId->has($primaryKey)) {
            foreach ($preferredOpenRouter as $candidateId) {
                if ($byProviderAndId->has('openrouter|' . $candidateId)) {
                    $primaryId = $candidateId;
                    $primaryProvider = 'openrouter';
                    break;
                }
            }
        }

        $routes = [];
        foreach ($preferredOpenRouter as $candidateId) {
            $key = 'openrouter|' . $candidateId;
            if ($candidateId !== $primaryId && $byProviderAndId->has($key)) {
                $routes[] = ['id' => $candidateId, 'provider' => 'openrouter'];
            }
        }
        foreach ($models->where('provider', 'replicate')->sortBy('openrouter_model_id') as $model) {
            $routes[] = ['id' => $model->openrouter_model_id, 'provider' => 'replicate'];
        }
        foreach ($models->where('provider', 'fal')->sortBy('openrouter_model_id')->take(2) as $model) {
            $routes[] = ['id' => $model->openrouter_model_id, 'provider' => 'fal'];
        }

        $routes = collect($routes)
            ->unique(fn (array $route): string => $route['provider'] . '|' . $route['id'])
            ->values();

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'primary_model' => $primaryId,
                'ai_provider' => $primaryProvider,
                'fallback_models' => json_encode($routes->pluck('id')->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fallback_model_providers' => json_encode($routes->pluck('provider')->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // مسیرهای جدید با اجرای migration بعدی دوباره قابل ساخت هستند و حذف
        // fallbackها می‌تواند مسیر اجرای ساخت‌های در صف را مختل کند.
    }
};
