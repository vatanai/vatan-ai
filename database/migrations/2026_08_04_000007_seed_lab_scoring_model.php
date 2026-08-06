<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) return;

        AiModel::updateOrCreate(
            ['provider' => 'openrouter', 'openrouter_model_id' => 'openai/gpt-4o-mini'],
            [
                'external_model_id' => 'openai/gpt-4o-mini',
                'provider_name' => 'OpenRouter',
                'name' => 'GPT-4o-mini — ارزیابی آزمایش',
                'output_modality' => 'text',
                'supports_image_input' => true,
                'cost_per_generation' => 0,
                'cost_per_generation_usd' => null,
                'default_parameters' => null,
                'capability_config' => [
                    'allowed_inputs' => ['prompt', 'image_url'],
                    'supports_vision' => true,
                    'purpose' => 'lab_scoring',
                ],
                'description' => 'مدل کم‌هزینه‌ی OpenRouter برای امتیازدهی خودکار به خروجی‌های آزمایش.',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        AiModel::where('provider', 'openrouter')
            ->where('openrouter_model_id', 'openai/gpt-4o-mini')
            ->where('name', 'GPT-4o-mini — ارزیابی آزمایش')
            ->delete();
    }
};
