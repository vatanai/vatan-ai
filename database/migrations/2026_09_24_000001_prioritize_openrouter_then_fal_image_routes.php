<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مسیر پیش‌فرض ساخت تصویر را قطعی می‌کند: ابتدا OpenRouter و بعد Fal.ai.
     * مسیرهای موجودِ محصولات حفظ می‌شوند مگر این‌که provider اصلی آن‌ها غیر از
     * OpenRouter باشد؛ در این حالت همان شناسه مدل، اگر در OpenRouter فعال باشد،
     * نگه داشته می‌شود و در غیر این صورت مدل استاندارد فعال جایگزین می‌شود.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $routes = $this->routes();

        $this->repairDefaultQualityPreset($routes);
        $this->repairTierDefaults($routes);
        $this->repairImageProducts($routes);
    }

    public function down(): void
    {
        // تغییر اولویت provider قابل بازگردانی امن به مقادیر قبلی نیست.
    }

    private function routes(): array
    {
        return [
            'standard' => [
                'primary' => ['model_id' => 'openai/gpt-image-1-mini', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana/edit', 'provider' => 'fal'],
            ],
            'professional' => [
                'primary' => ['model_id' => 'openai/gpt-image-1', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana-2/edit', 'provider' => 'fal'],
            ],
            'best' => [
                'primary' => ['model_id' => 'openai/gpt-image-2', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana-pro/edit', 'provider' => 'fal'],
            ],
        ];
    }

    private function repairDefaultQualityPreset(array $routes): void
    {
        if (! Schema::hasTable('model_quality_presets')
            || ! Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation')) {
            return;
        }

        DB::table('model_quality_presets')
            ->where('is_default_for_product_creation', true)
            ->get(['id', 'configuration'])
            ->each(function (object $preset) use ($routes): void {
                $configuration = $this->decode($preset->configuration);
                $configuration = $this->applyQualityRoutes($configuration, $routes);

                DB::table('model_quality_presets')->where('id', $preset->id)->update([
                    'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
    }

    private function repairTierDefaults(array $routes): void
    {
        if (! Schema::hasTable('model_tier_defaults')) {
            return;
        }

        $tierRoutes = [
            'free' => $routes['standard'],
            'economy' => $routes['standard'],
            'pro' => $routes['professional'],
            'business' => $routes['best'],
        ];

        foreach ($tierRoutes as $tier => $route) {
            if (! $this->activeModelExists($route['primary']['model_id'], 'openrouter')
                || ! $this->activeModelExists($route['fallback']['model_id'], 'fal')) {
                continue;
            }

            DB::table('model_tier_defaults')->where('tier_key', $tier)->update([
                'primary_model_id' => $route['primary']['model_id'],
                'primary_provider' => 'openrouter',
                'fallback_model_id' => $route['fallback']['model_id'],
                'fallback_provider' => 'fal',
                'updated_at' => now(),
            ]);
        }
    }

    private function repairImageProducts(array $routes): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $columns = ['id', 'primary_model', 'ai_provider', 'fallback_models', 'fallback_model_providers'];
        foreach (['model_configuration', 'min_reference_images', 'output_type', 'media_type'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                $columns[] = $column;
            }
        }

        $products = DB::table('products');
        if (Schema::hasColumn('products', 'output_type')) {
            $products->where(function ($query): void {
                $query->where('output_type', '!=', 'video')->orWhereNull('output_type');
            });
        } elseif (Schema::hasColumn('products', 'media_type')) {
            $products->where(function ($query): void {
                $query->where('media_type', '!=', 'video')->orWhereNull('media_type');
            });
        }

        $products->get($columns)->each(function (object $product) use ($routes): void {
            $updates = [];
            $primaryModel = (string) ($product->primary_model ?? '');
            $primaryProvider = (string) ($product->ai_provider ?? '');

            if ($primaryProvider !== 'openrouter') {
                $sameModelIsAvailable = $primaryModel !== ''
                    && $this->activeModelExists($primaryModel, 'openrouter');
                if ($sameModelIsAvailable) {
                    $updates['ai_provider'] = 'openrouter';
                } elseif ($this->activeModelExists($routes['standard']['primary']['model_id'], 'openrouter')) {
                    $updates['primary_model'] = $routes['standard']['primary']['model_id'];
                    $updates['ai_provider'] = 'openrouter';
                    $primaryModel = $routes['standard']['primary']['model_id'];
                }
            }

            [$fallbackModels, $fallbackProviders] = $this->orderedFallbacks($product, $primaryModel, $routes);
            if ($fallbackModels !== (array) ($this->decode($product->fallback_models ?? null))) {
                $updates['fallback_models'] = json_encode($fallbackModels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if ($fallbackProviders !== (array) ($this->decode($product->fallback_model_providers ?? null))) {
                $updates['fallback_model_providers'] = json_encode($fallbackProviders, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (property_exists($product, 'model_configuration') && $product->model_configuration !== null) {
                $configuration = $this->decode($product->model_configuration);
                $repaired = $this->applyQualityRoutes($configuration, $routes);
                $repaired = $this->applyTierRoutes($repaired, $routes);
                if ($repaired !== $configuration) {
                    $updates['model_configuration'] = json_encode($repaired, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('products')->where('id', $product->id)->update($updates);
            }
        });
    }

    private function orderedFallbacks(object $product, string $primaryModel, array $routes): array
    {
        $models = $this->decode($product->fallback_models ?? null);
        $providers = $this->decode($product->fallback_model_providers ?? null);
        $candidates = [];

        foreach (array_values($models) as $index => $modelId) {
            $modelId = trim((string) $modelId);
            if ($modelId === '' || $modelId === $primaryModel) {
                continue;
            }

            $provider = trim((string) ($providers[$index] ?? ''));
            if (! in_array($provider, ['openrouter', 'fal', 'replicate'], true)) {
                $provider = $this->providerForActiveModel($modelId) ?: 'openrouter';
            }
            $candidates[$modelId . '|' . $provider] = ['model_id' => $modelId, 'provider' => $provider];
        }

        $hasFal = collect($candidates)->contains(fn (array $candidate): bool => $candidate['provider'] === 'fal');
        if (! $hasFal) {
            $fallback = ((int) ($product->min_reference_images ?? 0) > 0)
                ? $routes['standard']['fallback']
                : ['model_id' => 'fal-ai/nano-banana', 'provider' => 'fal'];
            if ($this->activeModelExists($fallback['model_id'], 'fal')) {
                $candidates[$fallback['model_id'] . '|fal'] = $fallback;
            }
        }

        $priority = ['openrouter' => 0, 'fal' => 1, 'replicate' => 2];
        uasort($candidates, fn (array $left, array $right): int => ($priority[$left['provider']] ?? 3) <=> ($priority[$right['provider']] ?? 3));

        return [
            array_values(array_column($candidates, 'model_id')),
            array_values(array_column($candidates, 'provider')),
        ];
    }

    private function applyQualityRoutes(array $configuration, array $routes): array
    {
        foreach (['quality_models', 'free_quality_models'] as $group) {
            if (! array_key_exists($group, $configuration)) {
                continue;
            }
            foreach ((array) $configuration[$group] as $quality => $selection) {
                if (isset($routes[$quality])
                    && $this->activeModelExists($routes[$quality]['primary']['model_id'], 'openrouter')
                    && $this->activeModelExists($routes[$quality]['fallback']['model_id'], 'fal')) {
                    $configuration[$group][$quality] = $routes[$quality];
                }
            }
        }

        return $configuration;
    }

    private function applyTierRoutes(array $configuration, array $routes): array
    {
        if (! isset($configuration['tiers']) || ! is_array($configuration['tiers'])) {
            return $configuration;
        }

        $tierRoutes = [
            'free' => $routes['standard'],
            'economy' => $routes['standard'],
            'pro' => $routes['professional'],
            'business' => $routes['best'],
        ];
        foreach ($tierRoutes as $tier => $route) {
            if (array_key_exists($tier, $configuration['tiers'])
                && $this->activeModelExists($route['primary']['model_id'], 'openrouter')
                && $this->activeModelExists($route['fallback']['model_id'], 'fal')) {
                $configuration['tiers'][$tier] = $route;
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

    private function providerForActiveModel(string $modelId): ?string
    {
        return DB::table('ai_models')
            ->where('is_active', true)
            ->where('openrouter_model_id', $modelId)
            ->whereIn('provider', ['openrouter', 'fal', 'replicate'])
            ->orderByRaw("CASE provider WHEN 'openrouter' THEN 0 WHEN 'fal' THEN 1 WHEN 'replicate' THEN 2 ELSE 3 END")
            ->value('provider');
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return is_string($value) ? (json_decode($value, true) ?: []) : [];
    }
};
