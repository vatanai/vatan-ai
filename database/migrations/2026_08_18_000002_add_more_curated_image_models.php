<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پنج گزینه‌ی تکمیلی برای مقایسه‌ی واقعی کیفیت/هزینه در جریان
     * «عکس مرجع + پرامپت محصول → عکس».
     *
     * این migration کاتالوگ خام providerها را دست‌کاری نمی‌کند؛ فقط مدل‌های
     * مناسب این محصول را در انتخاب مدیر و آزمایشگاه فعال می‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        foreach ($this->catalog() as $data) {
            $model = AiModel::query()
                ->where('provider', $data['provider'])
                ->where(function ($query) use ($data): void {
                    $query->where('external_model_id', $data['external_model_id'])
                        ->orWhere('openrouter_model_id', $data['openrouter_model_id']);
                })
                ->first();

            if (! $model) {
                AiModel::create($data);
                continue;
            }

            $model->fill(Arr::except($data, [
                'provider', 'openrouter_model_id', 'external_model_id', 'name', 'is_active',
            ]));
            $model->save();
        }
    }

    public function down(): void
    {
        // مدل‌ها و گزارش‌های مرتبط عمداً حذف نمی‌شوند.
    }

    private function catalog(): array
    {
        $common = [
            'output_modality' => 'image',
            'supports_image_input' => true,
            'supports_face_identity' => false,
            'supports_multiple_faces' => false,
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
        ];

        $aspectRatios = [
            '1:1', '2:3', '3:2', '3:4', '4:3', '4:5', '5:4', '9:16', '16:9', 'match_input_image',
        ];

        return [
            $this->model($common, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Nano Banana — Replicate',
                'openrouter_model_id' => 'google/nano-banana', 'external_model_id' => 'google/nano-banana',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.039,
                'lab_priority' => 7, 'lab_categories' => ['popular', 'identity', 'economic'],
                'lab_description' => 'مدل رسمی و اقتصادی Google برای ادیت عکس مرجع و حفظ سوژه.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.039, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'aspect_ratio', 'output_format'], ['image_input'], 8.9),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'aspect_ratio' => ['type' => 'string', 'enum' => $aspectRatios], 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'FLUX Kontext Dev — Replicate',
                'openrouter_model_id' => 'black-forest-labs/flux-kontext-dev', 'external_model_id' => 'black-forest-labs/flux-kontext-dev',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.025,
                'lab_priority' => 8, 'lab_categories' => ['economic', 'business', 'popular'],
                'lab_description' => 'گزینه‌ی بسیار اقتصادی برای ادیت سریع، تغییر پس‌زمینه و حفظ ترکیب‌بندی.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.025, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'input_image', 'aspect_ratio', 'output_format'], ['input_image'], 8.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_image' => 'string', 'aspect_ratio' => ['type' => 'string', 'enum' => $aspectRatios], 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Seedream 4.5 — Replicate',
                'openrouter_model_id' => 'bytedance/seedream-4.5', 'external_model_id' => 'bytedance/seedream-4.5',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.04,
                'lab_priority' => 9, 'lab_categories' => ['popular', 'business', 'identity'],
                'lab_description' => 'کیفیت تولیدی بالا با درک فضایی و ادیت چندتصویری؛ خروجی پایه‌ی آن 2K است.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.04, 'unit' => 'output_image', 'resolution_tiers' => ['2K' => 0.04, '4K' => 0.08]],
                'default_parameters' => ['size' => '2K', 'sequential_image_generation' => 'disabled'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'size', 'aspect_ratio', 'output_format'], ['image_input'], 9.1) + ['field_map' => ['resolution' => 'size']],
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'size' => ['type' => 'string', 'enum' => ['2K', '4K', 'custom']], 'aspect_ratio' => ['type' => 'string', 'enum' => $aspectRatios], 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'FLUX Kontext Dev — Fal.ai',
                'openrouter_model_id' => 'fal-ai/flux-kontext/dev', 'external_model_id' => 'fal-ai/flux-kontext/dev',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.025,
                'lab_priority' => 6, 'lab_categories' => ['economic', 'business', 'popular'],
                'lab_description' => 'نسخه‌ی سریع و ارزان Kontext برای تست حجم بالا و تغییرات محصول.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.025, 'unit' => 'megapixel'],
                'capability_config' => $this->capabilities(['prompt', 'image_url', 'output_format'], ['image_url'], 8.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_url' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'FLUX Image-to-Image Dev — Fal.ai',
                'openrouter_model_id' => 'fal-ai/flux/dev/image-to-image', 'external_model_id' => 'fal-ai/flux/dev/image-to-image',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.03,
                'lab_priority' => 10, 'lab_categories' => ['economic', 'business'],
                'lab_description' => 'گزینه‌ی پایه و کم‌هزینه برای مقایسه‌ی ادیت و انتقال سبک روی عکس محصول.',
                'pricing_type' => 'per_megapixel',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.03, 'unit' => 'megapixel'],
                'default_parameters' => ['strength' => 0.8],
                'capability_config' => $this->capabilities(['prompt', 'image_url', 'strength', 'output_format'], ['image_url'], 8.2),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_url' => 'string', 'strength' => ['type' => 'number', 'default' => 0.8], 'output_format' => 'string']),
            ]),
        ];
    }

    private function model(array $common, array $specific): array
    {
        return array_merge($common, $specific);
    }

    private function capabilities(array $allowed, array $references, float $quality): array
    {
        return [
            'allowed_inputs' => $allowed,
            'reference_fields' => $references,
            'supports_text_to_image' => true,
            'supports_image_to_image' => true,
            'quality_score' => $quality,
        ];
    }

    private function schema(array $fields): array
    {
        $properties = [];
        foreach ($fields as $name => $definition) {
            if (is_array($definition)) {
                $properties[$name] = $definition;
                continue;
            }
            $properties[$name] = ['type' => $definition];
            if ($definition === 'array') {
                $properties[$name]['items'] = ['type' => 'string'];
            }
        }
        return ['properties' => $properties];
    }
};
