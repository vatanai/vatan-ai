<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * پیش‌فرض فرم ثبت محصول را صریحاً روی مسیر OpenRouter و سپس Fal.ai می‌گذارد.
     * این migration فقط preset پیش‌فرض تصویر را تغییر می‌دهد و به مسیر ویدیو دست نمی‌زند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')
            || ! Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation')) {
            return;
        }

        $configuration = [
            'quality_models' => $this->qualityRoutes(),
            'free_quality_models' => [
                'standard' => $this->qualityRoutes()['standard'],
                'best' => $this->qualityRoutes()['best'],
            ],
        ];

        $target = DB::table('model_quality_presets')->where('preset_key', 'preset_or_fal')->first();
        if (! $target) {
            DB::table('model_quality_presets')->insert([
                'preset_key' => 'preset_or_fal',
                'name' => 'اولویت OpenRouter + Fal.ai',
                'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_default_for_product_creation' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $target = DB::table('model_quality_presets')->where('preset_key', 'preset_or_fal')->first();
        } else {
            DB::table('model_quality_presets')->where('id', $target->id)->update([
                'name' => 'اولویت OpenRouter + Fal.ai',
                'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }

        DB::table('model_quality_presets')->update(['is_default_for_product_creation' => false]);
        DB::table('model_quality_presets')->where('id', $target->id)->update([
            'is_default_for_product_creation' => true,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // مقدار پیش‌فرض قبلی قابل بازگردانی مطمئن نیست؛ مسیر جدید امن باقی می‌ماند.
    }

    private function qualityRoutes(): array
    {
        return [
            'standard' => [
                'primary' => ['model_id' => 'openai/gpt-image-1-mini', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana/edit', 'provider' => 'fal'],
            ],
            'professional' => [
                'primary' => ['model_id' => 'openai/gpt-image-1', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana-2/edit', 'provider' => 'fal'],
            ],
            'best' => [
                'primary' => ['model_id' => 'openai/gpt-image-2', 'provider' => 'openrouter'],
                'fallback' => ['model_id' => 'fal-ai/nano-banana-pro/edit', 'provider' => 'fal'],
            ],
        ];
    }
};
