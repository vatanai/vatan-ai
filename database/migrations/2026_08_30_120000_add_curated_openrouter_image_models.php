<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ کوچک و کاربردی OpenRouter برای جریان وطن:
     * عکس مرجع + پرامپت → عکس خروجی.
     *
     * شناسه‌ها از Image Models API رسمی OpenRouter انتخاب شده‌اند. این
     * migration فقط مدل‌های تصویری مناسب محصول را به آزمایشگاه و فرم ثبت
     * محصول اضافه می‌کند و به مدل‌های Fal.ai/Replicate دست نمی‌زند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        foreach ($this->catalog() as $data) {
            $model = AiModel::query()
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $data['openrouter_model_id'])
                ->first();

            if (! $model) {
                AiModel::create($data);
                continue;
            }

            // تنظیمات دستی مدیر مثل فعال/غیرفعال‌بودن و نام سفارشی حفظ می‌شود؛
            // متادیتای فنی و وضعیت نمایش کاتالوگ به‌روز می‌گردد.
            $updates = Arr::except($data, [
                'provider', 'openrouter_model_id', 'external_model_id', 'name', 'is_active',
            ]);
            $updates['capability_config'] = array_replace_recursive(
                (array) ($model->capability_config ?? []),
                (array) ($data['capability_config'] ?? [])
            );
            $updates['pricing_config'] = array_replace_recursive(
                (array) ($model->pricing_config ?? []),
                (array) ($data['pricing_config'] ?? [])
            );
            $model->forceFill($updates)->save();
        }
    }

    public function down(): void
    {
        // مدل‌ها عمداً حذف نمی‌شوند تا محصولی که از آن‌ها استفاده می‌کند
        // بعد از rollback بدون شناسه‌ی مدل باقی نماند.
    }

    private function catalog(): array
    {
        $common = [
            'provider' => 'openrouter',
            'provider_name' => 'OpenRouter',
            'output_modality' => 'image',
            'task_type' => 'text_to_image',
            'supports_image_input' => true,
            'supports_face_identity' => false,
            'supports_multiple_faces' => false,
            'supports_audio' => false,
            'supports_video_input' => false,
            'default_width' => 1024,
            'default_height' => 1024,
            'supports_webhook' => false,
            'is_active' => true,
            'commercial_use' => true,
            'lab_status' => 'active',
            'featured_in_lab' => true,
        ];

        return [
            $this->model($common, [
                'name' => 'FLUX 2 Klein 4B — OpenRouter',
                'openrouter_model_id' => 'black-forest-labs/flux.2-klein-4b',
                'external_model_id' => 'black-forest-labs/flux.2-klein-4b',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.014,
                'pricing_type' => 'per_megapixel',
                'lab_priority' => 101,
                'lab_categories' => ['economic', 'popular', 'business'],
                'lab_description' => 'گزینه‌ی بسیار اقتصادی برای ساخت و ادیت سریع با عکس مرجع.',
                'pricing_config' => [
                    'source' => 'openrouter_image_models_api',
                    'unit_price' => 0.014,
                    'unit' => 'megapixel',
                    'price_source' => 'official_endpoint_pricing',
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'aspect_ratio', 'output_format', 'n', 'input_references'],
                    ['input_references'],
                    8.4
                ),
                'input_schema' => $this->schema(['prompt', 'aspect_ratio', 'output_format', 'n', 'input_references']),
            ]),
            $this->model($common, [
                'name' => 'Riverflow V2.5 Fast — OpenRouter',
                'openrouter_model_id' => 'sourceful/riverflow-v2.5-fast',
                'external_model_id' => 'sourceful/riverflow-v2.5-fast',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.019,
                'pricing_type' => 'per_generation',
                'lab_priority' => 102,
                'lab_categories' => ['economic', 'popular', 'creative'],
                'lab_description' => 'مدل سریع و کم‌هزینه برای تولید روزمره و تست حجم بالا.',
                'pricing_config' => [
                    'source' => 'openrouter_image_models_api',
                    'unit_price' => 0.019,
                    'resolution_tiers' => ['1K' => 0.019, '2K' => 0.021],
                    'unit' => 'output_image',
                    'price_source' => 'official_endpoint_pricing',
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'resolution', 'aspect_ratio', 'output_format', 'n', 'input_references'],
                    ['input_references'],
                    8.6
                ),
                'input_schema' => $this->schema(['prompt', 'resolution', 'aspect_ratio', 'output_format', 'n', 'input_references']),
            ]),
            $this->model($common, [
                'name' => 'Qwen Image 3 — OpenRouter',
                'openrouter_model_id' => 'qwen/qwen-image-3',
                'external_model_id' => 'qwen/qwen-image-3',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.033,
                'pricing_type' => 'per_generation',
                'lab_priority' => 103,
                'lab_categories' => ['popular', 'business', 'design'],
                'lab_description' => 'تعادل مناسب بین کیفیت، متن داخل تصویر و هزینه؛ با پشتیبانی از عکس مرجع.',
                'pricing_config' => [
                    'source' => 'openrouter_image_models_api',
                    'unit_price' => 0.03,
                    'input_reference_price' => 0.003,
                    'resolution_tiers' => ['1K' => 0.03, '2K' => 0.03],
                    'unit' => 'output_image',
                    'price_source' => 'official_endpoint_pricing',
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'resolution', 'aspect_ratio', 'n', 'input_references'],
                    ['input_references'],
                    8.8
                ),
                'input_schema' => $this->schema(['prompt', 'resolution', 'aspect_ratio', 'n', 'input_references']),
            ]),
            $this->model($common, [
                'name' => 'Seedream 5 Lite — OpenRouter',
                'openrouter_model_id' => 'bytedance-seed/seedream-5-0-lite',
                'external_model_id' => 'bytedance-seed/seedream-5-0-lite',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.035,
                'pricing_type' => 'per_generation',
                'lab_priority' => 104,
                'lab_categories' => ['popular', 'identity', 'business'],
                'lab_description' => 'مدل اقتصادی با کیفیت بالا برای محصول، چهره و انتقال نور و ترکیب‌بندی.',
                'pricing_config' => [
                    'source' => 'openrouter_image_models_api',
                    'unit_price' => 0.035,
                    'unit' => 'output_image',
                    'price_source' => 'official_endpoint_pricing',
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'resolution', 'aspect_ratio', 'n', 'input_references', 'seed'],
                    ['input_references'],
                    9.0
                ),
                'input_schema' => $this->schema(['prompt', 'resolution', 'aspect_ratio', 'n', 'input_references', 'seed']),
            ]),
            $this->model($common, [
                'name' => 'Gemini 2.5 Flash Image — OpenRouter',
                'openrouter_model_id' => 'google/gemini-2.5-flash-image',
                'external_model_id' => 'google/gemini-2.5-flash-image',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => null,
                'pricing_type' => 'usage_dependent',
                'lab_priority' => 105,
                'lab_categories' => ['identity', 'popular', 'business'],
                'lab_description' => 'مدل تصویری چندوجهی برای ساخت و ویرایش با عکس مرجع؛ هزینه طبق مصرف واقعی ثبت می‌شود.',
                'pricing_config' => [
                    'source' => 'openrouter_image_models_api',
                    'pricing_type' => 'usage_dependent',
                    'input_image_token_price' => 0.0000003,
                    'output_image_token_price' => 0.00003,
                    'unit' => 'token',
                    'price_source' => 'official_endpoint_pricing',
                ],
                'capability_config' => $this->capabilities(
                    ['prompt', 'aspect_ratio', 'n', 'input_references'],
                    ['input_references'],
                    9.1
                ),
                'input_schema' => $this->schema(['prompt', 'aspect_ratio', 'n', 'input_references']),
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
        foreach ($fields as $field) {
            $properties[$field] = match ($field) {
                'n' => ['type' => 'integer'],
                'input_references' => ['type' => 'array', 'items' => ['type' => 'object']],
                default => ['type' => 'string'],
            };
        }

        return ['properties' => $properties];
    }
};
