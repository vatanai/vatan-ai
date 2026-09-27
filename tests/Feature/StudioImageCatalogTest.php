<?php

namespace Tests\Feature;

use App\Models\AiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudioImageCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_studio_image_catalog_only_contains_the_verified_models(): void
    {
        AiModel::query()->delete();

        foreach ([
            'google/gemini-3.1-flash-lite-image',
            'sourceful/riverflow-v2.5-fast',
            'asfasdf/ad',
        ] as $modelId) {
            AiModel::query()->create([
                'name' => $modelId,
                'openrouter_model_id' => $modelId,
                'provider' => 'openrouter',
                'output_modality' => 'image',
                'task_type' => 'text_to_image',
                'supports_image_input' => true,
                'is_active' => true,
            ]);
        }

        $this->assertEqualsCanonicalizing(
            AiModel::STUDIO_IMAGE_MODEL_PRIORITY,
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
            'is_active' => true,
        ]);

        $this->assertFalse(AiModel::query()->selectableForImageStudio()->exists());
    }
}
