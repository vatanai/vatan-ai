<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ کوچک و قابل‌اعتماد جریان اصلی وطن: پرامپت + تصویر مرجع → تصویر.
     *
     * کاتالوگ خام providerها عمداً در اینجا نمایش داده نمی‌شود؛ فقط مدل‌هایی
     * وارد انتخاب محصول و آزمایشگاه می‌شوند که ورودی تصویر را واقعاً می‌پذیرند.
     * اجرای دوباره این migration مدل تکراری نمی‌سازد و نسخه‌های قبلی را حفظ می‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        // ابتدا کاتالوگ خام قبلی از انتخاب مدیر خارج می‌شود؛ ردیف‌ها حذف نمی‌شوند
        // تا محصولات قدیمی و گزارش‌های آزمایش همچنان به همان مدل‌ها وصل بمانند.
        AiModel::query()
            ->where('output_modality', 'image')
            ->update(['featured_in_lab' => false]);

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

            // نام و وضعیت فعال مدیر بازنویسی نمی‌شود؛ متادیتای فنی و انتخابی به‌روز می‌ماند.
            $model->fill(Arr::except($data, [
                'provider', 'openrouter_model_id', 'external_model_id', 'name', 'is_active',
            ]));
            $model->save();
        }
    }

    public function down(): void
    {
        // داده‌های موجود عمداً حذف نمی‌شوند؛ این migration روی کاتالوگ جاری اعمال می‌شود.
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
            'commercial_use' => null,
            'pricing_type' => 'per_generation',
            'lab_status' => 'active',
        ];

        return [
            // Replicate: مسیر اصلی فعلی و قابل تست در کلودیوا
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'Nano Banana — Replicate',
                'openrouter_model_id' => 'google/nano-banana',
                'external_model_id' => 'google/nano-banana',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 4,
                'cost_per_generation_usd' => 0.039,
                'lab_priority' => 3,
                'lab_categories' => ['popular', 'identity'],
                'featured_in_lab' => true,
                'lab_description' => 'انتخاب متعادل برای شباهت چهره، سرعت و هزینه؛ مناسب پیش‌فرض MVP.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.039, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'aspect_ratio', 'output_format'], ['image_input'], 8.8),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'Nano Banana 2 — Replicate',
                'openrouter_model_id' => 'google/nano-banana-2',
                'external_model_id' => 'google/nano-banana-2',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 4,
                'cost_per_generation_usd' => 0.04,
                'lab_priority' => 2,
                'lab_categories' => ['popular', 'identity', 'economic'],
                'featured_in_lab' => true,
                'lab_description' => 'نسخه سریع‌تر و جدیدتر برای استفاده روزمره؛ تعادل کیفیت و هزینه.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.04, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format'], ['image_input'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'Nano Banana 2 Lite — Replicate',
                'openrouter_model_id' => 'google/nano-banana-2-lite',
                'external_model_id' => 'google/nano-banana-2-lite',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 3,
                'cost_per_generation_usd' => 0.034,
                'lab_priority' => 5,
                'lab_categories' => ['popular', 'economic'],
                'featured_in_lab' => true,
                'lab_description' => 'اقتصادی‌ترین گزینه منتخب برای تست حجم بالا؛ مناسب گرید اقتصادی.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.034, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format'], ['image_input'], 8.2),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'Nano Banana Pro — Replicate',
                'openrouter_model_id' => 'google/nano-banana-pro',
                'external_model_id' => 'google/nano-banana-pro',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 15,
                'cost_per_generation_usd' => 0.15,
                'lab_priority' => 1,
                'lab_categories' => ['popular', 'identity', 'vip'],
                'featured_in_lab' => true,
                'lab_description' => 'بالاترین کیفیت منتخب و مناسب خروجی‌های حساس؛ هزینه آن عمداً بالاتر است.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.15, 'unit' => 'output_image', 'price_source' => 'official_model_page', 'tiers' => ['1K' => 0.15, '2K' => 0.15, '4K' => 0.30]],
                'capability_config' => $this->capabilities(['prompt', 'image_input', 'resolution', 'aspect_ratio', 'output_format', 'safety_filter_level'], ['image_input'], 9.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_input' => 'array', 'resolution' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string', 'safety_filter_level' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'FLUX Kontext Pro — Replicate',
                'openrouter_model_id' => 'black-forest-labs/flux-kontext-pro',
                'external_model_id' => 'black-forest-labs/flux-kontext-pro',
                'task_type' => 'image_to_image',
                'cost_per_generation' => 4,
                'cost_per_generation_usd' => 0.04,
                'lab_priority' => 4,
                'lab_categories' => ['popular', 'business'],
                'featured_in_lab' => true,
                'lab_description' => 'گزینه تجاری سریع و باکیفیت برای ادیت تصویر محصول و حفظ ترکیب‌بندی.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.04, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'input_image', 'aspect_ratio', 'output_format', 'seed'], ['input_image'], 8.7),
                'input_schema' => $this->schema(['prompt' => 'string', 'input_image' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string', 'seed' => 'integer']),
            ]),
            $this->model($common, [
                'provider' => 'replicate',
                'provider_name' => 'Replicate',
                'name' => 'Qwen Image Edit — Replicate',
                'openrouter_model_id' => 'qwen/qwen-image-edit',
                'external_model_id' => 'qwen/qwen-image-edit',
                'task_type' => 'image_to_image',
                'cost_per_generation' => 3,
                'cost_per_generation_usd' => 0.03,
                'lab_priority' => 6,
                'lab_categories' => ['economic', 'business', 'design'],
                'featured_in_lab' => true,
                'lab_description' => 'گزینه اقتصادی برای ادیت، متن داخل تصویر و کاربردهای محصول.',
                'pricing_config' => ['source' => 'replicate', 'unit_price' => 0.03, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image', 'aspect_ratio', 'output_format', 'output_quality', 'seed'], ['image'], 8.4),
                'input_schema' => $this->schema(['prompt' => 'string', 'image' => 'string', 'aspect_ratio' => 'string', 'output_format' => 'string', 'output_quality' => 'integer', 'seed' => 'integer']),
            ]),

            // OpenRouter: فقط سه مدل تصویری GPT برای مقایسه مدیریتی نگه داشته می‌شوند.
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 Mini — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1-mini',
                'external_model_id' => 'openai/gpt-image-1-mini',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.011,
                'lab_priority' => 7,
                'lab_categories' => ['economic', 'popular'],
                'featured_in_lab' => true,
                'lab_description' => 'نسخه اقتصادی GPT Image برای مقایسه کیفیت و هزینه در خروجی‌های روزمره.',
                'pricing_config' => ['source' => 'openai_official', 'unit_price' => 0.011, 'unit' => 'output_image', 'price_source' => 'medium_1024x1024'],
                'capability_config' => $this->capabilities(['prompt', 'image', 'size', 'quality', 'output_format'], ['image'], 8.5),
                'input_schema' => $this->schema(['prompt' => 'string', 'image' => 'array', 'size' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1',
                'external_model_id' => 'openai/gpt-image-1',
                'task_type' => 'text_to_image',
                'cost_per_generation' => 4,
                'cost_per_generation_usd' => 0.042,
                'lab_priority' => 8,
                'lab_categories' => ['popular', 'vip'],
                'featured_in_lab' => true,
                'lab_description' => 'کیفیت بالاتر GPT Image برای ادیت‌های پیچیده و رندر متن داخل تصویر.',
                'pricing_config' => ['source' => 'openai_official', 'unit_price' => 0.042, 'unit' => 'output_image', 'price_source' => 'medium_1024x1024'],
                'capability_config' => $this->capabilities(['prompt', 'image', 'size', 'quality', 'output_format'], ['image'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'image' => 'array', 'size' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 2 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-2',
                'external_model_id' => 'openai/gpt-image-2',
                'task_type' => 'text_to_image',
                // این ستون قدیمیِ اعتبار عدد صحیح می‌خواهد؛ قیمت واقعی GPT Image 2
                // وابسته به اندازه/کیفیت است و عمداً در USD خالی می‌ماند.
                'cost_per_generation' => 5,
                'cost_per_generation_usd' => null,
                'lab_priority' => 9,
                'lab_categories' => ['popular', 'vip'],
                'featured_in_lab' => true,
                'lab_description' => 'مدل جدید و حرفه‌ای OpenAI؛ قیمت وابسته به اندازه و کیفیت خروجی است و باید از اجرای واقعی ثبت شود.',
                'pricing_config' => ['source' => 'openai_official', 'pricing_type' => 'token_and_size_dependent', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image', 'size', 'quality', 'output_format'], ['image'], 9.5),
                'input_schema' => $this->schema(['prompt' => 'string', 'image' => 'array', 'size' => 'string', 'quality' => 'string', 'output_format' => 'string']),
            ]),

            // Fal.ai: در فهرست آماده است؛ فعال‌سازی واقعی به باز شدن خروجی HTTPS سرور نیاز دارد.
            $this->model($common, [
                'provider' => 'fal',
                'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana/edit',
                'external_model_id' => 'fal-ai/nano-banana/edit',
                'task_type' => 'image_to_image',
                'cost_per_generation_usd' => 0.039,
                'lab_priority' => 3,
                'lab_categories' => ['popular', 'identity'],
                'featured_in_lab' => true,
                'lab_description' => 'نسخه Fal.ai مدل متعادل نانو بنانا؛ پس از رفع دسترسی شبکه قابل تست است.',
                'pricing_config' => ['source' => 'fal.ai', 'unit_price' => 0.039, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'image_size', 'output_format'], ['image_urls'], 8.8),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'image_size' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal',
                'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana 2 — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana-2/edit',
                'external_model_id' => 'fal-ai/nano-banana-2/edit',
                'task_type' => 'image_to_image',
                'cost_per_generation_usd' => 0.08,
                'lab_priority' => 2,
                'lab_categories' => ['popular', 'identity', 'economic'],
                'featured_in_lab' => true,
                'lab_description' => 'نسخه سریع و جدید Fal.ai با تعادل کیفیت و هزینه.',
                'pricing_config' => ['source' => 'fal.ai', 'unit_price' => 0.08, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'image_size', 'output_format'], ['image_urls'], 9.0),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'image_size' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal',
                'provider_name' => 'Fal.ai',
                'name' => 'Nano Banana Pro — Fal.ai',
                'openrouter_model_id' => 'fal-ai/nano-banana-pro/edit',
                'external_model_id' => 'fal-ai/nano-banana-pro/edit',
                'task_type' => 'image_to_image',
                'cost_per_generation_usd' => 0.15,
                'lab_priority' => 1,
                'lab_categories' => ['popular', 'identity', 'vip'],
                'featured_in_lab' => true,
                'lab_description' => 'کیفیت ممتاز Fal.ai برای خروجی‌های حساس و حرفه‌ای.',
                'pricing_config' => ['source' => 'fal.ai', 'unit_price' => 0.15, 'unit' => 'output_image', 'price_source' => 'official_model_page', 'tiers' => ['1K' => 0.15, '2K' => 0.15, '4K' => 0.30]],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'image_size', 'output_format'], ['image_urls'], 9.6),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'image_size' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal',
                'provider_name' => 'Fal.ai',
                'name' => 'FLUX Kontext Pro — Fal.ai',
                'openrouter_model_id' => 'fal-ai/flux-pro/kontext',
                'external_model_id' => 'fal-ai/flux-pro/kontext',
                'task_type' => 'image_to_image',
                'cost_per_generation_usd' => 0.04,
                'lab_priority' => 4,
                'lab_categories' => ['popular', 'business'],
                'featured_in_lab' => true,
                'lab_description' => 'مدل تجاری سریع برای ادیت عکس محصول و حفظ ترکیب‌بندی.',
                'pricing_config' => ['source' => 'fal.ai', 'unit_price' => 0.04, 'unit' => 'output_image', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_url', 'image_size', 'output_format'], ['image_url'], 8.7),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_url' => 'string', 'image_size' => 'string', 'output_format' => 'string']),
            ]),
            $this->model($common, [
                'provider' => 'fal',
                'provider_name' => 'Fal.ai',
                'name' => 'Qwen Image Edit — Fal.ai',
                'openrouter_model_id' => 'fal-ai/qwen-image-edit',
                'external_model_id' => 'fal-ai/qwen-image-edit',
                'task_type' => 'image_to_image',
                'cost_per_generation_usd' => 0.03,
                'lab_priority' => 6,
                'lab_categories' => ['economic', 'business', 'design'],
                'featured_in_lab' => true,
                'lab_description' => 'مدل اقتصادی برای ادیت تصویر، متن داخل تصویر و محصولات.',
                'pricing_config' => ['source' => 'fal.ai', 'unit_price' => 0.03, 'unit' => 'megapixel', 'price_source' => 'official_model_page'],
                'capability_config' => $this->capabilities(['prompt', 'image_urls', 'image_size', 'output_format'], ['image_urls'], 8.4),
                'input_schema' => $this->schema(['prompt' => 'string', 'image_urls' => 'array', 'image_size' => 'string', 'output_format' => 'string']),
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
            'supports_face_identity' => false,
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
