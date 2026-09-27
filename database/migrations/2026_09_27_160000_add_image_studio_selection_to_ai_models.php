<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * انتخاب مدل‌های صفحهٔ عمومی ساخت عکس را مستقل از کاتالوگ محصول نگه می‌دارد.
     * مدل‌ها حذف یا غیرفعال نمی‌شوند؛ فقط نمایش آن‌ها در استودیو قابل مدیریت است.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        Schema::table('ai_models', function (Blueprint $table): void {
            if (! Schema::hasColumn('ai_models', 'featured_in_image_studio')) {
                $table->boolean('featured_in_image_studio')->default(false)->after('featured_in_lab');
            }
            if (! Schema::hasColumn('ai_models', 'studio_image_priority')) {
                $table->unsignedSmallInteger('studio_image_priority')->nullable()->after('featured_in_image_studio');
            }
        });

        foreach (AiModel::STUDIO_IMAGE_MODEL_PRIORITY as $priority => $modelId) {
            DB::table('ai_models')
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $modelId)
                ->where('output_modality', 'image')
                ->where('task_type', 'text_to_image')
                ->where('supports_image_input', true)
                ->update([
                    'featured_in_image_studio' => true,
                    'studio_image_priority' => $priority + 1,
                    'updated_at' => now(),
                ]);
        }

        foreach ($this->capabilities() as $modelId => $capabilities) {
            $model = AiModel::query()
                ->where('provider', 'openrouter')
                ->where('openrouter_model_id', $modelId)
                ->first();

            if (! $model) {
                continue;
            }

            $model->forceFill([
                'capability_config' => array_replace(
                    (array) ($model->capability_config ?? []),
                    $capabilities,
                ),
            ])->save();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        Schema::table('ai_models', function (Blueprint $table): void {
            if (Schema::hasColumn('ai_models', 'studio_image_priority')) {
                $table->dropColumn('studio_image_priority');
            }
            if (Schema::hasColumn('ai_models', 'featured_in_image_studio')) {
                $table->dropColumn('featured_in_image_studio');
            }
        });
    }

    /** متادیتای محدودکنندهٔ رابط کاربری، مطابق کاتالوگ زندهٔ OpenRouter. */
    private function capabilities(): array
    {
        $commonRatios = ['1:1', '4:3', '3:4', '3:2', '2:3', '16:9', '9:16', '21:9'];
        $openAiClassicRatios = ['1:1', '3:2', '2:3'];

        return [
            'google/gemini-3.1-flash-lite-image' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 14,
                'max_images' => 1,
            ],
            'google/gemini-3.1-flash-image' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K', '2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 14,
                'max_images' => 1,
            ],
            'google/gemini-3-pro-image' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K', '2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 14,
                'max_images' => 1,
            ],
            'google/gemini-2.5-flash-image' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 3,
                'max_images' => 1,
            ],
            'openai/gpt-image-1-mini' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_aspect_ratios' => $openAiClassicRatios,
                'max_reference_images' => 16,
            ],
            'openai/gpt-image-1' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_aspect_ratios' => $openAiClassicRatios,
                'max_reference_images' => 16,
            ],
            'openai/gpt-image-2' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 16,
            ],
            'sourceful/riverflow-v2.5-fast' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K', '2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 4,
                'max_images' => 1,
            ],
            'black-forest-labs/flux.2-klein-4b' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 4,
                'max_images' => 1,
            ],
            'qwen/qwen-image-3' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K', '2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 4,
            ],
            'qwen/qwen-image-3-pro' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['1K', '2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 4,
            ],
            'bytedance-seed/seedream-5-0-lite' => [
                'supports_text_to_image' => true,
                'supports_image_to_image' => true,
                'supported_resolutions' => ['2K'],
                'supported_aspect_ratios' => $commonRatios,
                'max_reference_images' => 14,
            ],
        ];
    }
};
