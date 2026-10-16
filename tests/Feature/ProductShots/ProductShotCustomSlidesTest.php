<?php

namespace Tests\Feature\ProductShots;

use App\Models\Admin;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ShotLibrary;
use App\Models\TelegramProductSetting;
use App\Services\ProductShots\ProductShotFeature;
use App\Services\ProductShots\ShotVisionService;
use App\Services\TelegramProduct\ProductShotBotSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * اسلاید اختصاصی (عکس نمونه + پرامپت) و «عکس ورودی» در فرم داشبورد، صفحه‌ی محصول و تنظیمات بات.
 */
class ProductShotCustomSlidesTest extends TestCase
{
    use RefreshDatabase;
    use ProductShotTestHelpers;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        ProductShotFeature::resetSchemaCache();
        ShotLibrary::flushSchemaCache();
        $this->admin = Admin::query()->forceCreate(['name' => 'رهبر', 'email' => 'l' . uniqid() . '@x.test', 'password' => bcrypt('x'), 'role' => 'leader', 'is_active' => true]);
    }

    private function payload(array $extra = []): array
    {
        $model = AiModel::query()->where('is_active', true)->where('output_modality', 'image')->where('supports_image_input', true)->where('provider', 'openrouter')->firstOrFail();
        $category = Category::query()->first() ?? Category::query()->forceCreate(['name' => 'آرایشی', 'name_fa' => 'آرایشی', 'name_en' => 'Beauty', 'slug' => 'beauty-' . uniqid()]);

        return array_merge([
            'name_fa' => 'پک اختصاصی سرم', 'name_en' => 'Custom Serum Pack', 'status' => 'active',
            'occupation_ids' => [Occupation::query()->firstOrFail()->id], 'category_ids' => [$category->id],
            'quality_models' => [
                'standard' => ['primary_id' => $model->id],
                'professional' => ['primary_id' => $model->id],
                'best' => ['primary_id' => $model->id],
            ],
            'preflight_min_side' => 900, 'product_sheet_size' => 2048,
            'preflight_blocking_issues' => ShotVisionService::BLOCKING,
            'explore_tiles' => ['1x1'],
            'shots' => [],
        ], $extra);
    }

    public function test_dashboard_creates_custom_slides_with_long_prompts_and_input_image(): void
    {
        $this->enableShots('public');
        $longPrompt = str_repeat('Hero shot of the exact serum bottle on wet black stone, soft rim light, water droplets. ', 280);
        $this->assertGreaterThan(20000, mb_strlen($longPrompt));

        $this->actingAs($this->admin, 'admin')
            ->get(route('admin.product-shots.products.create'))
            ->assertOk()
            ->assertSee('عکس ورودی')
            ->assertSee('data-add-custom-slide', false)
            ->assertSee('sp-custom-slide-template', false)
            ->assertDontSee('انتخاب پیش‌فرض');

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.store'), $this->payload([
            'input_image' => $this->productImage('input.jpg'),
            'new_slides' => [
                'nA' => ['name' => 'سنگ خیس', 'prompt' => $longPrompt, 'enabled' => '1', 'sort' => 1, 'sample' => $this->productImage('a.jpg'),
                    'quality_credits' => ['standard' => 10, 'professional' => 14, 'best' => 20], 'allowed_aspect_ratios' => ['4:5', '1:1'], 'aspect_ratio_default' => '1:1'],
                'nB' => ['name' => 'نور صبح', 'prompt' => 'Morning light flat-lay of the exact product with citrus slices.', 'enabled' => '1', 'sort' => 0,
                    'quality_credits' => ['standard' => 8], 'allowed_aspect_ratios' => ['4:5']],
                'nInput' => ['input_image_slide' => '1', 'enabled' => '1', 'sort' => 2],
            ],
        ]))->assertOk()->assertJson(['ok' => true]);

        $product = Product::query()->where('name_en', 'Custom Serum Pack')->firstOrFail();
        $shots = ProductShot::query()->where('product_id', $product->id)->with('shot')->orderBy('sort')->get();
        $this->assertCount(3, $shots);
        $this->assertSame('نور صبح', $shots[0]->displayName());
        $this->assertSame(trim($longPrompt), $shots[1]->prompt_override, 'پرامپت بلند کامل ذخیره می‌شود');
        $this->assertTrue($shots[1]->shot->isCustom());
        $this->assertSame($product->id, $shots[1]->shot->product_id);
        $this->assertSame(['4:5', '1:1'], $shots[1]->allowed_aspect_ratios);
        $this->assertSame('1:1', $shots[1]->aspect_ratio_default);
        $this->assertSame(20, $shots[1]->credits('best'));
        $this->assertSame(0, $shots->where('is_default', true)->count());
        $this->assertStringStartsWith('products/input/', $product->shot_settings['input_image']);
        Storage::disk('public')->assertExists($product->shot_settings['input_image']);
        $this->assertSame(8, (int) $product->credit_cost);

        // کتابخانه‌ی عمومی و فرم محصول دیگر اسلاید اختصاصی را نشان نمی‌دهد.
        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.index'))->assertOk()->assertDontSee('سنگ خیس');
        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.products.create'))->assertOk()->assertDontSee('سنگ خیس');
        $this->actingAs($this->admin, 'admin')->get(route('admin.product-shots.products.create', $product))
            ->assertOk()->assertSee('سنگ خیس')->assertSee('اختصاصی')->assertSee('پرامپت ساخت این اسلاید');

        // صفحه‌ی محصول: عکس ورودی در گالری + مقایسه؛ صفحه‌ی ساخت: بدون عکس ورودی و بدون پیش‌انتخاب.
        $inputUrl = 'storage/' . $product->shot_settings['input_image'];
        $user = $this->makeUser();
        $this->actingAs($user)->get(route('app.pro', ['product' => $product->short_code]))
            ->assertOk()
            ->assertSee($inputUrl, false)
            ->assertSee('data-compare', false)
            ->assertSee('عکس ورودی');
        $create = $this->actingAs($user)->get(route('app.crepro', ['product' => $product->short_code]))->assertOk();
        $this->assertStringNotContainsString($inputUrl, $create->getContent());
        $this->assertStringNotContainsString('"is_default":true', $create->getContent());
    }

    public function test_updating_keeps_input_image_and_removing_a_custom_slide_deletes_its_hidden_library_row(): void
    {
        $this->enableShots('admins');
        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.store'), $this->payload([
            'status' => 'draft',
            'input_image' => $this->productImage('input.jpg'),
            'new_slides' => [
                'n1' => ['name' => 'اول', 'prompt' => 'First custom slide prompt for the product.', 'enabled' => '1', 'allowed_aspect_ratios' => ['4:5']],
                'n2' => ['name' => 'دوم', 'prompt' => 'Second custom slide prompt for the product.', 'enabled' => '1', 'allowed_aspect_ratios' => ['4:5']],
            ],
        ]))->assertOk();
        $product = Product::query()->latest('id')->firstOrFail();
        $input = $product->shot_settings['input_image'];
        [$first, $second] = ProductShot::query()->where('product_id', $product->id)->orderBy('id')->get()->all();

        $this->actingAs($this->admin, 'admin')->putJson(route('admin.product-shots.products.update', $product), $this->payload([
            'status' => 'draft',
            'shots' => [
                $first->shot_id => ['enabled' => '1', 'prompt_override' => 'Edited first prompt for the product slide.', 'allowed_aspect_ratios' => ['4:5'], 'aspect_ratio_default' => '4:5'],
                $second->shot_id => ['removed' => '1', 'allowed_aspect_ratios' => ['4:5']],
            ],
        ]))->assertOk();
        $product->refresh();
        $this->assertSame($input, $product->shot_settings['input_image'], 'بدون آپلود جدید، عکس ورودی حفظ می‌شود');
        $this->assertSame('Edited first prompt for the product slide.', $first->fresh()->prompt_override);
        $this->assertNull($second->fresh());
        $this->assertNull(ShotLibrary::query()->find($second->shot_id));
        $this->assertNotNull(ShotLibrary::query()->find($first->shot_id));

        // حذف عکس ورودی
        $this->actingAs($this->admin, 'admin')->putJson(route('admin.product-shots.products.update', $product), $this->payload([
            'status' => 'draft', 'remove_input_image' => '1',
            'shots' => [$first->shot_id => ['enabled' => '1', 'allowed_aspect_ratios' => ['4:5']]],
        ]))->assertOk();
        $this->assertNull($product->fresh()->shot_settings['input_image']);
        Storage::disk('public')->assertMissing($input);
    }

    public function test_custom_slide_requires_prompt_and_at_least_one_enabled_slide(): void
    {
        $this->enableShots('admins');
        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.store'), $this->payload([
            'new_slides' => ['n1' => ['name' => 'بدون پرامپت', 'prompt' => '', 'allowed_aspect_ratios' => ['4:5']]],
        ]))->assertStatus(422)->assertJsonValidationErrors(['new_slides.n1.prompt']);

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.store'), $this->payload())
            ->assertStatus(422)->assertJsonValidationErrors(['shots']);
    }

    public function test_input_image_slide_needs_no_prompt_and_is_not_a_user_build_option(): void
    {
        $this->enableShots('public');

        $this->actingAs($this->admin, 'admin')->postJson(route('admin.product-shots.products.store'), $this->payload([
            'input_image' => $this->productImage('input-only.jpg'),
            'new_slides' => [
                'input' => ['input_image_slide' => '1', 'enabled' => '1'],
                'output' => ['name' => 'نمای محصول', 'prompt' => 'Create a clean product hero image for the product.', 'enabled' => '1', 'allowed_aspect_ratios' => ['4:5']],
            ],
        ]))->assertOk()->assertJson(['ok' => true]);

        $product = Product::query()->where('name_en', 'Custom Serum Pack')->firstOrFail();
        $inputSlide = ProductShot::query()->where('product_id', $product->id)->whereHas('shot', fn ($query) => $query->where('name_fa', 'عکس ورودی'))->firstOrFail();
        $this->assertSame('عکس ورودی', $inputSlide->displayName());
        $this->assertTrue($inputSlide->optionEnabled('input_image_slide', false));
        $this->assertNull($inputSlide->prompt_override);

        $packs = app(\App\Services\ProductShots\ShotPackService::class);
        $this->assertNotContains('عکس ورودی', array_column($packs->shotCards($product), 'name'));
        $this->assertSame('input', $packs->productGallery($product)->first()['kind']);
        $this->assertStringContainsString('عکس ورودی', $packs->productGallery($product)->first()['label']);
    }

    public function test_productshot_bot_settings_page_renders_and_saves(): void
    {
        $this->actingAs($this->admin, 'admin')->get(route('admin.settings.telegram.product-bot'))
            ->assertOk()->assertSee('پروداکتی')->assertSee(route('admin.settings.telegram.product-bot.productshot'), false);

        $this->actingAs($this->admin, 'admin')->get(route('admin.settings.telegram.product-bot.productshot'))
            ->assertOk()
            ->assertSee('پرامپت مادر')
            ->assertSee('پرامپت اصلاح‌گر اسلایدها')
            ->assertSee('متن پیام‌های بات')
            ->assertSee('مدل‌های ساخت تصویر');

        $defaults = app(ProductShotBotSettings::class)->defaults();
        $data = [
            'ai' => ['model' => 'openai/gpt-4.1-mini', 'use_vision' => '0', 'metadata_prompt' => 'پرامپت مادر سفارشی برای ساخت نام و توضیح محصولات پروداکتی', 'optimizer_enabled' => '1', 'optimizer_prompt' => 'Custom optimizer rules: keep everything, fix grammar only, output the prompt.'],
            'slides' => ['max_slides' => 8, 'max_prompt_chars' => 15000, 'credits' => ['standard' => 9, 'professional' => 13, 'best' => 19], 'allowed_aspect_ratios' => ['1:1', '4:5'], 'aspect_ratio_default' => '9:16', 'aspect_ratio_user_selectable' => '0'],
            'models' => ['quality' => ['standard' => ['primary_id' => ''], 'professional' => ['primary_id' => ''], 'best' => ['primary_id' => '']]],
            'quality' => ['preflight_enabled' => '1', 'blocking_issues' => ['blur'], 'min_side' => 1000, 'product_sheet_enabled' => '0', 'product_sheet_size' => 1536],
            'display' => ['watermark_enabled' => '1', 'display_mode' => 'card', 'card_shape' => 'square', 'gallery_layout' => 'grid', 'card_label' => 'جدید', 'explore_tiles' => ['2x2']],
            'texts' => array_merge($defaults['texts'], ['input_image' => 'متن تازه‌ی عکس ورودی']),
        ];
        $this->actingAs($this->admin, 'admin')->put(route('admin.settings.telegram.product-bot.productshot.update'), $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.telegram.product-bot.productshot'));

        $settings = new ProductShotBotSettings();
        $this->assertSame('openai/gpt-4.1-mini', $settings->get('ai.model'));
        $this->assertFalse($settings->get('ai.use_vision'));
        $this->assertSame(8, $settings->maxSlides());
        $this->assertSame(['standard' => 9, 'professional' => 13, 'best' => 19], $settings->slideCredits());
        $this->assertSame(['4:5', '1:1'], $settings->get('slides.allowed_aspect_ratios'));
        $this->assertSame('4:5', $settings->get('slides.aspect_ratio_default'), 'نسبت پیش‌فرض باید جزو نسبت‌های مجاز باشد');
        $this->assertSame(['blur'], $settings->get('quality.blocking_issues'));
        $this->assertSame(['2x2'], $settings->get('display.explore_tiles'));
        $this->assertSame('متن تازه‌ی عکس ورودی', $settings->text('input_image'));
        $this->assertNotNull(TelegramProductSetting::value(ProductShotBotSettings::KEY));

        // خالی کردن یک متن مجاز نیست.
        $data['texts']['review'] = '';
        $this->actingAs($this->admin, 'admin')->from(route('admin.settings.telegram.product-bot.productshot'))
            ->put(route('admin.settings.telegram.product-bot.productshot.update'), $data)
            ->assertSessionHasErrors('texts.review');

        // فقط رهبر پنل
        $staff = Admin::query()->forceCreate(['name' => 'کارمند', 'email' => 's' . uniqid() . '@x.test', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);
        $this->actingAs($staff, 'admin')->get(route('admin.settings.telegram.product-bot.productshot'))->assertForbidden();
    }
}
