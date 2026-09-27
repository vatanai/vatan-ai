<?php

namespace Tests\Feature;

use App\Models\AiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioImageCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_image_catalog_only_contains_admin_selected_models(): void
    {
        AiModel::query()->delete();

        $selectedModels = [
            'google/gemini-3.1-flash-lite-image',
            'sourceful/riverflow-v2.5-fast',
        ];

        foreach ([...$selectedModels, 'asfasdf/ad'] as $index => $modelId) {
            AiModel::query()->create([
                'name' => $modelId,
                'openrouter_model_id' => $modelId,
                'provider' => 'openrouter',
                'output_modality' => 'image',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'capability_config' => ['supports_text_to_image' => true, 'supports_image_to_image' => true],
                'is_active' => true,
                'featured_in_image_studio' => in_array($modelId, $selectedModels, true),
                'studio_image_priority' => in_array($modelId, $selectedModels, true) ? $index + 1 : null,
            ]);
        }

        $this->assertEqualsCanonicalizing(
            $selectedModels,
            AiModel::query()->selectableForImageStudio()->pluck('openrouter_model_id')->all(),
        );
    }

    public function test_models_without_image_input_are_not_exposed_in_studio(): void
    {
        AiModel::query()->delete();

        AiModel::query()->create([
            'name' => 'Gemini Flash Lite without references',
            'openrouter_model_id' => 'google/gemini-3.1-flash-lite-image',
            'provider' => 'openrouter',
            'output_modality' => 'image',
            'task_type' => 'text_to_image',
            'supports_image_input' => false,
            'capability_config' => ['supports_text_to_image' => true, 'supports_image_to_image' => false],
            'is_active' => true,
            'featured_in_image_studio' => true,
            'studio_image_priority' => 1,
        ]);

        $this->assertFalse(AiModel::query()->selectableForImageStudio()->exists());
    }
}
