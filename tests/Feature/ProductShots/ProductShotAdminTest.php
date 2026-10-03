<?php

namespace Tests\Feature\ProductShots;

use App\Models\Admin;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ShotLibrary;
use App\Services\ProductShots\ProductShotFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductShotAdminTest extends TestCase
{
    use RefreshDatabase;
    use ProductShotTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        ProductShotFeature::resetSchemaCache();
        $this->admin = Admin::query()->forceCreate(['name' => 'مدیر', 'email' => 'a' . uniqid() . '@x.test', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);
    }

    private function imageModel(): AiModel
    {
        return AiModel::query()->where('is_active', true)->where('output_modality', 'image')->where('supports_image_input', true)
            ->where('provider', 'openrouter')->firstOrFail();
    }

    private function category(): Category
    {
        return Category::query()->first() ?? Category::query()->forceCreate(['name' => 'آرایشی', 'name_fa' => 'آرایشی', 'name_en' => 'Beauty', 'slug' => 'beauty-' . uniqid()]);
    }

    public function test_index_renders_and_settings_can_turn_feature_on(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.index'))
            ->assertOk()->assertSee('استودیو محصول')->assertSee('خاموش');

        $this->actingAs($this->admin, 'admin')->put(route('admin.product-shots.settings.update'), [
            'enabled' => '1', 'audience' => 'whitelist', 'whitelist_user_ids' => '5, 9', 'whitelist_phones' => '09121112233',
            'max_shots_per_run' => 6, 'client_concurrency' => 1, 'daily_cost_cap_usd' => 3, 'credit_price_toman' => 585,
            'preflight_enabled' => '1', 'qc_enabled' => '1',
            'preflight_min_side' => 900, 'product_sheet_size' => 2048, 'product_sheet_enabled' => '1',
        ])->assertRedirect(route('admin.product-shots.index'));

        $settings = \App\Models\ProductShotSetting::query()->first();
        $this->assertTrue($settings->enabled);
        $this->assertSame([5, 9], $settings->whitelist_user_ids);
        $this->assertFalse($settings->qc_auto_retry);
        $this->assertTrue(app(ProductShotFeature::class)->enabled());
    }

    public function test_product_form_is_hidden_while_module_is_off(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.products.create'))->assertNotFound();
    }

    public function test_legacy_create_page_shows_mode_dialog_only_when_enabled(): void
    {
        $off = $this->actingAs($this->admin, 'admin')->get(route('admin.products.create'));
        $off->assertOk()->assertDontSee('ps-mode-dialog', false)->assertDontSee('ثبت محصول پروداکتی');

        $this->enableShots('admins');
        $this->actingAs($this->admin, 'admin')->get(route('admin.products.create'))
            ->assertOk()->assertSee('ps-mode-dialog', false)->assertSee('ثبت محصول پروداکتی')->assertSee('ثبت محصول چهره‌محور');

        // انتخاب چهره‌محور همان فرم قدیمی را بدون نمایش دوباره‌ی انتخاب‌گر باز می‌کند.
        $this->actingAs($this->admin, 'admin')->get(route('admin.products.create', ['mode' => 'portrait']))
            ->assertOk()->assertDontSee('ps-mode-dialog', false)->assertSee('ثبت محصول عکس');
    }

    public function test_shot_library_crud(): void
    {
        $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.library.store'), [
            'name_fa' => 'شات تست', 'name_en' => 'Test Shot', 'category' => 'detail', 'default_credits' => 15,
            'aspect_ratio_default' => '1:1', 'tokens' => ['framing' => 'closeup-macro', 'props' => ['bubbles', 'nope'], 'lighting' => ['rim-light']],
        ])->assertSessionHasErrors('tokens.props.1');

        $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.library.store'), [
            'name_fa' => 'شات تست', 'name_en' => 'Test Shot', 'category' => 'detail', 'default_credits' => 15,
            'aspect_ratio_default' => '1:1', 'tokens' => ['framing' => 'closeup-macro', 'props' => ['bubbles'], 'lighting' => ['rim-light']],
            'sample' => UploadedFile::fake()->image('s.jpg', 400, 500),
        ])->assertRedirect();

        $shot = ShotLibrary::query()->where('key', 'test-shot')->firstOrFail();
        $this->assertSame(['bubbles'], $shot->tokens['props']);
        $this->assertNotNull($shot->sample_image);

        $this->actingAs($this->admin, 'admin')->put(route('admin.product-shots.library.update', $shot), [
            'key' => 'test-shot', 'name_fa' => 'شات ویرایش‌شده', 'category' => 'hero', 'default_credits' => 20, 'aspect_ratio_default' => '4:5',
            'tokens' => ['mood' => 'editorial'],
        ])->assertRedirect();
        $this->assertSame('شات ویرایش‌شده', $shot->fresh()->name_fa);
        $this->assertSame(['mood' => 'editorial'], $shot->fresh()->tokens);

        $this->actingAs($this->admin, 'admin')->patch(route('admin.product-shots.library.toggle', $shot))->assertRedirect();
        $this->assertFalse($shot->fresh()->is_active);
    }

    public function test_store_and_update_shot_product(): void
    {
        $this->enableShots('admins');
        $shots = ShotLibrary::query()->ordered()->take(3)->get();
        $model = $this->imageModel();
        $cat = $this->category();
        $occupation = Occupation::query()->firstOrFail();

        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.products.create'))
            ->assertOk()
            ->assertSee('data-step-panel="5"', false)
            ->assertSee('حفظ هویت برند')
            ->assertSee($shots[0]->name_fa);

        $payload = [
            'name_fa' => 'پک سرم', 'name_en' => 'Serum Pack', 'status' => 'active',
            'occupation_ids' => [$occupation->id], 'category_ids' => [$cat->id], 'product_description' => 'amber serum bottle',
            'quality_models' => [
                'standard' => ['primary_id' => $model->id],
                'professional' => ['primary_id' => $model->id],
                'best' => ['primary_id' => $model->id],
            ],
            'preflight_enabled' => '1', 'preflight_min_side' => 900, 'product_sheet_enabled' => '1', 'product_sheet_size' => 2048,
            'brand_identity_enabled' => '1',
            'brand_identity_prompt' => 'Keep the brand palette warm and minimal.',
            'explore_tiles' => ['1x1', '1x2'],
            'shots' => [
                $shots[0]->id => ['enabled' => '1', 'is_default' => '1', 'credits' => '', 'quality_credits' => ['standard'=>10,'professional'=>15,'best'=>20], 'allowed_aspect_ratios' => ['4:5','1:1'], 'aspect_ratio_default' => '4:5', 'sort' => 0],
                $shots[1]->id => ['enabled' => '1', 'is_default' => '0', 'credits' => '8', 'quality_credits' => ['standard'=>8,'professional'=>12,'best'=>18], 'allowed_aspect_ratios' => ['4:5','9:16'], 'aspect_ratio_default' => '4:5', 'aspect_ratio_user_selectable' => '1', 'sort' => 1],
                $shots[2]->id => ['enabled' => '0', 'is_default' => '0', 'credits' => '', 'quality_credits' => ['standard'=>10,'professional'=>15,'best'=>20], 'allowed_aspect_ratios' => ['4:5'], 'aspect_ratio_default' => '4:5', 'sort' => 2],
            ],
        ];
        $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.products.store'), $payload)->assertRedirect();

        $product = Product::query()->where('slug', 'serum-pack')->firstOrFail();
        $this->assertTrue($product->isShotProduct());
        $this->assertSame($model->openrouter_model_id, $product->primary_model);
        $this->assertSame('amber serum bottle', $product->shot_settings['product_description']);
        $this->assertTrue($product->shot_settings['brand_identity_enabled']);
        $this->assertSame('Keep the brand palette warm and minimal.', $product->shot_settings['brand_identity_prompt']);
        $this->assertSame(8, (int) $product->credit_cost);
        $this->assertSame((int) $this->admin->id, (int) $product->created_by);
        $this->assertSame(2, $product->enabledProductShots()->count());
        $this->assertSame([$cat->id], $product->categories()->pluck('categories.id')->all());
        $this->assertSame([$occupation->id], $product->occupations()->pluck('occupations.id')->all());
        $this->assertSame(15, $product->productShots()->where('shot_id', $shots[0]->id)->firstOrFail()->credits('professional'));

        // ویرایش از فرم قدیمی به فرم جدید هدایت می‌شود
        $this->actingAs($this->admin, 'admin')->get(route('admin.products.create', $product))
            ->assertRedirect(route('admin.product-shots.products.create', $product->id));

        $payload['shots'][$shots[1]->id]['enabled'] = '0';
        $payload['name_fa'] = 'پک سرم ۲';
        $this->actingAs($this->admin, 'admin')->put(route('admin.product-shots.products.update', $product), $payload)->assertRedirect();
        $this->assertSame('پک سرم ۲', $product->fresh()->name_fa);
        $this->assertSame(1, $product->fresh()->enabledProductShots()->count());
        $this->assertSame('serum-pack', $product->fresh()->slug);
    }

    public function test_publishing_requires_category(): void
    {
        $this->enableShots('admins');
        $shot = ShotLibrary::query()->firstOrFail();
        $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.products.store'), [
            'name_fa' => 'x', 'name_en' => 'x', 'status' => 'active',
            'occupation_ids' => [Occupation::query()->firstOrFail()->id],
            'quality_models' => collect(['standard','professional','best'])->mapWithKeys(fn($quality)=>[$quality=>['primary_id'=>$this->imageModel()->id]])->all(),
            'preflight_min_side' => 900, 'product_sheet_size' => 2048,
            'shots' => [$shot->id => ['enabled' => '1', 'is_default' => '1', 'quality_credits'=>['standard'=>10,'professional'=>15,'best'=>20], 'allowed_aspect_ratios'=>['4:5'], 'aspect_ratio_default'=>'4:5']],
            'explore_tiles' => ['1x1'],
        ])->assertSessionHasErrors('category_ids');
    }

    public function test_preview_costs_no_credits_and_sample_can_be_saved(): void
    {
        $this->enableShots('admins');
        $this->mockRouter([], 0.025);
        $this->mockVision();
        $shot = ShotLibrary::query()->firstOrFail();
        $product = $this->makeShotProduct(1);

        $res = $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.preview'), [
            'shot_id' => $shot->id, 'ai_model_id' => $this->imageModel()->id, 'image' => UploadedFile::fake()->image('t.jpg', 800, 1000),
        ], ['Accept' => 'application/json'])->assertOk()->json();

        $this->assertTrue($res['ok']);
        $this->assertEqualsWithDelta(0.025, $res['cost_usd'], 0.0001);
        $this->assertSame(0, \App\Models\Order::query()->count());

        // استفاده‌ی دوباره از همان عکس تست بدون آپلود مجدد
        $this->actingAs($this->admin, 'admin')->post(route('admin.product-shots.preview'), [
            'shot_id' => $shot->id, 'ai_model_id' => $this->imageModel()->id, 'image_path' => $res['source_path'],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.sample', [$product, $shot]), ['image_path' => $res['image_path']])->assertOk();
        $sample = ProductShot::query()->where('product_id', $product->id)->where('shot_id', $shot->id)->value('sample_image');
        $this->assertStringStartsWith('products/shot-samples/', $sample);
        Storage::disk('public')->assertExists($sample);
    }

    public function test_sample_copy_rejects_path_traversal(): void
    {
        $this->enableShots('admins');
        Storage::disk('public')->put('secret.txt', 'x');
        $shot = ShotLibrary::query()->firstOrFail();
        $product = $this->makeShotProduct(1);

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.sample', [$product, $shot]), ['image_path' => '../secret.txt'])->assertStatus(422);
        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.sample', [$product, $shot]), ['image_path' => 'secret.txt'])->assertStatus(422);
    }

    public function test_guests_cannot_reach_admin_pages(): void
    {
        $this->get(route('admin.product-shots.index'))->assertRedirect();
    }
}
