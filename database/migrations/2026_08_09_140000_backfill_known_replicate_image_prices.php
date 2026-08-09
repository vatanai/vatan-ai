<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $model = AiModel::query()
            ->where('provider', 'replicate')
            ->where('external_model_id', 'google/nano-banana')
            ->first();

        if (!$model || (float) $model->cost_per_generation_usd > 0) return;

        $pricing = is_array($model->pricing_config) ? $model->pricing_config : [];
        $model->forceFill([
            'cost_per_generation_usd' => 0.039,
            'pricing_type' => 'per_generation',
            'pricing_config' => array_merge($pricing, [
                'unit_price' => 0.039,
                'unit' => 'output_image',
                'price_source' => 'replicate_official_model_page',
                'price_verified_at' => now()->toIso8601String(),
            ]),
        ])->save();
    }

    public function down(): void
    {
        $model = AiModel::query()
            ->where('provider', 'replicate')
            ->where('external_model_id', 'google/nano-banana')
            ->first();

        if (!$model || (float) $model->cost_per_generation_usd !== 0.039) return;

        $pricing = is_array($model->pricing_config) ? $model->pricing_config : [];
        unset($pricing['unit_price'], $pricing['unit'], $pricing['price_source'], $pricing['price_verified_at']);
        $model->forceFill([
            'cost_per_generation_usd' => null,
            'pricing_type' => 'unknown',
            'pricing_config' => $pricing,
        ])->save();
    }
};
