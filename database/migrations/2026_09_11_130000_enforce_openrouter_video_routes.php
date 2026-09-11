<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تمام محصولات ویدیوییِ فعال را به مدل‌های ویدیویی OpenRouter متصل می‌کند.
     * این migration عمداً idempotent است تا اجرای دوباره، داده را تکثیر نکند.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('ai_models')) {
            return;
        }
        if (!Schema::hasColumn('products', 'output_type')
            || !Schema::hasColumn('products', 'primary_model')
            || !Schema::hasColumn('products', 'ai_provider')
            || !Schema::hasColumn('products', 'fallback_models')
            || !Schema::hasColumn('products', 'fallback_model_providers')) {
            return;
        }

        try {
            $preferred = [
            'x-ai/grok-imagine-video',
            'alibaba/wan-3.0-prime',
            'alibaba/wan-3.0',
            'bytedance/seedance-2.5',
            'bytedance/seedance-2.0-mini',
            'runway/gen-4.5',
            'google/veo-3.1',
        ];
        $models = DB::table('ai_models')
            ->where('is_active', true)
            ->where('provider', 'openrouter')
            ->where('output_modality', 'video')
            ->whereIn('task_type', ['text_to_video', 'image_to_video', 'video_to_video', 'face_animation'])
            ->whereNotNull('openrouter_model_id')
            ->where('openrouter_model_id', '<>', '')
            ->get(['openrouter_model_id']);

        if ($models->isEmpty()) {
            return;
        }

        $available = $models->pluck('openrouter_model_id')->map(fn ($id): string => (string) $id)->unique()->values();
        $fallbackPool = collect($preferred)->filter(fn (string $id): bool => $available->contains($id));
        if ($fallbackPool->isEmpty()) {
            $fallbackPool = $available;
        }

            DB::table('products')
            ->where('output_type', 'video')
            ->orderBy('id')
            ->get(['id', 'primary_model', 'ai_provider'])
            ->each(function (object $product) use ($available, $fallbackPool): void {
                $currentId = (string) ($product->primary_model ?? '');
                $primaryId = $product->ai_provider === 'openrouter' && $available->contains($currentId)
                    ? $currentId
                    : (string) $fallbackPool->first();
                if ($primaryId === '') {
                    return;
                }

                $routes = $fallbackPool
                    ->reject(fn (string $id): bool => $id === $primaryId)
                    ->take(3)
                    ->values();
                DB::table('products')->where('id', $product->id)->update([
                    'primary_model' => $primaryId,
                    'ai_provider' => 'openrouter',
                    'fallback_models' => json_encode($routes->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fallback_model_providers' => json_encode($routes->map(fn (): string => 'openrouter')->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $exception) {
            // اجرای اصلی به‌صورت تک‌پروایدر در سرویس enforce شده است؛
            // اختلاف schema در دیتابیس قدیمی نباید کل سرویس را از کار بیندازد.
            Log::warning('OpenRouter video route normalization skipped', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function down(): void
    {
        // مسیر قبلی عمداً بازسازی نمی‌شود تا محصولات ویدیویی دوباره به provider
        // خارج از OpenRouter برنگردند.
    }
};
