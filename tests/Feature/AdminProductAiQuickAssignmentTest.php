<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\ModelTierService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductAiQuickAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response(['lastTradePrice' => '900000'], 200)]);
        $this->admin = Admin::query()->where('is_active', true)->firstOrFail();
    }

    public function test_single_product_ai_model_is_saved_without_touching_registration_structure(): void
    {
        $product = $this->makeProduct('single-ai');
        $originalStructure = $product->only(['prompt_template', 'fallback_models', 'input_schema']);
        $model = AiModel::query()->selectableForProduct()->where('provider', 'replicate')->firstOrFail();

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
        $model = AiModel::query()->selectableForProduct()->where('provider', 'openrouter')->firstOrFail();

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
        $validModel = AiModel::query()->selectableForProduct()->where('provider', 'openrouter')->firstOrFail();
        $valid = $this->makeProduct('valid-ai-filter', $validModel->provider, $validModel->openrouter_model_id);
        $invalid = $this->makeProduct('invalid-ai-filter', 'openrouter', 'missing/model-id');

        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.products', ['ai_status' => 'invalid']));

        $response->assertOk()
            ->assertSee('data-row-id="' . $invalid->id . '"', false)
            ->assertDontSee('data-row-id="' . $valid->id . '"', false)
            ->assertSee('مدل نامعتبر');
    }

    public function test_a_complete_product_can_still_be_registered_locally(): void
    {
        Storage::fake('public');
        $category = Category::query()->firstOrFail();
        $model = AiModel::query()->selectableForProduct()->where('provider', 'replicate')->firstOrFail();
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
                'model_configuration' => [
                    'quality_credit_preset_key' => 'custom',
                    'quality_credit_costs' => [
                        'standard' => 7,
                        'professional' => 17,
                        'best' => 37,
                    ],
                ],
                // مقادیر قدیمی عمداً متناقض ارسال می‌شوند تا ثابت شود بک‌اند
                // فقط «مصرف اعتبار محصول» را منبع قطعی می‌داند.
                'pricing_model' => 'free',
                'credit_cost' => 999,
                'new_min_credit_required' => 999,
                'new_max_run_per_user' => 999,
                'new_show_free_badge' => 1,
                'new_price_custom_label' => 'نباید ذخیره شود',
                'main_images' => [UploadedFile::fake()->image('local-product.jpg', 800, 800)],
                'skip_image_optimization' => 1,
                'special_features_enabled' => 0,
                'identity_preservation' => 0,
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk()->assertJsonPath('ok', true);
        $this->assertDatabaseHas('products', [
            'slug' => $slug,
            'ai_provider' => $model->provider,
            'status' => 'active',
            'pricing_model' => 'per_credit',
            'credit_cost' => 7,
            'new_min_credit_required' => 0,
            'new_max_run_per_user' => null,
            'new_show_free_badge' => 0,
            'new_price_custom_label' => null,
        ]);
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $this->assertTrue(AiModel::query()->selectableForProduct()
            ->where('provider', $product->ai_provider)
            ->where('openrouter_model_id', $product->primary_model)
            ->exists());
        $this->assertSame([
            'standard' => 7,
            'professional' => 17,
            'best' => 37,
        ], $product->qualityCreditCosts());
    }

    public function test_product_update_ignores_legacy_pricing_inputs_and_preserves_legacy_metadata(): void
    {
        $product = $this->makeProduct('legacy-pricing-update');
        $product->forceFill([
            'new_min_credit_required' => 2,
            'new_max_run_per_user' => 3,
            'new_show_free_badge' => true,
            'new_price_custom_label' => 'مقدار قبلی',
        ])->save();

        $response = $this->actingAs($this->admin, 'admin')->put(
            route('admin.products.update', $product),
            [
                'name_fa' => $product->name_fa,
                'name_en' => $product->name_en,
                'slug' => $product->slug,
                'status' => 'draft',
                'special_features_enabled' => 0,
                'identity_preservation' => 0,
                'model_configuration' => [
                    'quality_credit_preset_key' => 'custom',
                    'quality_credit_costs' => [
                        'standard' => 9,
                        'professional' => 19,
                        'best' => 39,
                    ],
                ],
                'pricing_model' => 'free',
                'credit_cost' => 777,
                'new_min_credit_required' => 777,
                'new_max_run_per_user' => 777,
                'new_show_free_badge' => 0,
                'new_price_custom_label' => 'مقدار مخرب',
            ],
            ['Accept' => 'application/json']
        );

        $response->assertOk()->assertJsonPath('ok', true);
        $product->refresh();
        $this->assertSame('per_credit', $product->pricing_model);
        $this->assertSame(9, $product->credit_cost);
        $this->assertSame(2, $product->new_min_credit_required);
        $this->assertSame(3, $product->new_max_run_per_user);
        $this->assertTrue((bool) $product->new_show_free_badge);
        $this->assertSame('مقدار قبلی', $product->new_price_custom_label);
        $this->assertSame([
            'standard' => 9,
            'professional' => 19,
            'best' => 39,
        ], $product->qualityCreditCosts());
    }

    public function test_registration_form_moves_legacy_pricing_to_future_updates(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.products.create'));

        $response->assertOk()
            ->assertSee('مصرف اعتبار محصول')
            ->assertSee('قیمت‌گذاری قدیمی')
            ->assertSee('data-legacy-pricing-update', false)
            ->assertDontSee('name="pricing_model"', false)
            ->assertDontSee('name="credit_cost"', false)
            ->assertDontSee('legacy-pricing-toggle', false);
    }

    public function test_step_four_completion_mirrors_step_two_without_legacy_credit_gate(): void
    {
        $response = $this->actingAs($this->admin, 'admin')->get(route('admin.products.create'));

        $response->assertOk()
            ->assertSee('id="step-tab-4" data-completion-mirrors-step="2"', false)
            ->assertDontSee('name="credit_cost"', false);

        $script = file_get_contents(public_path('admin/js/products-create.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('function completionMirrorSourceStep(n)', $script);
        $this->assertStringContainsString('const mirroredFrom = completionMirrorSourceStep(n);', $script);
        $this->assertStringContainsString('4: [], // وضعیت تکمیل این گام', $script);
        $this->assertStringNotContainsString("4: [ ['credit_cost'", $script);
    }

    public function test_step_two_registration_and_product_list_editor_share_the_same_three_quality_models(): void
    {
        Storage::fake('public');
        $category = Category::query()->firstOrFail();
        $models = AiModel::query()->selectableForProduct()->limit(6)->get()->values();
        $this->assertCount(6, $models, 'برای آزمون یکپارچگی کیفیت‌ها حداقل شش مدل فعال لازم است.');

        $pair = static fn (AiModel $primary, AiModel $fallback): array => [
            'primary' => [
                'model_id' => $primary->openrouter_model_id,
                'provider' => $primary->provider,
            ],
            'fallback' => [
                'model_id' => $fallback->openrouter_model_id,
                'provider' => $fallback->provider,
            ],
        ];
        $registrationConfiguration = [
            'quality_preset_key' => 'custom',
            'quality_architecture_enabled' => true,
            'quality_models' => [
                'standard' => $pair($models[0], $models[1]),
                'professional' => $pair($models[2], $models[3]),
                'best' => $pair($models[4], $models[5]),
            ],
            'free_quality_models' => [
                'standard' => $pair($models[0], $models[1]),
                'best' => $pair($models[4], $models[5]),
            ],
            'quality_credit_costs' => [
                'standard' => 12,
                'professional' => 20,
                'best' => 50,
            ],
        ];
        $slug = 'quality-integration-' . uniqid();

        $registration = $this->actingAs($this->admin, 'admin')->post(
            route('admin.products.store'),
            [
                'name_fa' => 'محصول آزمون یکپارچگی کیفیت',
                'name_en' => 'Quality Integration Product',
                'slug' => $slug,
                'status' => 'active',
                'category_ids' => [$category->id],
                'ai_provider' => $models[0]->provider,
                'primary_model' => $models[0]->openrouter_model_id,
                'prompt_template' => 'Create a professional image of {prompt}',
                'model_configuration' => $registrationConfiguration,
                'main_images' => [UploadedFile::fake()->image('quality-integration.jpg', 800, 800)],
                'skip_image_optimization' => 1,
                'special_features_enabled' => 0,
                'identity_preservation' => 0,
            ],
            ['Accept' => 'application/json']
        );

        $registration->assertOk()->assertJsonPath('ok', true);
        $product = Product::query()->where('slug', $slug)->firstOrFail();
        $this->assertEquals($registrationConfiguration['quality_models'], data_get($product->model_configuration, 'quality_models'));
        $this->assertEquals($registrationConfiguration['free_quality_models'], data_get($product->model_configuration, 'free_quality_models'));

        // همان تنظیمات از پنجره «تنظیم مدل‌های هوش مصنوعی» در لیست محصولات
        // جابه‌جا می‌شود تا ثابت شود هر دو رابط دقیقاً یک منبع داده را می‌نویسند.
        $listConfiguration = [
            ...$registrationConfiguration,
            'quality_models' => [
                'standard' => $pair($models[2], $models[3]),
                'professional' => $pair($models[4], $models[5]),
                'best' => $pair($models[0], $models[1]),
            ],
            'free_quality_models' => [
                'standard' => $pair($models[2], $models[3]),
                'best' => $pair($models[0], $models[1]),
            ],
        ];
        $listUpdate = $this->actingAs($this->admin, 'admin')->patchJson(
            route('admin.products.bulk_update_model_quality_configuration'),
            [
                'ids' => [$product->id],
                'model_configuration' => $listConfiguration,
            ]
        );

        $listUpdate->assertOk()->assertJsonPath('updated', 1);
        $product->refresh();
        $this->assertEquals($listConfiguration['quality_models'], data_get($product->model_configuration, 'quality_models'));
        $this->assertEquals($listConfiguration['free_quality_models'], data_get($product->model_configuration, 'free_quality_models'));
        $this->assertSame($listConfiguration['quality_models']['best']['primary']['model_id'], $product->primary_model);
        $this->assertSame($listConfiguration['quality_models']['best']['primary']['provider'], $product->ai_provider);

        $paidUser = new User();
        $paidUser->setRelation('plan', new Plan(['billing_type' => 'one_time', 'price' => 970000]));
        foreach (['standard', 'professional', 'best'] as $quality) {
            $execution = app(ModelTierService::class)->executionProductForQuality($product, $paidUser, $quality, 'economy');
            $this->assertSame($listConfiguration['quality_models'][$quality]['primary']['model_id'], $execution->primary_model);
            $this->assertSame($listConfiguration['quality_models'][$quality]['primary']['provider'], $execution->ai_provider);
            $this->assertSame([$listConfiguration['quality_models'][$quality]['fallback']['model_id']], $execution->fallback_models);
        }
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
