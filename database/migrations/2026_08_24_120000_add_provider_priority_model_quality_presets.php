<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * دو پیش‌فرض مستقل برای مقایسه‌ی providerها:
     *
     * - اولویت Fal.ai: مدل اصلی از Fal.ai و جایگزین از Replicate
     * - اولویت Replicate: مدل اصلی از Replicate و جایگزین از Fal.ai
     *
     * preset_3 («آزمایش شده») عمداً نه بازنویسی و نه جابه‌جا می‌شود.
     */
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets') || ! Schema::hasTable('ai_models')) {
            return;
        }

        $falModels = [
            'standard' => 'fal-ai/nano-banana/edit',
            'professional' => 'fal-ai/nano-banana-2/edit',
            'best' => 'fal-ai/nano-banana-pro/edit',
        ];
        $replicateModels = [
            'standard' => 'google/nano-banana-2-lite',
            'professional' => 'google/nano-banana-2',
            'best' => 'google/nano-banana-pro',
        ];

        // این سه endpoint عکس ورودی + prompt را می‌پذیرند. قبلاً در کاتالوگ
        // ذخیره بودند اما برای انتخاب محصول نمایش داده نمی‌شدند؛ فقط همین سه
        // مدل را فعال می‌کنیم و وضعیت تست‌شده به آن‌ها نمی‌دهیم.
        DB::table('ai_models')
            ->where('provider', 'fal')
            ->whereIn('openrouter_model_id', array_values($falModels))
            ->update([
                'is_active' => true,
                'featured_in_lab' => true,
                'task_type' => 'image_to_image',
                'supports_image_input' => true,
                'lab_status' => 'active',
                'updated_at' => now(),
            ]);

        $pair = static function (string $primaryProvider, string $primaryModel, string $fallbackProvider, string $fallbackModel): array {
            return [
                'primary' => [
                    'model_id' => $primaryModel,
                    'provider' => $primaryProvider,
                ],
                'fallback' => [
                    'model_id' => $fallbackModel,
                    'provider' => $fallbackProvider,
                ],
            ];
        };

        $falPriority = $this->configuration($pair, $falModels, $replicateModels, 'fal');
        $replicatePriority = $this->configuration($pair, $replicateModels, $falModels, 'replicate');

        foreach ([
            'preset_priority_fal' => [
                'name' => 'اولویت Fal.ai',
                'configuration' => $falPriority,
            ],
            'preset_priority_replicate' => [
                'name' => 'اولویت Replicate',
                'configuration' => $replicatePriority,
            ],
        ] as $presetKey => $preset) {
            DB::table('model_quality_presets')->updateOrInsert(
                ['preset_key' => $presetKey],
                [
                    'name' => $preset['name'],
                    'configuration' => json_encode($preset['configuration'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_default_for_product_creation' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('model_quality_presets')) {
            DB::table('model_quality_presets')
                ->whereIn('preset_key', ['preset_priority_fal', 'preset_priority_replicate'])
                ->delete();
        }
    }

    private function configuration(callable $pair, array $primaryModels, array $fallbackModels, string $primaryProvider): array
    {
        $fallbackProvider = $primaryProvider === 'fal' ? 'replicate' : 'fal';
        $qualities = [];

        foreach (['standard', 'professional', 'best'] as $quality) {
            $qualities[$quality] = $pair(
                $primaryProvider,
                $primaryModels[$quality],
                $fallbackProvider,
                $fallbackModels[$quality]
            );
        }

        return [
            'quality_models' => $qualities,
            // مسیر کاربران رایگان همچنان فقط دو سطح موجود خود را دارد؛
            // سطح best قفل می‌ماند اما تنظیم معتبرش برای فعال‌سازی بعدی حفظ می‌شود.
            'free_quality_models' => [
                'standard' => $qualities['standard'],
                'best' => $qualities['best'],
            ],
        ];
    }
};
