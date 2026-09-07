<?php

use App\Models\AiModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_models')) {
            return;
        }

        $kontext = AiModel::query()
            ->where('provider', 'fal')
            ->where('external_model_id', 'fal-ai/flux-kontext/dev')
            ->first();
        if ($kontext) {
            $kontext->forceFill([
                'capability_config' => [
                    'allowed_inputs' => ['prompt', 'image_url', 'output_format'],
                    'reference_fields' => ['image_url'],
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'quality_score' => 8.6,
                ],
                'input_schema' => ['properties' => [
                    'prompt' => ['type' => 'string'],
                    'image_url' => ['type' => 'string'],
                    'output_format' => ['type' => 'string'],
                ]],
            ])->save();
        }

        $flux = AiModel::query()
            ->where('provider', 'fal')
            ->where('external_model_id', 'fal-ai/flux/dev/image-to-image')
            ->first();
        if ($flux) {
            $flux->forceFill([
                'default_parameters' => ['strength' => 0.8],
                'capability_config' => [
                    'allowed_inputs' => ['prompt', 'image_url', 'strength', 'output_format'],
                    'reference_fields' => ['image_url'],
                    'supports_text_to_image' => true,
                    'supports_image_to_image' => true,
                    'quality_score' => 8.2,
                ],
                'input_schema' => ['properties' => [
                    'prompt' => ['type' => 'string'],
                    'image_url' => ['type' => 'string'],
                    'strength' => ['type' => 'number', 'default' => 0.8],
                    'output_format' => ['type' => 'string'],
                ]],
            ])->save();
        }
    }

    public function down(): void
    {
        // تنظیمات فنی مدل‌ها عمداً به نسخه‌ی قبلی برگردانده نمی‌شود.
    }
};
