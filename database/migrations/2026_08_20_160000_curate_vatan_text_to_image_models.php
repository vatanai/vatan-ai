<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * کاتالوگ قابل‌استفاده‌ی وطن برای «متن → عکس» را در هر سه provider
     * روشن می‌کند و آن را بر اساس هزینه/کیفیت مرتب می‌سازد.
     *
     * مدل‌های قدیمی حذف یا از آزمایشگاه خارج نمی‌شوند؛ ممکن است محصول قدیمی
     * به آن‌ها اشاره کند. این migration فقط مدل‌های منتخب را فعال و اولویت‌دار
     * می‌کند و تنظیمات پیش‌فرض کیفیت را به جفت‌های امن OpenRouter/Replicate
     * می‌سپارد تا تا زمان رفع مسیر Fal.ai، مسیر تولید اصلی پایدار بماند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        foreach ($this->catalog() as $data) {
            $this->upsertModel($data);
        }

        $this->prioritizeSelectedModels();
        $this->updateTierDefaults();
        $this->updateQualityPresets();
    }

    public function down(): void
    {
        // مدل‌ها و تنظیمات محصول عمداً حذف نمی‌شوند تا rollback به محصولی
        // اشاره نکند که دیگر در کاتالوگ موجود نیست.
    }

    private function upsertModel(array $data): void
    {
        $model = AiModel::query()
            ->where('provider', $data['provider'])
            ->where(function ($query) use ($data): void {
                $query->where('external_model_id', $data['external_model_id'])
                    ->orWhere('openrouter_model_id', $data['openrouter_model_id']);
            })
            ->first();

        if (! $model) {
            AiModel::create($data);
            return;
        }

        // capabilityهای موجود (مثلاً schema مدل‌های Replicate) حفظ می‌شوند و
        // فقط کلیدهای کاتالوگ وطن روی آن‌ها اعمال می‌شود.
        foreach (['capability_config', 'pricing_config'] as $jsonField) {
            if (array_key_exists($jsonField, $data)) {
                $data[$jsonField] = array_replace_recursive(
                    (array) ($model->{$jsonField} ?? []),
                    (array) $data[$jsonField]
                );
            }
        }

        $model->forceFill(Arr::except($data, [
            'provider', 'openrouter_model_id', 'external_model_id', 'name',
        ]))->save();
    }

    private function catalog(): array
    {
        $common = [
            'output_modality' => 'image',
            'supports_face_identity' => false,
            'supports_multiple_faces' => false,
            'supports_audio' => false,
            'supports_video_input' => false,
            'default_width' => 1024,
            'default_height' => 1024,
            'supports_webhook' => true,
            'is_active' => true,
            'commercial_use' => true,
            'lab_status' => 'active',
            'featured_in_lab' => true,
        ];

        $aspectRatios = ['1:1', '2:3', '3:2', '3:4', '4:3', '4:5', '5:4', '9:16', '16:9'];

        return [
            // OpenRouter: قیمت دو مدل از کاتالوگ معتبر فعلی آمده و GPT Image 2
            // عمداً live/usage-dependent می‌ماند.
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 Mini — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1-mini',
                'external_model_id' => 'openai/gpt-image-1-mini',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.011,
                'pricing_type' => 'per_generation',
                'lab_priority' => 10,
                'lab_categories' => ['popular', 'economic', 'business'],
                'lab_description' => 'گزینه‌ی استاندارد و اقتصادی برای ساخت روزمره‌ی محصولات وطن.',
                'pricing_config' => [
                    'source' => 'openrouter_official',
                    'unit_price' => 0.011,
                    'unit' => 'output_image_or_usage',
                    'price_source' => 'official_model_page',
                ],
                'capability_config' => [
                    'quality_score' => 8.5,
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'allowed_inputs' => ['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'],
                    'reference_fields' => ['input_references'],
                ],
            ]),
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 1 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-1',
                'external_model_id' => 'openai/gpt-image-1',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => 0.042,
                'pricing_type' => 'per_generation',
                'lab_priority' => 20,
                'lab_categories' => ['popular', 'business', 'design'],
                'lab_description' => 'سطح حرفه‌ای برای جزئیات بیشتر، طراحی محصول و متن داخل تصویر.',
                'pricing_config' => [
                    'source' => 'openrouter_official',
                    'unit_price' => 0.042,
                    'unit' => 'output_image_or_usage',
                    'price_source' => 'official_model_page',
                ],
                'capability_config' => [
                    'quality_score' => 9.0,
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'allowed_inputs' => ['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'],
                    'reference_fields' => ['input_references'],
                ],
            ]),
            $this->model($common, [
                'provider' => 'openrouter',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT Image 2 — OpenRouter',
                'openrouter_model_id' => 'openai/gpt-image-2',
                'external_model_id' => 'openai/gpt-image-2',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'cost_per_generation' => 1,
                'cost_per_generation_usd' => null,
                'pricing_type' => 'usage_dependent',
                'lab_priority' => 30,
                'lab_categories' => ['vip', 'business', 'design'],
                'lab_description' => 'بهترین سطح OpenRouter؛ قیمت نهایی وابسته به مصرف واقعی و کیفیت خروجی است.',
                'pricing_config' => [
                    'source' => 'openrouter_usage',
                    'pricing_type' => 'usage_dependent',
                    'unit' => 'reported_usage',
                    'price_source' => 'live_usage',
                ],
                'capability_config' => [
                    'quality_score' => 9.5,
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'allowed_inputs' => ['prompt', 'input_references', 'aspect_ratio', 'quality', 'output_format'],
                    'reference_fields' => ['input_references'],
                ],
            ]),

            // Fal.ai: سه مدل اصلی متن به عکس. قیمت Flux Schnell/Dev از منبع
            // رسمی ثبت شده؛ قیمت Flux 2 Max تا رفع مشکل شبکه live باقی می‌ماند.
            $this->falTextToImage($common, 'fal-ai/flux/schnell', 'FLUX Schnell — Fal.ai', 10, 8.5, 0.003, 'per_megapixel', ['economic', 'popular'], 'مدل استاندارد بسیار سریع و کم‌هزینه برای تست و ساخت روزمره.'),
            $this->falTextToImage($common, 'fal-ai/flux/dev', 'FLUX Dev — Fal.ai', 20, 9.0, 0.025, 'per_megapixel', ['business', 'popular'], 'مدل حرفه‌ای برای جزئیات بالاتر و خروجی‌های تجاری.'),
            $this->falTextToImage($common, 'fal-ai/flux-2-max', 'FLUX 2 Max — Fal.ai', 30, 9.5, null, 'usage_dependent', ['vip', 'business'], 'بهترین سطح Fal.ai؛ مبلغ نهایی پس از دسترسی زنده به قیمت endpoint ثبت می‌شود.'),

            // Replicate: هر سه مدل هم prompt و هم در صورت نیاز عکس مرجع را
            // می‌پذیرند؛ بنابراین برای محصولات وطن و ادیت محصول کاربردی‌اند.
            $this->replicateTextToImage($common, 'google/nano-banana-2-lite', 'Nano Banana 2 Lite — Replicate', 10, 8.5, 0.039, ['economic', 'popular', 'business'], 'مدل استاندارد اقتصادی برای ساخت سریع و ادیت سبک محصول.'),
            $this->replicateTextToImage($common, 'bytedance/seedream-5-pro', 'Seedream 5 Pro — Replicate', 20, 9.2, 0.045, ['business', 'design', 'popular'], 'مدل حرفه‌ای برای محصول، متن داخل تصویر و خروجی‌های 2K.'),
            $this->replicateTextToImage($common, 'openai/gpt-image-2', 'GPT Image 2 — Replicate', 30, 9.5, 0.128, ['vip', 'business', 'design'], 'بهترین سطح Replicate برای خروجی‌های کلیدی و کمپین‌های مهم.'),
        ];
    }

    private function falTextToImage(array $common, string $modelId, string $name, int $priority, float $quality, ?float $usd, string $pricingType, array $categories, string $description): array
    {
        return $this->model($common, [
            'provider' => 'fal',
            'provider_name' => 'Fal.ai',
            'name' => $name,
            'openrouter_model_id' => $modelId,
            'external_model_id' => $modelId,
            'task_type' => 'text_to_image',
            'supports_image_input' => false,
            'cost_per_generation' => 1,
            'cost_per_generation_usd' => $usd,
            'pricing_type' => $pricingType,
            'lab_priority' => $priority,
            'lab_categories' => $categories,
            'lab_description' => $description,
            'input_schema' => ['properties' => [
                'prompt' => ['type' => 'string'],
                'output_format' => ['type' => 'string'],
            ]],
            'pricing_config' => [
                'source' => 'fal_official',
                'unit_price' => $usd,
                'unit' => $pricingType === 'per_megapixel' ? 'megapixel' : 'reported_usage',
                'price_source' => $usd === null ? 'fal_live_lookup_after_network_fix' : 'official_model_page',
            ],
            'capability_config' => [
                'quality_score' => $quality,
                'allowed_inputs' => ['prompt', 'output_format'],
                'reference_fields' => [],
                'supports_text_to_image' => true,
                'supports_image_to_image' => false,
            ],
        ]);
    }

    private function replicateTextToImage(array $common, string $modelId, string $name, int $priority, float $quality, float $usd, array $categories, string $description): array
    {
        $referenceFields = match ($modelId) {
            'google/nano-banana-2-lite', 'bytedance/seedream-5-pro' => ['image_input'],
            default => ['input_images'],
        };

        return $this->model($common, [
            'provider' => 'replicate',
            'provider_name' => 'Replicate',
            'name' => $name,
            'openrouter_model_id' => $modelId,
            'external_model_id' => $modelId,
            'task_type' => 'text_to_image',
            'supports_image_input' => true,
            'cost_per_generation' => 1,
            'cost_per_generation_usd' => $usd,
            'pricing_type' => 'per_generation',
            'lab_priority' => $priority,
            'lab_categories' => $categories,
            'lab_description' => $description,
            'pricing_config' => [
                'source' => 'replicate_official',
                'unit_price' => $usd,
                'unit' => 'output_image',
                'price_source' => 'official_model_page',
            ],
            'capability_config' => [
                'quality_score' => $quality,
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'allowed_inputs' => array_values(array_unique(array_merge(['prompt', 'aspect_ratio', 'output_format'], $referenceFields))),
                'reference_fields' => $referenceFields,
            ],
        ]);
    }

    private function model(array $common, array $specific): array
    {
        return array_merge($common, $specific);
    }

    private function updateTierDefaults(): void
    {
        if (! Schema::hasTable('model_tier_defaults')) {
            return;
        }

        $tiers = [
            'free' => ['google/nano-banana-2-lite', 'replicate', 'openai/gpt-image-1-mini', 'openrouter'],
            'economy' => ['openai/gpt-image-1-mini', 'openrouter', 'google/nano-banana-2-lite', 'replicate'],
            'pro' => ['openai/gpt-image-1', 'openrouter', 'bytedance/seedream-5-pro', 'replicate'],
            'business' => ['openai/gpt-image-2', 'replicate', 'openai/gpt-image-2', 'openrouter'],
        ];

        foreach ($tiers as $tierKey => [$primaryModel, $primaryProvider, $fallbackModel, $fallbackProvider]) {
            DB::table('model_tier_defaults')
                ->where('tier_key', $tierKey)
                ->update([
                    'primary_model_id' => $primaryModel,
                    'primary_provider' => $primaryProvider,
                    'fallback_model_id' => $fallbackModel,
                    'fallback_provider' => $fallbackProvider,
                    'updated_at' => now(),
                ]);
        }
    }

    private function prioritizeSelectedModels(): void
    {
        $selected = collect($this->catalog())
            ->groupBy('provider')
            ->map(fn ($models) => $models->pluck('openrouter_model_id')->all());

        foreach ($selected as $provider => $modelIds) {
            // گزینه‌های قدیمی ویرایش/حفظ هویت حذف نمی‌شوند؛ فقط پایین‌تر از
            // سه مدل اصلی متن‌به‌عکس قرار می‌گیرند تا انتخاب محصول از مسیر
            // استاندارد، حرفه‌ای و بهترین خروجی شروع شود.
            AiModel::query()
                ->where('provider', $provider)
                ->where('featured_in_lab', true)
                ->whereNotIn('openrouter_model_id', $modelIds)
                ->where('lab_priority', '<', 40)
                ->update(['lab_priority' => 40, 'updated_at' => now()]);
        }
    }

    private function updateQualityPresets(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        $configuration = [
            'quality_models' => [
                'standard' => $this->pair('openai/gpt-image-1-mini', 'openrouter', 'google/nano-banana-2-lite', 'replicate'),
                'professional' => $this->pair('openai/gpt-image-1', 'openrouter', 'bytedance/seedream-5-pro', 'replicate'),
                'best' => $this->pair('openai/gpt-image-2', 'replicate', 'openai/gpt-image-2', 'openrouter'),
            ],
            'free_quality_models' => [
                'standard' => $this->pair('google/nano-banana-2-lite', 'replicate', 'openai/gpt-image-1-mini', 'openrouter'),
                'best' => $this->pair('openai/gpt-image-2', 'replicate', 'openai/gpt-image-2', 'openrouter'),
            ],
        ];

        DB::table('model_quality_presets')->update([
            'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    private function pair(string $primaryModel, string $primaryProvider, string $fallbackModel, string $fallbackProvider): array
    {
        return [
            'primary' => ['model_id' => $primaryModel, 'provider' => $primaryProvider],
            'fallback' => ['model_id' => $fallbackModel, 'provider' => $fallbackProvider],
        ];
    }
};
