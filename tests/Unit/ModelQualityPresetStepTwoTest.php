<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ModelQualityPresetController;
use App\Http\Controllers\Admin\ProductController;
use App\Models\AiModel;
use App\Models\ModelQualityPreset;
use App\Models\Product;
use App\Services\ModelTierService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ModelQualityPresetStepTwoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('ai_models', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('openrouter_model_id');
            $table->string('provider');
            $table->boolean('is_active')->default(true);
            $table->boolean('featured_in_lab')->default(true);
            $table->string('output_modality')->default('image');
            $table->string('task_type')->default('text_to_image');
            $table->boolean('supports_image_input')->default(false);
            $table->timestamps();
        });

        Schema::create('model_quality_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('preset_key')->unique();
            $table->string('name');
            $table->json('configuration');
            $table->boolean('is_default_for_product_creation')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->json('model_configuration')->nullable();
            $table->string('primary_model')->nullable();
            $table->string('ai_provider')->nullable();
            $table->json('fallback_models')->nullable();
            $table->json('fallback_model_providers')->nullable();
            $table->string('pricing_model')->nullable();
            $table->integer('credit_cost')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_credit_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('preset_key');
            $table->boolean('is_default_for_product_creation')->default(false);
            $table->integer('standard_credit_cost')->default(12);
            $table->integer('professional_credit_cost')->default(20);
            $table->integer('best_credit_cost')->default(50);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('product_credit_presets');
        Schema::dropIfExists('products');
        Schema::dropIfExists('model_quality_presets');
        Schema::dropIfExists('ai_models');

        parent::tearDown();
    }

    public function test_step_two_keeps_the_selected_preset_while_it_is_edited(): void
    {
        $view = file_get_contents(resource_path('views/admin/products/partials/model-quality-architecture-preview.blade.php'));

        $this->assertStringContainsString('ذخیره روی همین پیش‌فرض', $view);
        $this->assertStringContainsString('const markPresetAsDirty = () => {', $view);
        $this->assertStringContainsString("const key = (presetSelect?.value && presetSelect.value !== 'custom') ? presetSelect.value : null;", $view);
        $this->assertStringContainsString("requestPreset(url, 'PATCH', { configuration })", $view);
        $this->assertStringContainsString('if (target) target.selected = true;', $view);
        $this->assertStringContainsString('window.saveStepTwoModelQualityPresetIfDirty', $view);
        $this->assertStringNotContainsString("presetSelect.value = 'custom';", $view);
    }

    public function test_updating_the_selected_preset_replaces_models_without_losing_other_settings(): void
    {
        $original = $this->completeConfiguration('old');
        $updatedModels = $this->completeConfiguration('new');
        $original['quality_credit_costs'] = ['standard' => 3, 'professional' => 7, 'best' => 11];

        $preset = ModelQualityPreset::query()->create([
            'preset_key' => 'preset_selected',
            'name' => 'پیش‌فرض انتخاب‌شده',
            'configuration' => $original,
            'is_default_for_product_creation' => true,
        ]);
        $linked = $this->makeProduct('preset_selected', $original);
        $unrelated = $this->makeProduct('custom', $original);

        $request = Request::create('/admin/model-quality-presets/' . $preset->id, 'PATCH', [
            'configuration' => [
                'quality_models' => $updatedModels['quality_models'],
                'free_quality_models' => $updatedModels['free_quality_models'],
            ],
        ]);

        $response = app(ModelQualityPresetController::class)->update($request, $preset);

        $this->assertSame(200, $response->status());
        $preset->refresh();
        $this->assertSame($updatedModels['quality_models'], $preset->configuration['quality_models']);
        $this->assertSame($updatedModels['free_quality_models'], $preset->configuration['free_quality_models']);
        $this->assertSame($original['quality_credit_costs'], $preset->configuration['quality_credit_costs']);
        $this->assertSame([
            'enabled' => true,
            'primary_max_attempts' => 3,
            'primary_retry_delays_seconds' => [5, 10],
            'fallback_max_attempts' => 1,
        ], $preset->configuration['image_retry_policy']);
        $linked->refresh();
        $unrelated->refresh();
        $this->assertSame($updatedModels['quality_models'], $linked->model_configuration['quality_models']);
        $this->assertSame($updatedModels['free_quality_models'], $linked->model_configuration['free_quality_models']);
        $this->assertSame($original['quality_credit_costs'], $linked->model_configuration['quality_credit_costs']);
        $this->assertSame($original['quality_models'], $unrelated->model_configuration['quality_models']);
        $this->assertSame($preset->configuration['image_retry_policy'], $linked->model_configuration['image_retry_policy']);
        $this->assertSame($updatedModels['quality_models']['best']['primary']['model_id'], $linked->primary_model);
        $this->assertSame($updatedModels['quality_models']['best']['fallback']['model_id'], $linked->fallback_models[0]);

        $execution = app(ModelTierService::class)->executionProductForQuality($linked, null, 'standard', 'free');
        $this->assertSame($updatedModels['free_quality_models']['standard']['primary']['model_id'], $execution->primary_model);
        $this->assertSame($updatedModels['free_quality_models']['standard']['fallback']['model_id'], $execution->fallback_models[0]);
        $this->assertTrue((bool) $execution->strict_model_priority);
    }

    public function test_saving_the_product_list_dialog_updates_the_selected_preset_and_all_linked_products(): void
    {
        $original = $this->completeConfiguration('old');
        $updated = $this->completeConfiguration('new');
        $preset = ModelQualityPreset::query()->create([
            'preset_key' => 'preset_selected',
            'name' => 'پیش‌فرض انتخاب‌شده',
            'configuration' => $original,
            'is_default_for_product_creation' => true,
        ]);
        $first = $this->makeProduct($preset->preset_key, $original);
        $second = $this->makeProduct($preset->preset_key, $original);
        $custom = $this->makeProduct('custom', $original);
        $configuration = array_replace($updated, [
            'quality_preset_key' => $preset->preset_key,
            'quality_architecture_enabled' => true,
        ]);

        $request = Request::create('/admin/products/bulk-model-quality-configuration', 'PATCH', [
            'ids' => [$first->id],
            'model_configuration' => $configuration,
            'update_preset' => true,
        ]);
        $response = app(ProductController::class)->bulkUpdateModelQualityConfiguration($request);

        $this->assertSame(200, $response->status());
        $this->assertSame($updated['quality_models'], $preset->fresh()->configuration['quality_models']);
        $this->assertSame($updated['quality_models'], $first->fresh()->model_configuration['quality_models']);
        $this->assertSame($updated['quality_models'], $second->fresh()->model_configuration['quality_models']);
        $this->assertSame($original['quality_models'], $custom->fresh()->model_configuration['quality_models']);
    }

    public function test_saving_one_product_in_the_list_uses_the_same_preset_and_execution_models(): void
    {
        $original = $this->completeConfiguration('old');
        $updated = $this->completeConfiguration('new');
        $preset = ModelQualityPreset::query()->create([
            'preset_key' => 'preset_selected',
            'name' => 'پیش‌فرض انتخاب‌شده',
            'configuration' => $original,
            'is_default_for_product_creation' => true,
        ]);
        $product = $this->makeProduct($preset->preset_key, $original);
        $configuration = array_replace($updated, [
            'quality_preset_key' => $preset->preset_key,
            'quality_architecture_enabled' => true,
        ]);

        $request = Request::create('/admin/products/' . $product->id . '/model-quality-configuration', 'PATCH', [
            'model_configuration' => $configuration,
            'update_preset' => true,
        ]);
        $response = app(ProductController::class)->updateModelQualityConfiguration($request, $product);

        $this->assertSame(200, $response->status());
        $this->assertSame($updated['free_quality_models'], $preset->fresh()->configuration['free_quality_models']);
        $product->refresh();
        $this->assertSame($updated['quality_models'], $product->model_configuration['quality_models']);
        $this->assertSame($updated['quality_models']['best']['primary']['model_id'], $product->primary_model);

        $paid = new \App\Models\User();
        $paid->setRelation('plan', new \App\Models\Plan(['billing_type' => 'one_time', 'price' => 1000]));
        foreach (['standard', 'professional', 'best'] as $quality) {
            $execution = app(ModelTierService::class)->executionProductForQuality($product, $paid, $quality, 'economy');
            $this->assertSame($updated['quality_models'][$quality]['primary']['model_id'], $execution->primary_model);
            $this->assertSame($updated['quality_models'][$quality]['primary']['provider'], $execution->ai_provider);
            $this->assertSame([$updated['quality_models'][$quality]['fallback']['model_id']], $execution->fallback_models);
        }
    }

    public function test_generation_uses_the_selected_preset_even_if_a_product_has_an_old_snapshot(): void
    {
        $original = $this->completeConfiguration('old');
        $updated = $this->completeConfiguration('new');
        $preset = ModelQualityPreset::query()->create([
            'preset_key' => 'preset_selected',
            'name' => 'پیش‌فرض انتخاب‌شده',
            'configuration' => $original,
            'is_default_for_product_creation' => true,
        ]);
        $product = $this->makeProduct($preset->preset_key, $original);
        $preset->update(['configuration' => $updated]);

        $execution = app(ModelTierService::class)->executionProductForQuality($product, null, 'standard', 'free');

        $this->assertSame($original['quality_models'], $product->model_configuration['quality_models']);
        $this->assertSame($updated['free_quality_models']['standard']['primary']['model_id'], $execution->primary_model);
        $this->assertSame($updated['free_quality_models']['standard']['fallback']['model_id'], $execution->fallback_models[0]);
    }

    public function test_invalid_bulk_changes_leave_the_preset_and_products_unchanged(): void
    {
        $original = $this->completeConfiguration('old');
        $preset = ModelQualityPreset::query()->create([
            'preset_key' => 'preset_selected',
            'name' => 'پیش‌فرض انتخاب‌شده',
            'configuration' => $original,
            'is_default_for_product_creation' => true,
        ]);
        $product = $this->makeProduct($preset->preset_key, $original);
        $invalid = $original;
        $invalid['quality_models']['standard']['primary']['model_id'] = 'missing/model';
        $invalid['quality_preset_key'] = $preset->preset_key;

        try {
            app(ProductController::class)->bulkUpdateModelQualityConfiguration(Request::create(
                '/admin/products/bulk-model-quality-configuration',
                'PATCH',
                ['ids' => [$product->id], 'model_configuration' => $invalid, 'update_preset' => true]
            ));
            $this->fail('An invalid model must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('model_configuration.quality_models.standard.primary.model_id', $exception->errors());
        }

        $this->assertSame($original['quality_models'], $preset->fresh()->configuration['quality_models']);
        $this->assertSame($original['quality_models'], $product->fresh()->model_configuration['quality_models']);
    }

    private function makeProduct(string $presetKey, array $configuration): Product
    {
        return Product::query()->create([
            'model_configuration' => array_replace($configuration, ['quality_preset_key' => $presetKey]),
            'primary_model' => 'old/quality_models-best-primary',
            'ai_provider' => 'openrouter',
            'fallback_models' => ['old/quality_models-best-fallback'],
            'fallback_model_providers' => ['openrouter'],
            'pricing_model' => 'per_credit',
            'credit_cost' => 12,
        ]);
    }

    private function completeConfiguration(string $prefix): array
    {
        $configuration = ['quality_models' => [], 'free_quality_models' => []];
        $definitions = [
            ['quality_models', 'standard'],
            ['quality_models', 'professional'],
            ['quality_models', 'best'],
            ['free_quality_models', 'standard'],
            ['free_quality_models', 'best'],
        ];

        foreach ($definitions as $index => [$group, $quality]) {
            $primaryId = "{$prefix}/{$group}-{$quality}-primary";
            $fallbackId = "{$prefix}/{$group}-{$quality}-fallback";
            foreach ([$primaryId, $fallbackId] as $modelId) {
                AiModel::query()->create([
                    'name' => $modelId,
                    'openrouter_model_id' => $modelId,
                    'provider' => $index % 2 === 0 ? 'openrouter' : 'fal',
                    'is_active' => true,
                    'featured_in_lab' => true,
                ]);
            }

            $provider = $index % 2 === 0 ? 'openrouter' : 'fal';
            $configuration[$group][$quality] = [
                'primary' => ['model_id' => $primaryId, 'provider' => $provider],
                'fallback' => ['model_id' => $fallbackId, 'provider' => $provider],
            ];
        }

        return $configuration;
    }
}
