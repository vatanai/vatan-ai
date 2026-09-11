<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مدل‌های ویدیوییِ بدون capability شناخته‌شده نباید در مسیر عمومی ساخت
     * باقی بمانند؛ چون رابط نمی‌تواند گزینه‌های ناسازگار آن‌ها را فیلتر کند.
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

        $known = DB::table('ai_models')
            ->where('is_active', true)
            ->where('provider', 'openrouter')
            ->where('output_modality', 'video')
            ->whereNotNull('capability_config')
            ->whereNotNull('openrouter_model_id')
            ->where('openrouter_model_id', '<>', '')
            ->pluck('openrouter_model_id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values();
        if ($known->isEmpty()) {
            return;
        }

        $preferred = collect([
            'x-ai/grok-imagine-video',
            'alibaba/wan-3.0-prime',
            'alibaba/wan-3.0',
            'bytedance/seedance-2.5',
            'bytedance/seedance-2.0-mini',
            'runway/gen-4.5',
            'google/veo-3.1',
        ])->filter(fn (string $id): bool => $known->contains($id));
        $pool = $preferred->isEmpty() ? $known : $preferred;

        DB::table('products')
            ->where('output_type', 'video')
            ->orderBy('id')
            ->get(['id', 'primary_model', 'ai_provider'])
            ->each(function (object $product) use ($known, $pool): void {
                $current = (string) ($product->primary_model ?? '');
                $primary = $product->ai_provider === 'openrouter' && $known->contains($current)
                    ? $current
                    : (string) $pool->first();
                $fallbacks = $pool->reject(fn (string $id): bool => $id === $primary)->take(3)->values();

                DB::table('products')->where('id', $product->id)->update([
                    'primary_model' => $primary,
                    'ai_provider' => 'openrouter',
                    'fallback_models' => json_encode($fallbacks->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'fallback_model_providers' => json_encode($fallbacks->map(fn (): string => 'openrouter')->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // مسیر عمومی ویدیو نباید به مدل‌های بدون capability بازگردد.
    }
};
