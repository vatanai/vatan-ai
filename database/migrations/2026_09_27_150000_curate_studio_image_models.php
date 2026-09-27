<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * متادیتای دو مدل آزموده‌شده‌ی استودیوی عکس را با endpoint فعلی
     * OpenRouter همگام می‌کند؛ مدل‌های دیگر و اتصال محصولات تغییر نمی‌کنند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        foreach ($this->catalog() as $modelId => $metadata) {
            $model = AiModel::query()
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $modelId)
                ->first();

            if (! $model) {
                throw new RuntimeException("Required Studio image model is missing: {$modelId}");
            }

            $model->forceFill([
                'output_modality' => 'image',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'is_active' => true,
                'cost_per_generation_usd' => $metadata['cost_per_generation_usd'],
                'pricing_type' => $metadata['pricing_type'],
                'pricing_config' => array_merge(
                    (array) ($model->pricing_config ?? []),
                    $metadata['pricing_config'],
                ),
                'capability_config' => array_merge(
                    (array) ($model->capability_config ?? []),
                    $metadata['capability_config'],
                ),
                'last_verified_at' => now(),
            ])->save();
        }
    }

    public function down(): void
    {
        // بازگردانی متادیتای قدیمی و نامطمئن، رفتار امنی برای محیط تولید نیست.
    }

    private function catalog(): array
    {
        return [
            'google/gemini-3.1-flash-lite-image' => [
                'cost_per_generation_usd' => 0.034,
                'pricing_type' => 'usage_dependent',
                'pricing_config' => [
                    'source' => 'openrouter.image.models',
                    'unit_price' => 0.034,
                    'unit' => 'output_image',
                    'price_source' => 'official_endpoint_pricing_and_production_usage',
                    'resolution_tiers' => ['1K' => 0.034],
                ],
                'capability_config' => [
                    'allowed_inputs' => ['prompt', 'resolution', 'aspect_ratio', 'n', 'input_references'],
                    'reference_fields' => ['input_references'],
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'supported_resolutions' => ['1K'],
                    'supported_aspect_ratios' => ['1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', '21:9'],
                    'max_reference_images' => 14,
                    'max_images' => 1,
                ],
            ],
            'sourceful/riverflow-v2.5-fast' => [
                'cost_per_generation_usd' => 0.019,
                'pricing_type' => 'per_generation',
                'pricing_config' => [
                    'source' => 'openrouter.image.models',
                    'unit_price' => 0.019,
                    'unit' => 'output_image',
                    'price_source' => 'official_endpoint_pricing',
                    'resolution_tiers' => ['1K' => 0.019, '2K' => 0.021],
                ],
                'capability_config' => [
                    'allowed_inputs' => ['prompt', 'resolution', 'aspect_ratio', 'output_format', 'n', 'input_references'],
                    'reference_fields' => ['input_references'],
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'supported_resolutions' => ['1K', '2K'],
                    'supported_aspect_ratios' => ['1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', '21:9'],
                    'supported_output_formats' => ['jpeg'],
                    'max_reference_images' => 4,
                    'max_images' => 1,
                ],
            ],
        ];
    }
};
