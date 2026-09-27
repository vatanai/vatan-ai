<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStudioImageModelSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_choose_the_models_visible_in_image_studio(): void
    {
        $admin = $this->makeAdmin();
        AiModel::query()->update([
            'featured_in_image_studio' => false,
            'studio_image_priority' => null,
        ]);
        $models = collect(range(1, 11))->map(fn (int $index) => $this->makeEligibleModel($index));
        $selectedIds = $models->take(10)->pluck('id')->all();

        $response = $this->actingAs($admin, 'admin')->put(
            route('admin.ai-models.image-studio-selection'),
            ['studio_models' => $selectedIds],
        );

        $response->assertRedirect(route('admin.ai-models.index'));
        $this->assertSame(10, AiModel::query()->where('featured_in_image_studio', true)->count());
        $this->assertSame(
            $selectedIds,
            AiModel::query()
                ->where('featured_in_image_studio', true)
                ->orderBy('studio_image_priority')
                ->pluck('id')
                ->all(),
        );
        $this->assertFalse($models->last()->fresh()->featured_in_image_studio);
    }

    public function test_admin_must_keep_at_least_ten_models_in_image_studio(): void
    {
        $admin = $this->makeAdmin();
        $modelIds = collect(range(1, 9))->map(fn (int $index) => $this->makeEligibleModel($index)->id)->all();

        $this->actingAs($admin, 'admin')
            ->from(route('admin.ai-models.index'))
            ->put(route('admin.ai-models.image-studio-selection'), ['studio_models' => $modelIds])
            ->assertRedirect(route('admin.ai-models.index'))
            ->assertSessionHasErrors('studio_models');
    }

    private function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر تست استودیو',
            'email' => 'studio-models-' . uniqid() . '@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);
    }

    private function makeEligibleModel(int $index): AiModel
    {
        return AiModel::query()->create([
            'name' => 'Studio model ' . $index,
            'openrouter_model_id' => 'test/studio-image-' . $index . '-' . uniqid(),
            'provider' => 'openrouter',
            'output_modality' => 'image',
            'task_type' => 'text_to_image',
            'supports_image_input' => true,
            'capability_config' => ['supports_text_to_image' => true, 'supports_image_to_image' => true],
            'is_active' => true,
            'featured_in_image_studio' => false,
        ]);
    }
}
