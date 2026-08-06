<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductAiQuickAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::query()->where('is_active', true)->firstOrFail();
    }

    public function test_single_product_ai_model_is_saved_without_touching_registration_structure(): void
    {
        $product = $this->makeProduct('single-ai');
        $originalStructure = $product->only(['prompt_template', 'fallback_models', 'input_schema']);
        $model = AiModel::query()->where('provider', 'liara')->where('liara_plan', 'turing')->firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')->patchJson(
            route('admin.products.update_ai_model', $product),
            ['ai_provider' => $model->provider, 'primary_model' => $model->openrouter_model_id]
        );

        $response->assertOk()->assertJsonPath('model_id', $model->openrouter_model_id);
        $product->refresh();
        $this->assertSame($model->provider, $product->ai_provider);
        $this->assertSame($model->openrouter_model_id, $product->primary_model);
        $this->assertEquals($originalStructure, $product->only(['prompt_template', 'fallback_models', 'input_schema']));
    }

    public function test_bulk_ai_model_assignment_updates_all_selected_products(): void
    {
        $first = $this->makeProduct('bulk-ai-first');
        $second = $this->makeProduct('bulk-ai-second');
        $model = AiModel::query()->where('provider', 'openrouter')->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->admin, 'admin')->patchJson(
            route('admin.products.bulk_update_ai_model'),
            [
                'ids' => [$first->id, $second->id],
                'ai_provider' => $model->provider,
                'primary_model' => $model->openrouter_model_id,
            ]
        );

        $response->assertOk()->assertJsonPath('updated', 2);
        $this->assertSame(2, Product::whereIn('id', [$first->id, $second->id])
            ->where('ai_provider', $model->provider)
            ->where('primary_model', $model->openrouter_model_id)
            ->count());
    }

    public function test_invalid_ai_model_filter_only_returns_problem_products(): void
    {
        $validModel = AiModel::query()->where('provider', 'openrouter')->where('is_active', true)->firstOrFail();
        $valid = $this->makeProduct('valid-ai-filter', $validModel->provider, $validModel->openrouter_model_id);
        $invalid = $this->makeProduct('invalid-ai-filter', 'openrouter', 'missing/model-id');

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.products', ['ai_status' => 'invalid']));

        $response->assertOk()
            ->assertSee($invalid->name_fa)
            ->assertDontSee($valid->name_fa)
            ->assertSee('مدل نامعتبر');
    }

    public function test_a_complete_product_can_still_be_registered_locally(): void
    {
        Storage::fake('public');
        $category = Category::query()->firstOrFail();
        $model = AiModel::query()->where('provider', 'liara')->firstOrFail();
        $slug = 'local-registration-check-' . uniqid();

        $response = $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            [
                'name_fa' => 'محصول آزمایشی بررسی ثبت لوکال',
                'name_en' => 'Local Registration Verification Product',
                'slug' => $slug,
                'status' => 'active',
                'category_ids' => [$category->id],
                'ai_provider' => $model->provider,
                'primary_model' => $model->openrouter_model_id,
                'prompt_template' => 'Create a professional image of {prompt}',
                'pricing_model' => 'per_credit',
                'credit_cost' => 5,
                'main_images' => [UploadedFile::fake()->image('local-product.jpg', 800, 800)],
                'skip_image_optimization' => 1,
                'special_features_enabled' => 0,
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('products', [
            'slug' => $slug,
            'ai_provider' => $model->provider,
            'primary_model' => $model->openrouter_model_id,
            'status' => 'active',
        ]);
    }

    private function makeProduct(string $suffix, string $provider = 'openrouter', string $modelId = 'legacy/model'): Product
    {
        return Product::query()->create([
            'name_fa' => 'محصول تست ' . $suffix,
            'name_en' => 'Test Product ' . $suffix,
            'slug' => $suffix . '-' . uniqid(),
            'product_code' => Product::generateUniqueProductCode(),
            'thumbnail' => 'products/thumbnails/default_placeholder.jpg',
            'status' => 'draft',
            'category' => 'عمومی',
            'primary_model' => $modelId,
            'ai_provider' => $provider,
            'prompt_template' => 'Keep this prompt {prompt}',
            'fallback_models' => ['fallback/model'],
            'fallback_model_providers' => ['openrouter'],
            'input_schema' => [[
                'field_id' => 'style',
                'label_fa' => 'سبک',
                'type' => 'text',
                'required' => '1',
            ]],
            'pricing_model' => 'per_credit',
            'credit_cost' => 5,
        ]);
    }
}
