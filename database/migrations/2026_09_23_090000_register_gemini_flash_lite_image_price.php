<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ثبت قیمت مدل تصویری Gemini Flash Lite در کاتالوگ وطن.
     *
     * این مدل قبلاً در فهرست استودیو وجود داشت، اما به‌دلیل خالی بودن
     * cost_per_generation_usd، قبل از ارسال درخواست متوقف می‌شد.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $model = AiModel::query()
            ->where('provider', 'openrouter')
            ->where('openrouter_model_id', 'google/gemini-3.1-flash-lite-image')
            ->first();

        if (! $model) {
            return;
        }

        $pricing = array_replace_recursive(
            (array) ($model->pricing_config ?? []),
            [
                'source' => 'openrouter_image_models_api',
                'unit_price' => 0.034,
                'unit' => 'output_image',
                'price_source' => 'official_endpoint_pricing',
            ]
        );

        $model->forceFill([
            'cost_per_generation' => 1,
            'cost_per_generation_usd' => 0.034,
            'pricing_type' => 'per_generation',
            'pricing_config' => $pricing,
        ])->save();
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        AiModel::query()
            ->where('provider', 'openrouter')
            ->where('openrouter_model_id', 'google/gemini-3.1-flash-lite-image')
            ->where('cost_per_generation_usd', 0.034)
            ->update([
                'cost_per_generation_usd' => null,
                'pricing_type' => 'usage_dependent',
                'pricing_config' => null,
            ]);
    }
};
