<?php

namespace App\Services;

use App\Models\ModelQualityPreset;
use App\Models\Product;

/** همگام‌سازی پیش‌فرض مدل با محصولات متصل به آن، در تراکنش فراخواننده. */
class ModelQualityPresetSync
{
    public function synchronize(ModelQualityPreset $preset): void
    {
        Product::query()
            ->where('model_configuration->quality_preset_key', $preset->preset_key)
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($preset): void {
                foreach ($products as $product) {
                    $this->applyToProduct($product, $preset);
                }
            });
    }

    public function applyToProduct(Product $product, ModelQualityPreset $preset): void
    {
        $configuration = (array) ($product->model_configuration ?? []);
        $presetConfiguration = (array) ($preset->configuration ?? []);

        foreach (['quality_models', 'free_quality_models'] as $group) {
            $configuration[$group] = (array) ($presetConfiguration[$group] ?? []);
        }
        if (array_key_exists('image_retry_policy', $presetConfiguration)) {
            $configuration['image_retry_policy'] = (array) $presetConfiguration['image_retry_policy'];
        }
        if (array_key_exists('quality_architecture_enabled', $presetConfiguration)) {
            $configuration['quality_architecture_enabled'] = (bool) $presetConfiguration['quality_architecture_enabled'];
        }
        $configuration['quality_preset_key'] = $preset->preset_key;
        $product->model_configuration = $configuration;

        $best = (array) data_get($configuration, 'quality_models.best', []);
        $primary = (array) ($best['primary'] ?? []);
        $fallback = (array) ($best['fallback'] ?? []);
        if (filled($primary['model_id'] ?? null) && filled($primary['provider'] ?? null)) {
            $product->primary_model = $primary['model_id'];
            $product->ai_provider = $primary['provider'];
            $product->fallback_models = filled($fallback['model_id'] ?? null) ? [$fallback['model_id']] : [];
            $product->fallback_model_providers = filled($fallback['provider'] ?? null) ? [$fallback['provider']] : [];
        }

        $product->save();
    }
}
