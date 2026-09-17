<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مسیر پیش‌فرض ساخت عکس را از migration آزمایشی Replicate جدا می‌کند.
     * تنظیمات قدیمی حفظ می‌شوند و مدل Replicate فقط به‌عنوان fallback باقی
     * می‌ماند؛ برای محصولات موجودی که ساختار کیفیت دارند نیز همین repair
     * فقط وقتی اعمال می‌شود که هر دو مسیرشان Replicate باشد.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) return;

        $routes = [
            'standard' => ['openai/gpt-image-1-mini', 'openai/gpt-image-1'],
            'professional' => ['openai/gpt-image-1', 'openai/gpt-image-2'],
            'best' => ['openai/gpt-image-2', 'bytedance-seed/seedream-5-0-pro'],
        ];

        if (Schema::hasTable('model_quality_presets')
            && Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation')) {
            DB::table('model_quality_presets')
                ->where('is_default_for_product_creation', true)
                ->get(['id', 'configuration'])
                ->each(function (object $preset) use ($routes): void {
                    $configuration = $this->repairConfiguration($this->decode($preset->configuration), $routes);
                    DB::table('model_quality_presets')->where('id', $preset->id)->update([
                        'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'model_configuration')) {
            DB::table('products')
                ->where('media_type', '!=', 'video')
                ->whereNotNull('model_configuration')
                ->get(['id', 'model_configuration'])
                ->each(function (object $product) use ($routes): void {
                    $old = $this->decode($product->model_configuration);
                    $new = $this->repairConfiguration($old, $routes);
                    if ($new !== $old) {
                        DB::table('products')->where('id', $product->id)->update([
                            'model_configuration' => json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        if (Schema::hasTable('model_tier_defaults')) {
            foreach ([
                'free' => ['openai/gpt-image-1-mini', 'openai/gpt-image-1'],
                'economy' => ['openai/gpt-image-1-mini', 'openai/gpt-image-1'],
                'pro' => ['openai/gpt-image-1', 'openai/gpt-image-2'],
                'business' => ['openai/gpt-image-2', 'bytedance-seed/seedream-5-0-pro'],
            ] as $tier => [$primary, $fallback]) {
                DB::table('model_tier_defaults')->where('tier_key', $tier)->update([
                    'primary_model_id' => $primary,
                    'primary_provider' => 'openrouter',
                    'fallback_model_id' => $fallback,
                    'fallback_provider' => 'replicate',
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // بازگرداندن مسیر کم‌اعتبار Replicate در rollback امن نیست.
    }

    private function repairConfiguration(array $configuration, array $routes): array
    {
        foreach (['quality_models', 'free_quality_models'] as $group) {
            foreach ((array) data_get($configuration, $group, []) as $quality => $selection) {
                if (! isset($routes[$quality])) continue;
                $primary = (array) data_get($selection, 'primary', []);
                if (($primary['provider'] ?? null) !== 'replicate' || blank($primary['model_id'])) continue;
                if (! $this->activeModelExists($routes[$quality][0], 'openrouter')) continue;

                data_set($configuration, "{$group}.{$quality}.primary", [
                    'model_id' => $routes[$quality][0],
                    'provider' => 'openrouter',
                ]);
                data_set($configuration, "{$group}.{$quality}.fallback", [
                    'model_id' => $primary['model_id'],
                    'provider' => 'replicate',
                ]);
            }
        }

        return $configuration;
    }

    private function activeModelExists(string $modelId, string $provider): bool
    {
        return DB::table('ai_models')
            ->where('provider', $provider)
            ->where('is_active', true)
            ->where('openrouter_model_id', $modelId)
            ->exists();
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) return $value;
        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }
};
