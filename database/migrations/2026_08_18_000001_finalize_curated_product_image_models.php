<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ نهایی و کوچک جریان اصلی وطن:
     * تصویر مرجع + پرامپت محصول -> تصویر.
     *
     * این migration کاتالوگ خام providerها را حذف نمی‌کند؛ فقط مدل‌هایی را
     * که در فرم ثبت محصول و آزمایشگاه قابل انتخاب‌اند، به مجموعه‌ی تأییدشده
     * و قابل قیمت‌گذاری محدود می‌کند. بنابراین محصولات قدیمی همچنان کار می‌کنند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        AiModel::query()->where('output_modality', 'image')->update(['featured_in_lab' => false]);

        foreach ($this->catalog() as $data) {
            $model = AiModel::query()
                ->where('provider', $data['provider'])
                ->where('external_model_id', $data['external_model_id'])
                ->first();

            if (! $model) {
                AiModel::create($data);
                continue;
            }

            // شناسه و نام ثبت‌شده‌ی مدیر را بی‌دلیل بازنویسی نمی‌کنیم؛
            // اما متادیتای فنی، قابلیت و قیمت curated باید قابل اصلاح باشد.
            $model->fill(Arr::except($data, [
                'provider', 'openrouter_model_id', 'external_model_id', 'is_active',
            ]));
            $model->save();
        }
    }

    public function down(): void
    {
        // عمداً داده‌ها یا مدل‌های قدیمی حذف نمی‌شوند.
    }

    private function catalog(): array
    {
        $base = [
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

        return [
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Nano Banana 2 Lite — Replicate',
                'openrouter_model_id' => 'google/nano-banana-2-lite', 'external_model_id' => 'google/nano-banana-2-lite',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.039,
                'lab_priority' => 1, 'lab_categories' => ['economic', 'popular', 'identity'],
                'lab_description' => 'ارزان‌ترین گزینه‌ی curated برای تست حجم بالا؛ ۱K و مناسب خروجی روزمره.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.039, 'unit' => 'output_image', 'resolution_tiers' => ['1K' => 0.039]],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'aspect_ratio', 'output_format'], ['image_input'], 8.2),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Nano Banana 2 — Replicate',
                'openrouter_model_id' => 'google/nano-banana-2', 'external_model_id' => 'google/nano-banana-2',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.08,
                'lab_priority' => 2, 'lab_categories' => ['popular', 'identity', 'economic'],
                'lab_description' => 'انتخاب متعادل برای سرعت، کیفیت و حفظ هویت؛ گزینه‌ی پیشنهادی MVP.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.08, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format'], ['image_input'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Nano Banana Pro — Replicate',
                'openrouter_model_id' => 'google/nano-banana-pro', 'external_model_id' => 'google/nano-banana-pro',
                'task_type' => 'image_to_image', 'cost_per_generation' => 2, 'cost_per_generation_usd' => 0.15,
                'lab_priority' => 3, 'lab_categories' => ['popular', 'identity', 'vip'],
                'lab_description' => 'بالاترین کیفیت Google برای تست‌های حساس؛ هزینه‌ی premium.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.15, 'unit' => 'output_image', 'resolution_tiers' => ['1K' => 0.15, '2K' => 0.15, '4K' => 0.30]],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format'], ['image_input'], 9.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'Seedream 5 Pro — Replicate',
                'openrouter_model_id' => 'bytedance/seedream-5-pro', 'external_model_id' => 'bytedance/seedream-5-pro',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.045,
                'lab_priority' => 4, 'lab_categories' => ['popular', 'business', 'identity'],
                'lab_description' => 'کیفیت بالا با ۱K/۲K و تا ۱۰ تصویر مرجع؛ گزینه‌ی اقتصادی‌تر از مدل‌های premium.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.045, 'unit' => 'output_image', 'resolution_tiers' => ['1K' => 0.045, '2K' => 0.09]],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format'], ['image_input'], 9.2),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'GPT Image 2 — Replicate',
                'openrouter_model_id' => 'openai/gpt-image-2', 'external_model_id' => 'openai/gpt-image-2',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.128,
                'lab_priority' => 5, 'lab_categories' => ['popular', 'business', 'vip'],
                'lab_description' => 'پیروی بسیار دقیق از پرامپت، متن داخل تصویر و ادیت با fidelity بالا.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.128, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'input_images', 'aspect_ratio', 'quality', 'output_format'], ['input_images'], 9.5),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_images' => 'array', 'aspect_ratio' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'replicate', 'provider_name' => 'Replicate',
                'name' => 'FLUX Kontext Pro — Replicate',
                'openrouter_model_id' => 'black-forest-labs/flux-kontext-pro', 'external_model_id' => 'black-forest-labs/flux-kontext-pro',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.04,
                'lab_priority' => 6, 'lab_categories' => ['business', 'popular'],
                'lab_description' => 'ادیت متنی سریع و تجاری با حفظ ترکیب‌بندی و کاراکتر.',
                'pricing_config' => ['source' => 'replicate_official', 'unit_price' => 0.04, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'input_image', 'aspect_ratio', 'output_format'], ['input_image'], 8.8),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_image' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),

            $this->model($base, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana/edit', 'external_model_id' => 'fal-ai/nano-banana/edit',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.039,
                'lab_priority' => 1, 'lab_categories' => ['economic', 'popular', 'identity'],
                'lab_description' => 'نسخه‌ی سریع و اقتصادی Fal برای ادیت عکس مرجع.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.039, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'aspect_ratio', 'output_format'], ['image_urls'], 8.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'FLUX Kontext Pro — Fal.ai',
                'openrouter_model_id' => 'fal-ai/flux-pro/kontext', 'external_model_id' => 'fal-ai/flux-pro/kontext',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.04,
                'lab_priority' => 2, 'lab_categories' => ['business', 'popular'],
                'lab_description' => 'ادیت تجاری و کنترل‌شده؛ هزینه‌ی پایین و مناسب شروع MVP.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.04, 'unit' => 'output_image'],
                'capability_config' => $this->capabilities(['prompt', 'image_url', 'aspect_ratio', 'output_format'], ['image_url'], 8.8),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_url' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana 2 — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana-2', 'external_model_id' => 'fal-ai/nano-banana-2',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.08,
                'lab_priority' => 3, 'lab_categories' => ['popular', 'identity'],
                'lab_description' => 'مدل سریع نسل جدید Google برای حجم و کیفیت متعادل.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.08, 'unit' => 'output_image', 'resolution_tiers' => ['2K' => 0.12, '4K' => 0.16]],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'resolution', 'aspect_ratio', 'output_format'], ['image_urls'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana Pro — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana-pro/edit', 'external_model_id' => 'fal-ai/nano-banana-pro/edit',
                'task_type' => 'image_to_image', 'cost_per_generation' => 2, 'cost_per_generation_usd' => 0.15,
                'lab_priority' => 4, 'lab_categories' => ['popular', 'identity', 'vip'],
                'lab_description' => 'مدل premium برای بهترین شباهت، ادیت‌های پیچیده و چند تصویر مرجع.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.15, 'unit' => 'output_image', 'resolution_tiers' => ['4K' => 0.30]],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'resolution', 'aspect_ratio', 'output_format'], ['image_urls'], 9.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'fal', 'provider_name' => 'Fal.ai',
                'name' => 'Qwen Image Edit — Fal.ai',
                'openrouter_model_id' => 'fal-ai/qwen-image-edit', 'external_model_id' => 'fal-ai/qwen-image-edit',
                'task_type' => 'image_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.03,
                'lab_priority' => 5, 'lab_categories' => ['economic', 'business', 'design'],
                'lab_description' => 'اقتصادی‌ترین گزینه برای ادیت محصول و متن داخل تصویر.',
                'pricing_config' => ['source' => 'fal_official', 'unit_price' => 0.03, 'unit' => 'megapixel'],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'image_size', 'output_format'], ['image_urls'], 8.4),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'image_size' => 'string', 'output_format' => 'string']),
            ]),

            $this->model($base, [
                'provider' => 'openrouter', 'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 Mini — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1-mini', 'external_model_id' => 'openai/gpt-image-1-mini',
                'task_type' => 'text_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.011,
                'lab_priority' => 1, 'lab_categories' => ['economic', 'popular'],
                'lab_description' => 'مسیر اقتصادی OpenRouter برای تست prompt-following و مقایسه‌ی هزینه.',
                'pricing_config' => ['source' => 'openrouter_official', 'unit_price' => 0.011, 'unit' => 'output_image_or_usage'],
                'capability_config' => $this->capabilities(['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'], ['input_references'], 8.5),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_references' => 'array', 'aspect_ratio' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'openrouter', 'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1', 'external_model_id' => 'openai/gpt-image-1',
                'task_type' => 'text_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => 0.042,
                'lab_priority' => 2, 'lab_categories' => ['popular', 'business'],
                'lab_description' => 'ادیت دقیق و رندر متن بهتر؛ هزینه‌ی واقعی از usage ثبت می‌شود.',
                'pricing_config' => ['source' => 'openrouter_official', 'unit_price' => 0.042, 'unit' => 'output_image_or_usage'],
                'capability_config' => $this->capabilities(['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'], ['input_references'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_references' => 'array', 'aspect_ratio' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($base, [
                'provider' => 'openrouter', 'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 2 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-2', 'external_model_id' => 'openai/gpt-image-2',
                'task_type' => 'text_to_image', 'cost_per_generation' => 1, 'cost_per_generation_usd' => null,
                'lab_priority' => 3, 'lab_categories' => ['popular', 'vip'],
                'lab_description' => 'کیفیت حرفه‌ای OpenAI؛ حتماً هزینه‌ی واقعی usage.cost را بعد از تست ثبت کنید.',
                'pricing_config' => ['source' => 'openrouter_usage', 'pricing_type' => 'usage_dependent', 'unit' => 'reported_usage'],
                'capability_config' => $this->capabilities(['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'], ['input_references'], 9.5),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_references' => 'array', 'aspect_ratio' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
        ];
    }

    private function model(array $base, array $specific): array
    {
        return array_merge($base, $specific);
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
        foreach ($fields as $name => $type) {
            $properties[$name] = ['type' => $type];
            if ($type === 'array') $properties[$name]['items'] = ['type' => 'string'];
        }
        return ['properties' => $properties];
    }
};
