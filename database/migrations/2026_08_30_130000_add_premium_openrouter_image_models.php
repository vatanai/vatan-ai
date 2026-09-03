<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * مدل‌های ویژه‌ی OpenRouter برای سه سطح کیفی وطن.
     * مدل‌های اقتصادی migration قبلی حفظ می‌شوند؛ این migration فقط لایه‌ی
     * حرفه‌ای و بهترین خروجی را به همان کاتالوگ مشترک اضافه می‌کند.
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

        // برچسب سطح برای نمایش/فیلتر آینده و هماهنگی با گریدهای سه‌گانه‌ی
        // محصول؛ امتیاز عددی همچنان منبع فعلی pricingGrade باقی می‌ماند.
        $tiers = [
            'black-forest-labs/flux.2-klein-4b' => 'standard',
            'sourceful/riverflow-v2.5-fast' => 'standard',
            'qwen/qwen-image-3' => 'standard',
            'bytedance-seed/seedream-5-0-lite' => 'standard',
            'google/gemini-2.5-flash-image' => 'professional',
            'openai/gpt-image-1-mini' => 'standard',
            'openai/gpt-image-1' => 'professional',
            'openai/gpt-image-2' => 'best',
        ];

        foreach ($tiers as $modelId => $tier) {
            $model = AiModel::query()
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $modelId)
                ->first();
            if (! $model) continue;

            $capabilities = (array) ($model->capability_config ?? []);
            $capabilities['quality_tier'] = $tier;
            $model->forceFill(['capability_config' => $capabilities])->save();
        }
    }

    public function down(): void
    {
        // حذف نمی‌شوند تا محصولات ذخیره‌شده با rollback بدون مدل نمانند.
    }

    private function catalog(): array
    {
        $base = [
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
            // حرفه‌ای: خروجی ممتاز با هزینه‌ی کنترل‌شده‌تر از مدل‌های پرچم‌دار.
            $this->item($base, 'Seedream 5 Pro — OpenRouter', 'bytedance-seed/seedream-5-0-pro', 121, 9.2, 0.045, 'per_generation', ['popular', 'identity', 'business'], 'مدل حرفه‌ای برای چهره، محصول و جزئیات دقیق با پشتیبانی از عکس مرجع.', ['resolution', 'aspect_ratio', 'n', 'input_references', 'seed'], ['input_references']),
            $this->item($base, 'FLUX 2 Pro — OpenRouter', 'black-forest-labs/flux.2-pro', 122, 9.2, 0.03, 'per_megapixel', ['popular', 'business', 'design'], 'مدل حرفه‌ای فتورئال برای صحنه‌های تبلیغاتی و ادیت محصول.', ['aspect_ratio', 'output_format', 'n', 'input_references', 'seed'], ['input_references']),

            // بهترین خروجی: مدل‌های پرچم‌دار برای محصولات حساس و خروجی VIP.
            $this->item($base, 'Gemini 3 Pro Image — OpenRouter', 'google/gemini-3-pro-image', 131, 9.6, null, 'usage_dependent', ['vip', 'identity', 'business'], 'مدل پرچم‌دار Google برای حفظ جزئیات چهره، درک صحنه و ادیت چندتصویری؛ قیمت بر اساس مصرف واقعی.', ['resolution', 'aspect_ratio', 'n', 'input_references'], ['input_references'], [
                'pricing_type' => 'usage_dependent',
                'input_image_token_price' => 0.000002,
                'output_image_token_price' => 0.00012,
                'unit' => 'token',
            ]),
            $this->item($base, 'Qwen Image 3 Pro — OpenRouter', 'qwen/qwen-image-3-pro', 132, 9.5, 0.043, 'per_generation', ['vip', 'identity', 'business', 'design'], 'خروجی رده‌بالا برای چهره و محصول با کنترل مناسب روی متن و ترکیب‌بندی.', ['resolution', 'aspect_ratio', 'n', 'input_references', 'seed'], ['input_references'], [
                'unit_price' => 0.04,
                'input_reference_price' => 0.003,
                'resolution_tiers' => ['1K' => 0.04, '2K' => 0.075],
                'unit' => 'output_image',
            ]),
            $this->item($base, 'MAI Image 2.5 Pro — OpenRouter', 'microsoft/mai-image-2.5-pro', 133, 9.6, null, 'usage_dependent', ['vip', 'identity', 'business'], 'مدل رده‌بالا برای خروجی‌های تبلیغاتی و چهره‌محور؛ مبلغ نهایی از usage واقعی ثبت می‌شود.', ['aspect_ratio', 'n', 'input_references'], ['input_references'], [
                'pricing_type' => 'usage_dependent',
                'input_text_token_price' => 0.000005,
                'input_image_token_price' => 0.000008,
                'output_image_token_price' => 0.000108,
                'unit' => 'token',
            ]),
            $this->item($base, 'FLUX 2 Max — OpenRouter', 'black-forest-labs/flux.2-max', 134, 9.7, 0.07, 'per_megapixel', ['vip', 'identity', 'business'], 'بالاترین سطح FLUX برای خروجی‌های فتورئال، کمپین و محصولات VIP.', ['aspect_ratio', 'output_format', 'n', 'input_references', 'seed'], ['input_references']),
        ];
    }

    private function item(array $base, string $name, string $modelId, int $priority, float $score, ?float $unitPrice, string $pricingType, array $categories, string $description, array $allowed, array $references, array $pricing = []): array
    {
        $pricing = array_merge([
            'source' => 'openrouter_image_models_api',
            'unit_price' => $unitPrice,
            'unit' => $pricingType === 'per_megapixel' ? 'megapixel' : 'output_image',
            'price_source' => $unitPrice === null ? 'official_endpoint_usage' : 'official_endpoint_pricing',
        ], $pricing);

        return array_merge($base, [
            'name' => $name,
            'openrouter_model_id' => $modelId,
            'external_model_id' => $modelId,
            'cost_per_generation' => 1,
            'cost_per_generation_usd' => $unitPrice,
            'pricing_type' => $pricingType,
            'lab_priority' => $priority,
            'lab_categories' => $categories,
            'lab_description' => $description,
            'pricing_config' => $pricing,
            'capability_config' => [
                'quality_score' => $score,
                'quality_tier' => $score >= 9.5 ? 'best' : 'professional',
                'allowed_inputs' => $allowed,
                'reference_fields' => $references,
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
            ],
            'input_schema' => ['properties' => array_fill_keys($allowed, ['type' => 'string'])],
        ]);
    }
};
