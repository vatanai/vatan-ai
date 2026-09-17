<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) {
            return;
        }

        $qualityCreditCosts = [
            'standard' => 12,
            'professional' => 20,
            'best' => 50,
        ];

        $presets = [
            'preset_or_fal' => [
                'name' => 'اوپن روتر + فال ای آی',
                'quality_models' => [
                    'standard' => $this->pair('google/gemini-3.1-flash-lite-image', 'openrouter', 'fal-ai/nano-banana/edit', 'fal'),
                    'professional' => $this->pair('google/gemini-3.1-flash-image', 'openrouter', 'fal-ai/nano-banana-2/edit', 'fal'),
                    'best' => $this->pair('google/gemini-3-pro-image', 'openrouter', 'fal-ai/nano-banana-pro/edit', 'fal'),
                ],
            ],
            'preset_or_replicate' => [
                'name' => 'اوپن روتر + ریپیلیکیت',
                'quality_models' => [
                    'standard' => $this->pair('google/gemini-3.1-flash-lite-image', 'openrouter', 'google/nano-banana-2-lite', 'replicate'),
                    'professional' => $this->pair('google/gemini-3.1-flash-image', 'openrouter', 'google/nano-banana-2', 'replicate'),
                    'best' => $this->pair('google/gemini-3-pro-image', 'openrouter', 'google/nano-banana-pro', 'replicate'),
                ],
            ],
        ];

        foreach ($presets as $presetKey => $preset) {
            $configuration = [
                'quality_models' => $preset['quality_models'],
                'free_quality_models' => [
                    'standard' => $preset['quality_models']['standard'],
                    'best' => $preset['quality_models']['best'],
                ],
                'quality_credit_costs' => $qualityCreditCosts,
            ];

            DB::table('model_quality_presets')->updateOrInsert(
                ['preset_key' => $presetKey],
                [
                    'name' => $preset['name'],
                    'configuration' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
                ->whereIn('preset_key', ['preset_or_fal', 'preset_or_replicate'])
                ->delete();
        }
    }

    private function pair(string $primaryModel, string $primaryProvider, string $fallbackModel, string $fallbackProvider): array
    {
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
    }
};
