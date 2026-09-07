<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پیش‌فرض چهره‌محور OpenAI روی endpointهای واقعی image-to-image در Fal.ai.
     * مدل text-to-image قدیمیِ openai/gpt-image-2 عمداً دست‌نخورده می‌ماند؛
     * endpoint ویرایش تصویر شناسه‌ی جداگانه دارد و باید تصویر ورودی را
     * در image_urls دریافت کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models') || ! Schema::hasTable('model_quality_presets')) {
            return;
        }

        DB::table('model_quality_presets')
            ->where('preset_key', 'preset_priority_replicate')
            ->update([
                'name' => 'اولویت Replicate چهره',
                'updated_at' => now(),
            ]);

        foreach ($this->catalog() as $data) {
            $model = AiModel::query()
                ->where('provider', $data['provider'])
                ->where('external_model_id', $data['external_model_id'])
                ->first();

            if (! $model) {
                AiModel::create($data);
                continue;
            }

            $model->fill(Arr::except($data, ['provider', 'openrouter_model_id', 'external_model_id']));
            $model->save();
        }

        // نسخه‌ی text-to-image برای جریان چهره مناسب نیست و نباید در انتخاب
        // محصول نمایش داده شود؛ محصولات قدیمی همچنان با همان شناسه کار می‌کنند.
        AiModel::query()
            ->where('provider', 'fal')
            ->where('external_model_id', 'openai/gpt-image-2')
            ->update([
                'featured_in_lab' => false,
                'updated_at' => now(),
            ]);

        DB::table('model_quality_presets')->updateOrInsert(
            ['preset_key' => 'preset_priority_openai_face'],
            [
                'name' => 'اولویت OpenAI چهره',
                'configuration' => json_encode([
                    'quality_models' => [
                        'standard' => $this->pair('fal', 'fal-ai/gpt-image-1.5/edit', 'replicate', 'google/nano-banana-2-lite'),
                        'professional' => $this->pair('fal', 'openai/gpt-image-2/edit', 'replicate', 'google/nano-banana-2'),
                        'best' => $this->pair('fal', 'openai/gpt-image-2/edit', 'replicate', 'google/nano-banana-pro'),
                    ],
                    'free_quality_models' => [
                        'standard' => $this->pair('fal', 'fal-ai/gpt-image-1.5/edit', 'replicate', 'google/nano-banana-2-lite'),
                        'best' => $this->pair('fal', 'openai/gpt-image-2/edit', 'replicate', 'google/nano-banana-pro'),
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_default_for_product_creation' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('model_quality_presets')) {
            DB::table('model_quality_presets')
                ->where('preset_key', 'preset_priority_openai_face')
                ->delete();
        }

        if (Schema::hasTable('ai_models')) {
            AiModel::query()
                ->where('provider', 'fal')
                ->whereIn('external_model_id', ['fal-ai/gpt-image-1.5/edit', 'openai/gpt-image-2/edit'])
                ->delete();

            AiModel::query()
                ->where('provider', 'fal')
                ->where('external_model_id', 'openai/gpt-image-2')
                ->update(['featured_in_lab' => true, 'updated_at' => now()]);
        }
    }

    private function catalog(): array
    {
        $base = [
            'provider' => 'fal',
            'provider_name' => 'Fal.ai',
            'output_modality' => 'image',
            'task_type' => 'image_to_image',
            'supports_image_input' => true,
            'supports_face_identity' => true,
            'supports_multiple_faces' => true,
            'supports_audio' => false,
            'supports_video_input' => false,
            'default_width' => 1024,
            'default_height' => 1024,
            'supports_webhook' => true,
            'is_active' => true,
            'commercial_use' => true,
            'pricing_type' => 'per_generation',
            'lab_status' => 'active',
            'featured_in_lab' => true,
            'lab_categories' => ['identity', 'popular', 'business', 'vip'],
        ];

        return [
            array_merge($base, [
                'name' => 'GPT Image 1.5 Edit — Fal.ai',
                'openrouter_model_id' => 'fal-ai/gpt-image-1.5/edit',
                'external_model_id' => 'fal-ai/gpt-image-1.5/edit',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.034,
                'lab_priority' => 7,
                'lab_description' => 'مدل OpenAI برای ویرایش عکس ورودی با حفظ ترکیب‌بندی، نور و جزئیات چهره.',
                'pricing_config' => [
                    'source' => 'fal_official',
                    'unit' => 'output_image',
                    'unit_price' => 0.034,
                    'quality_tiers' => ['low' => 0.009, 'medium' => 0.034, 'high' => 0.133],
                    'portrait_2k_tiers' => ['medium' => 0.051, 'high' => 0.200],
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'image_urls', 'image_size', 'quality', 'input_fidelity', 'num_images', 'output_format'],
                    9.4
                ),
                'input_schema' => $this->schema(true),
            ]),
            array_merge($base, [
                'name' => 'GPT Image 2 Edit — Fal.ai',
                'openrouter_model_id' => 'openai/gpt-image-2/edit',
                'external_model_id' => 'openai/gpt-image-2/edit',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.061,
                'lab_priority' => 8,
                'lab_description' => 'بالاترین سطح OpenAI برای ویرایش دقیق عکس و حفظ هویت چهره؛ هزینه‌ی بیشتر.',
                'pricing_config' => [
                    'source' => 'fal_official',
                    'unit' => 'output_image',
                    'unit_price' => 0.061,
                    'quality_tiers' => ['low' => 0.015, 'medium' => 0.061, 'high' => 0.219],
                    'portrait_2k_tiers' => ['medium' => 0.054, 'high' => 0.178],
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'image_urls', 'image_size', 'quality', 'num_images', 'output_format'],
                    9.7
                ),
                'input_schema' => $this->schema(false),
            ]),
        ];
    }

    private function pair(string $provider, string $model, string $fallbackProvider, string $fallbackModel): array
    {
        return [
            'primary' => ['model_id' => $model, 'provider' => $provider],
            'fallback' => ['model_id' => $fallbackModel, 'provider' => $fallbackProvider],
        ];
    }

    private function capabilities(array $allowed, float $quality): array
    {
        return [
            'allowed_inputs' => $allowed,
            'reference_fields' => ['image_urls'],
            'supports_text_to_image' => false,
            'supports_image_to_image' => true,
            'quality_score' => $quality,
            'required_reference_count' => 1,
        ];
    }

    private function schema(bool $withInputFidelity): array
    {
        $properties = [
            'prompt' => ['type' => 'string'],
            'image_urls' => ['type' => 'array', 'items' => ['type' => 'string']],
            'image_size' => ['type' => 'string', 'enum' => ['auto', '1024x1024', '1536x1024', '1024x1536']],
            'quality' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
            'num_images' => ['type' => 'integer'],
            'output_format' => ['type' => 'string', 'enum' => ['jpeg', 'png', 'webp']],
        ];

        if ($withInputFidelity) {
            $properties['input_fidelity'] = ['type' => 'string', 'enum' => ['low', 'high']];
        }

        return ['properties' => $properties];
    }
};
