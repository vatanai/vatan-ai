<?php

namespace Tests\Feature;

use App\Http\Controllers\TelegramProductDirectWebhookController;
use App\Jobs\ProcessTelegramProductDraftJob;
use App\Models\Category;
use App\Models\Occupation;
use App\Models\Product;
use App\Models\ProductShot;
use App\Models\TelegramProductDraft;
use App\Models\TelegramProductEvent;
use App\Models\TelegramProductManager;
use App\Services\OpenRouterService;
use App\Services\ProductShots\ProductShotFeature;
use App\Services\ProductShots\ShotProductRegistrar;
use App\Services\TelegramProductDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class TelegramProductShotBotFlowTest extends TestCase
{
    use RefreshDatabase;

    private int $updateId = 5000;
    private TelegramProductManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Bus::fake([ProcessTelegramProductDraftJob::class]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]])]);
        ProductShotFeature::resetSchemaCache();
        config()->set('services.telegram_product.bot_token', 'test-token');
        config()->set('services.telegram_product.webhook_secret', 'test-secret');
        config()->set('services.telegram_product.direct_webhook_secret', 'direct_secret_123');
        $this->manager = TelegramProductManager::query()->create(['telegram_id' => 881001, 'name' => 'مدیر محصول']);
        Category::query()->forceCreate(['name' => 'آرایشی', 'name_fa' => 'آرایشی', 'name_en' => 'Beauty', 'slug' => 'beauty-cat', 'is_active' => true, 'sort_order' => 1]);
        // مدل‌های کیفیت از آخرین محصول پروداکتی خوانده می‌شوند.
        $model = ['primary_id' => 1, 'primary_model' => 'google/test-image-edit', 'primary_provider' => 'openrouter', 'fallback_ids' => [], 'fallback_models' => [], 'fallback_providers' => []];
        Product::query()->forceCreate([
            'name_fa' => 'پک قبلی', 'name_en' => 'Old pack', 'slug' => 'old-pack', 'category' => 'آرایشی', 'thumbnail' => 'x.jpg',
            'primary_model' => 'google/test-image-edit', 'prompt_template' => 'p', 'status' => 'draft', 'product_mode' => 'product',
            'product_code' => '111111', 'shot_settings' => ['quality_models' => ['standard' => $model, 'professional' => $model, 'best' => $model]],
        ]);
    }

    private function send(array $input, ?UploadedFile $image = null): array
    {
        return app(TelegramProductDraftService::class)->handle(array_merge([
            'update_id' => ++$this->updateId,
            'telegram' => ['id' => $this->manager->telegram_id],
            'chat_id' => (string) $this->manager->telegram_id,
            'transport' => 'direct',
        ], $input), $image);
    }

    private function click(string $data): array
    {
        return $this->send(['event' => 'callback', 'callback_data' => $data, 'message_id' => '10']);
    }

    private function image(): UploadedFile
    {
        $img = imagecreatetruecolor(900, 1100);
        imagefill($img, 0, 0, imagecolorallocate($img, 230, 200, 160));
        ob_start();
        imagejpeg($img, null, 90);
        $contents = (string) ob_get_clean();

        return UploadedFile::fake()->createWithContent('cover.jpg', $contents);
    }

    /** @var array<int,string> سیستم‌پرامپت‌های فرستاده‌شده (برای بررسی پرامپت اصلاح‌گر داشبورد) */
    private array $systemPrompts = [];

    private function mockAi(?\Throwable $metadataFailure = null, bool $optimizerFails = false): void
    {
        $mock = Mockery::mock(OpenRouterService::class)->makePartial();
        $occupation = Occupation::query()->where('group_key', 'beauty')->orderBy('id')->first();
        $content = [
            'name_fa' => 'پک سرم آفتابی',
            'name_en' => 'Sunny Serum Pack',
            'description_fa' => 'عکس‌های تبلیغاتی گرم و آفتابی برای سرم‌های پوستی.',
            'tags' => ['سرم', '#آرایشی', 'عکس_محصول'],
            'slide_names' => ['سرم روی سنگ', 'قطره‌های آب', 'نور صبح'],
            'occupation_ids' => [$occupation?->id, 999999],
            'categories' => ['آرایشی', 'ناموجود'],
            'product_description' => 'a glass dropper serum bottle',
            'brand_style' => 'warm natural',
            'brand_palette' => 'orange and cream',
        ];
        $mock->shouldReceive('generateStructuredText')->andReturnUsing(function ($system, $user) use ($content, $metadataFailure, $optimizerFails) {
            $this->systemPrompts[] = (string) $system;
            if (str_contains((string) $system, '{"prompt"')) {
                if ($optimizerFails) {
                    throw new \RuntimeException('optimizer down');
                }

                return ['content' => ['prompt' => 'OPTIMIZED ' . mb_substr((string) $user, 0, 80)], 'usage' => [], 'model' => 'openai/gpt-4o-mini'];
            }
            if ($metadataFailure) {
                throw $metadataFailure;
            }

            return ['content' => $content, 'usage' => [], 'model' => 'openai/gpt-4o-mini'];
        });
        $vision = $mock->shouldReceive('analyzeImagesJson');
        $metadataFailure
            ? $vision->andThrow($metadataFailure)
            : $vision->andReturn(['content' => $content, 'usage' => [], 'model' => 'openai/gpt-4o-mini']);
        $this->app->instance(OpenRouterService::class, $mock);
        $this->app->forgetInstance(TelegramProductDraftService::class);
        $this->app->forgetInstance(\App\Services\TelegramProduct\ProductShotBotFlow::class);
    }

    private function text(string $text, int $messageId): array
    {
        return $this->send(['event' => 'message', 'text' => $text, 'message_id' => (string) $messageId]);
    }

    private function photo(int $messageId, string $caption = ''): array
    {
        return $this->send(['event' => 'photo', 'message_id' => (string) $messageId, 'caption' => $caption], $this->image());
    }

    /** اجرای کامل آماده‌سازی (هر گام یک جاب) تا بازبینی یا خطا. */
    private function runProcessing(): void
    {
        $service = app(TelegramProductDraftService::class);
        for ($i = 0; $i < 30; $i++) {
            if ($service->process($this->draft())) {
                return;
            }
        }
        $this->fail('آماده‌سازی تمام نشد.');
    }

    /** عکس ورودی + دو اسلاید کامل (پرامپت اول در دو تکه‌ی جابه‌جا رسیده) و پایان. */
    private function buildTwoSlides(): array
    {
        $this->click('ps:type:product');
        $this->photo(100);
        $this->photo(101);
        $partB = str_repeat('B', 3900);
        $partA = str_repeat('A', 4000);
        // تلگرام پیام بلند را چند تکه می‌کند؛ تکه‌ها ممکن است جابه‌جا برسند.
        $this->text($partB, 103);
        $this->text($partA, 102);
        $this->click('ps:sl:next');
        $this->photo(104, 'Second slide prompt: serum on wet stone with morning light and water drops.');

        return [$partA, $partB];
    }

    private function draft(): TelegramProductDraft
    {
        return TelegramProductDraft::query()->latest()->firstOrFail();
    }

    public function test_n8n_transport_keeps_the_classic_portrait_start(): void
    {
        $response = app(TelegramProductDraftService::class)->handle([
            'update_id' => 1, 'event' => 'callback', 'callback_data' => 'product:start',
            'telegram' => ['id' => $this->manager->telegram_id], 'chat_id' => '881001',
        ]);

        $this->assertSame('awaiting_image', $response['status']);
        $this->assertSame('portrait', $this->draft()->product_mode);
    }

    public function test_direct_transport_asks_the_product_type_and_portrait_choice_starts_the_classic_flow(): void
    {
        $choice = $this->click('product:start');
        $this->assertSame('type_choice', $choice['status']);
        $this->assertSame(0, TelegramProductDraft::query()->count());
        $callbacks = collect($choice['reply_markup']['inline_keyboard'])->flatten(1)->pluck('callback_data')->all();
        $this->assertContains('ps:type:portrait', $callbacks);
        $this->assertContains('ps:type:product', $callbacks);

        $portrait = $this->click('ps:type:portrait');
        $this->assertSame('awaiting_image', $portrait['status']);
        $this->assertSame('portrait', $this->draft()->product_mode);

        // متن منوی اصلی هم همین انتخاب را نشان می‌دهد.
        $this->assertSame('type_choice', $this->send(['event' => 'message', 'text' => 'ثبت محصول'])['status']);
    }

    public function test_failed_portrait_draft_is_no_longer_a_dead_end(): void
    {
        $service = app(TelegramProductDraftService::class);
        $started = $service->handle(['update_id' => 2, 'event' => 'callback', 'callback_data' => 'product:start', 'telegram' => ['id' => $this->manager->telegram_id], 'chat_id' => '881001']);
        $draft = TelegramProductDraft::query()->findOrFail($started['draft_id']);
        $draft->forceFill(['state' => 'failed', 'description' => 'متن قبلی محصول تست', 'image_paths' => ['products/telegram/a.webp']])->save();

        $status = $service->handle(['update_id' => 3, 'event' => 'callback', 'callback_data' => 'product:status', 'telegram' => ['id' => $this->manager->telegram_id], 'chat_id' => '881001']);
        $this->assertSame('failed', $status['status']);
        $this->assertContains('product:retry', array_column($status['buttons'], 'callback_data'));

        $retry = $service->handle(['update_id' => 4, 'event' => 'callback', 'callback_data' => 'product:retry', 'telegram' => ['id' => $this->manager->telegram_id], 'chat_id' => '881001']);
        $this->assertSame('processing', $retry['status']);
        $this->assertSame(['products/telegram/a.webp'], $draft->fresh()->image_paths);

        $draft->fresh()->forceFill(['state' => 'failed'])->save();
        $text = $service->handle(['update_id' => 5, 'event' => 'message', 'text' => 'توضیح کامل جدید برای محصول', 'telegram' => ['id' => $this->manager->telegram_id], 'chat_id' => '881001']);
        $this->assertSame('awaiting_prompt_confirmation', $text['status']);
    }

    public function test_direct_webhook_authenticates_renders_and_ignores_duplicates(): void
    {
        $update = [
            'update_id' => 9001,
            'callback_query' => [
                'id' => 'cbq-1',
                'data' => 'product:start',
                'from' => ['id' => $this->manager->telegram_id, 'first_name' => 'Mohsen'],
                'message' => ['message_id' => 55, 'chat' => ['id' => $this->manager->telegram_id, 'type' => 'private']],
            ],
        ];

        $this->postJson(route('telegram.product.direct'), $update)->assertForbidden();
        $this->postJson(route('telegram.product.direct'), $update, ['X-Telegram-Bot-Api-Secret-Token' => 'direct_secret_123'])->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/answerCallbackQuery'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/editMessageText') && str_contains((string) $request['text'], 'چه نوع محصولی'));
        $this->assertSame(1, TelegramProductEvent::query()->count());

        $this->postJson(route('telegram.product.direct'), $update, ['X-Telegram-Bot-Api-Secret-Token' => 'direct_secret_123'])->assertOk()->assertJson(['duplicate' => true]);
        $this->assertSame(1, TelegramProductEvent::query()->count());
    }

    public function test_update_normalization(): void
    {
        $photo = TelegramProductDirectWebhookController::normalize([
            'update_id' => 1,
            'message' => [
                'message_id' => 9, 'chat' => ['id' => 5, 'type' => 'private'], 'from' => ['id' => 5],
                'photo' => [['file_id' => 'small', 'width' => 90, 'file_size' => 100], ['file_id' => 'big', 'width' => 1280, 'file_size' => 90000]],
            ],
        ]);
        $this->assertSame('photo', $photo['event']);
        $this->assertSame('big', $photo['file_id']);
        $this->assertSame(90000, $photo['file_size']);

        $document = TelegramProductDirectWebhookController::normalize([
            'update_id' => 4,
            'message' => [
                'message_id' => 10, 'chat' => ['id' => 5, 'type' => 'private'], 'from' => ['id' => 5], 'caption' => 'prompt',
                'document' => ['file_id' => 'doc', 'mime_type' => 'image/PNG', 'file_size' => 5000000],
            ],
        ]);
        $this->assertSame('photo', $document['event']);
        $this->assertSame('image/png', $document['file_mime']);
        $this->assertSame(5000000, $document['file_size']);
        $this->assertSame('prompt', $document['caption']);
        $this->assertSame('direct', $photo['transport']);

        $start = TelegramProductDirectWebhookController::normalize(['update_id' => 2, 'message' => ['message_id' => 1, 'chat' => ['id' => 5, 'type' => 'private'], 'from' => ['id' => 5], 'text' => '/start abc']]);
        $this->assertSame('start', $start['event']);
        $this->assertSame('/start', $start['text']);

        $this->assertNull(TelegramProductDirectWebhookController::normalize(['update_id' => 3, 'message' => ['message_id' => 1, 'chat' => ['id' => -100, 'type' => 'group'], 'from' => ['id' => 5], 'text' => 'hi']]));
    }

    public function test_renderer_turns_portrait_buttons_into_inline_keyboard_and_keeps_main_menu_on_welcome(): void
    {
        $renderer = app(\App\Services\TelegramProduct\TelegramProductBotRenderer::class);
        $renderer->deliver([
            'chat_id' => '881001', 'text' => 'نام و توضیحات تأیید شد.', 'status' => 'awaiting_save_choice',
            'buttons' => [
                ['text' => 'ذخیره در پیش‌نویس', 'callback_data' => 'product:save_draft'],
                ['text' => 'ثبت و انتشار', 'callback_data' => 'product:publish'],
                ['text' => 'لغو فرآیند', 'callback_data' => 'product:cancel'],
            ],
            'reply_markup' => ['keyboard' => [[['text' => 'ذخیره در پیش‌نویس']]]],
        ], ['chat_id' => '881001']);
        Http::assertSent(function ($request) {
            $markup = $request['reply_markup'] ?? [];

            return str_contains($request->url(), '/sendMessage')
                && ($markup['inline_keyboard'][0][1]['callback_data'] ?? null) === 'product:publish'
                && ($markup['inline_keyboard'][1][0]['callback_data'] ?? null) === 'product:cancel';
        });

        $renderer->deliver([
            'chat_id' => '881001', 'text' => 'سلام', 'status' => 'welcome',
            'buttons' => [['text' => 'ثبت محصول', 'callback_data' => 'product:start']],
            'reply_markup' => ['keyboard' => [[['text' => 'ثبت محصول'], ['text' => 'آموزش']]], 'resize_keyboard' => true],
        ], ['chat_id' => '881001']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage') && isset($request['reply_markup']['keyboard']));
    }

    public function test_bot_images_are_optimized_like_the_dashboard_and_reported(): void
    {
        $this->click('ps:type:product');
        // عکس بزرگ «به‌صورت فایل» (PNG ۲۴۰۰×۳۰۰۰) → WebP با حداکثر ضلع ۱۶۰۰ و گزارش حجم.
        $img = imagecreatetruecolor(2400, 3000);
        imagefill($img, 0, 0, imagecolorallocate($img, 120, 160, 200));
        ob_start();
        imagepng($img);
        $png = UploadedFile::fake()->createWithContent('big.png', (string) ob_get_clean());
        $response = $this->send(['event' => 'photo', 'message_id' => '300'], $png);

        $path = $this->draft()->input_payload['pshot']['input_image'];
        $this->assertStringEndsWith('.webp', $path);
        [$width, $height, $type] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(IMAGETYPE_WEBP, $type);
        $this->assertSame(1600, max($width, $height), 'ضلع بلند مثل داشبورد به ۱۶۰۰ می‌رسد');
        $this->assertSame(1280, $width);
        $this->assertStringContainsString('عکس ورودی بهینه شد', $response['text']);
        $this->assertStringContainsString('۱۲۸۰×۱۶۰۰', $response['text']);

        // عکس کم‌کیفیت: ذخیره می‌شود ولی هشدار می‌گیرد.
        $small = imagecreatetruecolor(600, 700);
        ob_start();
        imagejpeg($small, null, 90);
        $low = $this->send(['event' => 'photo', 'message_id' => '301'], UploadedFile::fake()->createWithContent('s.jpg', (string) ob_get_clean()));
        $this->assertStringContainsString('عکس اسلاید بهینه شد', $low['text']);
        $this->assertStringContainsString('کیفیت این عکس پایین است', $low['text']);
        $this->assertStringEndsWith('.webp', $this->draft()->input_payload['pshot']['slides'][0]['image']);
    }

    public function test_oversized_or_unsupported_telegram_files_are_rejected_before_download(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 777]])]);
        $this->click('ps:type:product');

        $big = $this->send(['event' => 'photo', 'message_id' => '310', 'file_id' => 'huge', 'file_size' => 25 * 1024 * 1024, 'file_mime' => 'image/png']);
        $this->assertSame('ps_input', $big['status']);
        $this->assertStringContainsString('۲۰ مگابایت', $big['text']);

        $heic = $this->send(['event' => 'photo', 'message_id' => '311', 'file_id' => 'heic', 'file_size' => 900000, 'file_mime' => 'image/heic']);
        $this->assertStringContainsString('image/heic', $heic['text']);
        $this->assertEmpty(data_get($this->draft()->input_payload, 'pshot.input_image'));
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'getFile'));

        // خطای شبکه‌ی تلگرام فرآیند را نمی‌شکند.
        Http::fake(['api.telegram.org/*' => Http::response('down', 500)]);
        $down = $this->send(['event' => 'photo', 'message_id' => '312', 'file_id' => 'x', 'file_size' => 1000, 'file_mime' => 'image/jpeg']);
        $this->assertSame('ps_input', $down['status']);
        $this->assertStringContainsString('دریافت عکس از تلگرام ناموفق بود', $down['text']);
    }

    public function test_full_product_flow_with_long_multi_part_prompts_creates_custom_slides(): void
    {
        $this->mockAi();

        $start = $this->click('ps:type:product');
        $this->assertSame('ps_input', $start['status']);
        $this->assertTrue($this->draft()->isProductMode());

        $wrong = $this->text('اول متن می‌فرستم', 90);
        $this->assertSame('ps_input', $wrong['status']);
        $this->assertTrue($wrong['consume_input'], 'پیام مدیر همیشه پاک می‌شود');

        $input = $this->photo(100);
        $this->assertSame('ps_slides', $input['status']);
        $inputPath = $this->draft()->input_payload['pshot']['input_image'];
        $this->assertStringStartsWith('products/input/', $inputPath);
        Storage::disk('public')->assertExists($inputPath);
        $this->assertStringEndsWith('.webp', $inputPath, 'عکس بات هم مثل داشبورد بهینه (WebP) ذخیره می‌شود');

        $slidePhoto = $this->photo(101);
        $this->assertTrue($slidePhoto['consume_input']);
        $this->assertStringContainsString('پرامپت ساخت اسلاید ۱', $slidePhoto['text']);

        $partB = str_repeat('B', 3900);
        $partA = str_repeat('A', 4000);
        $this->text($partB, 103);
        $panel = $this->text($partA, 102);
        $this->assertStringContainsString('۲ بخش', $panel['text']);
        $callbacks = collect($panel['reply_markup']['inline_keyboard'])->flatten(1)->pluck('callback_data')->all();
        $this->assertContains('ps:sl:next', $callbacks);
        $this->assertContains('ps:sl:finish', $callbacks);

        $this->assertSame('ps_slides', $this->click('ps:sl:next')['status']);
        $this->photo(104, 'Second slide prompt: serum on wet stone with morning light and water drops.');

        // اسلاید سوم ناقص (فقط عکس) جلوی پایان را می‌گیرد تا حذف شود.
        $this->click('ps:sl:next');
        $this->photo(105);
        $blocked = $this->click('ps:sl:finish');
        $this->assertSame('ps_slides', $blocked['status']);
        $this->assertStringContainsString('ناقص', $blocked['text']);
        $this->click('ps:sl:drop');
        $this->assertCount(2, $this->draft()->input_payload['pshot']['slides']);

        $processing = $this->click('ps:sl:finish');
        $this->assertSame('processing', $processing['status']);
        Bus::assertDispatched(ProcessTelegramProductDraftJob::class);

        $this->runProcessing();
        $review = app(TelegramProductDraftService::class)->resultResponse($this->draft());
        $this->assertSame('ps_review', $review['status']);
        $this->assertStringContainsString('پک سرم آفتابی', $review['text']);
        $this->assertStringContainsString('پرامپت ۲ اسلاید با پرامپت اصلاح‌گر اصلاح شد', $review['text']);
        $this->assertStringNotContainsString('OPTIMIZED', $review['text'], 'پرامپت اصلاح‌شده نمایش داده نمی‌شود');
        $this->assertStringContainsString('سرم روی سنگ', $review['text']);
        $pshot = $this->draft()->input_payload['pshot'];
        $this->assertCount(1, $pshot['occupation_ids'], 'شناسه‌ی صنف نامعتبر AI حذف می‌شود');
        $this->assertCount(1, $pshot['category_ids']);

        // اصلاح نام و نام اسلاید
        $this->assertSame('ps_edit', $this->click('ps:ed:name_fa')['status']);
        $this->assertSame('ps_review', $this->text('پک سرم طلایی', 200)['status']);
        $this->click('ps:rv:slides');
        $this->click('ps:sn:1');
        $this->assertSame('ps_slide_list', $this->text('قطره و سنگ خیس', 201)['status']);
        $this->assertSame('ps_review', $this->click('ps:rv:back')['status']);

        $this->assertSame('ps_tiles', $this->click('ps:save:active')['status']);
        $this->click('ps:t:2x2');
        $saved = $this->click('ps:t:done');

        $this->assertTrue($saved['final_product']);
        $this->assertArrayHasKey('delete_message_ids', $saved);
        $product = Product::query()->findOrFail($saved['product_id']);
        $this->assertSame('product', $product->product_mode);
        $this->assertSame('active', $product->status);
        $this->assertSame('پک سرم طلایی', $product->name_fa);
        $this->assertSame(['1x1', '2x2'], $product->explore_tiles);
        $this->assertSame($inputPath, $product->shot_settings['input_image']);
        $this->assertSame('google/test-image-edit', $product->shot_settings['quality_models']['best']['primary_model']);

        $shots = ProductShot::query()->where('product_id', $product->id)->with('shot')->orderBy('sort')->get();
        $this->assertCount(2, $shots);
        $this->assertSame(0, $shots->where('is_default', true)->count(), 'هیچ اسلایدی از پیش تیک نمی‌خورد');
        $this->assertSame($partA . "\n" . $partB, $shots[0]->shot->prompt_template, 'تکه‌ها به ترتیب شناسه‌ی پیام چسبانده می‌شوند');
        $this->assertStringStartsWith('OPTIMIZED AAAA', $shots[0]->prompt_override);
        $this->assertSame('قطره و سنگ خیس', $shots[1]->displayName());
        $this->assertTrue($shots[0]->shot->isCustom());
        $this->assertSame($product->id, $shots[0]->shot->product_id);
        $this->assertSame(12, $shots[0]->credits('standard'));
        $this->assertSame(24, $shots[0]->credits('best'));
        $this->assertSame($shots[0]->sample_image, $product->cover, 'اسلاید اول کاور است');
        $this->assertStringStartsWith('products/shot-samples/', $shots[0]->sample_image);
        $this->assertSame('published', $this->draft()->state);

        // اسلاید اختصاصی در کتابخانه‌ی عمومی و پیشنهاد صنف دیده نمی‌شود.
        $this->assertFalse(app(ShotProductRegistrar::class)->shotsForGroups(['beauty'])->contains(fn ($s) => $s->isCustom()));
        // صفحه‌ی بساز همه‌ی اسلایدها را بدون پیش‌انتخاب می‌گیرد.
        $cards = app(\App\Services\ProductShots\ShotPackService::class)->shotCards($product);
        $this->assertCount(2, $cards);
        $this->assertFalse(collect($cards)->contains('is_default', true));
    }

    public function test_metadata_failure_keeps_slides_and_retry_resumes(): void
    {
        $this->mockAi(new \RuntimeException('openrouter down'), true);
        $this->buildTwoSlides();
        $this->click('ps:sl:finish');
        $this->runProcessing();

        $failed = app(TelegramProductDraftService::class)->resultResponse($this->draft());
        $this->assertSame('failed', $failed['status']);
        $slides = $this->draft()->input_payload['pshot']['slides'];
        $this->assertCount(2, $slides);
        $this->assertFalse($slides[0]['optimizer_applied'], 'خطای اصلاح‌گر پرامپت اصلی را نگه می‌دارد');

        $this->mockAi();
        $this->assertSame('processing', $this->click('ps:retry')['status']);
        $this->runProcessing();
        $this->assertSame('ps_review', $this->draft()->state);
    }

    public function test_job_runs_one_step_per_job_and_pushes_progress_then_review(): void
    {
        $this->mockAi();
        $this->buildTwoSlides();
        $this->click('ps:sl:finish');

        $job = new ProcessTelegramProductDraftJob($this->draft()->id);
        $job->handle(app(TelegramProductDraftService::class));
        $this->assertSame('processing', $this->draft()->state);
        $this->assertTrue($this->draft()->input_payload['pshot']['slides'][0]['optimized']);
        $this->assertArrayNotHasKey('optimized', $this->draft()->input_payload['pshot']['slides'][1]);
        Bus::assertDispatchedTimes(ProcessTelegramProductDraftJob::class, 2);

        for ($i = 0; $i < 5 && $this->draft()->state === 'processing'; $i++) {
            $job->handle(app(TelegramProductDraftService::class));
        }
        $this->assertSame('ps_review', $this->draft()->state);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'اصلاح پرامپت‌ها'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendPhoto') || (str_contains($request->url(), '/sendMessage') && str_contains((string) $request['text'], 'بازبینی')));
    }

    public function test_dashboard_settings_drive_texts_optimizer_and_limits(): void
    {
        $this->mockAi();
        app(\App\Services\TelegramProduct\ProductShotBotSettings::class)->save([
            'ai' => ['optimizer_enabled' => false, 'metadata_prompt' => 'MASTER PROMPT FROM DASHBOARD'],
            'slides' => ['max_prompt_chars' => 600, 'credits' => ['standard' => 7, 'professional' => 9, 'best' => 11]],
            'texts' => ['input_image' => 'متن سفارشی عکس ورودی'],
        ]);
        $this->app->forgetInstance(\App\Services\TelegramProduct\ProductShotBotSettings::class);

        $start = $this->click('ps:type:product');
        $this->assertStringContainsString('متن سفارشی عکس ورودی', $start['text']);
        $this->photo(100);
        $this->photo(101);
        $tooLong = $this->text(str_repeat('x', 700), 102);
        $this->assertFalse($tooLong['consume_input'], 'متن ردشده پاک نمی‌شود تا مدیر کوتاهش کند');
        $this->assertStringContainsString('سقف', $tooLong['text']);
        $this->text('Short but valid slide prompt for a serum bottle.', 103);
        $this->click('ps:sl:finish');
        $this->runProcessing();

        $this->assertSame('ps_review', $this->draft()->state);
        $this->assertFalse(collect($this->systemPrompts)->contains(fn ($p) => str_contains($p, '{"prompt"')), 'اصلاح‌گر خاموش است');
        $this->assertNotEmpty(collect($this->systemPrompts)->first(fn ($p) => true) ?? 'vision');
        $saved = $this->click('ps:save:draft');
        $shot = ProductShot::query()->where('product_id', $saved['product_id'])->firstOrFail();
        $this->assertSame('Short but valid slide prompt for a serum bottle.', $shot->prompt_override);
        $this->assertSame(7, $shot->credits('standard'));
        $this->assertSame('draft', Product::query()->find($saved['product_id'])->status);
    }

    public function test_review_can_add_and_delete_slides_and_only_new_slides_are_processed(): void
    {
        $this->mockAi();
        $this->buildTwoSlides();
        $this->click('ps:sl:finish');
        $this->runProcessing();
        $this->click('ps:ed:name_fa');
        $this->text('نام دستی من', 300);

        $this->click('ps:rv:slides');
        $this->assertSame('ps_slides', $this->click('ps:sl:add')['status']);
        $this->photo(301);
        $this->text('Third slide prompt: serum bottle on a pastel podium with soft shadow.', 302);
        $this->assertSame('processing', $this->click('ps:sl:finish')['status']);
        $before = count($this->systemPrompts);
        $this->runProcessing();

        $this->assertSame('ps_review', $this->draft()->state);
        $this->assertSame('نام دستی من', $this->draft()->ai_result['name_fa'], 'اصلاحات دستی حفظ می‌شود');
        $optimizerCalls = collect(array_slice($this->systemPrompts, $before))->filter(fn ($p) => str_contains($p, '{"prompt"'))->count();
        $this->assertSame(1, $optimizerCalls, 'فقط اسلاید جدید اصلاح می‌شود');
        $slides = $this->draft()->input_payload['pshot']['slides'];
        $this->assertCount(3, $slides);
        $this->assertSame('نور صبح', $slides[2]['name']);

        $this->click('ps:rv:slides');
        $this->click('ps:sd:0');
        $this->assertCount(2, $this->draft()->input_payload['pshot']['slides']);
        $this->click('ps:sd:0');
        $last = $this->click('ps:sd:0');
        $this->assertStringContainsString('حداقل یک اسلاید', $last['text']);
    }

    public function test_cancel_removes_uploaded_files_and_lists_messages_to_clean(): void
    {
        $this->mockAi();
        $this->click('ps:type:product');
        $this->photo(100);
        $this->photo(101);
        $paths = [$this->draft()->input_payload['pshot']['input_image'], $this->draft()->input_payload['pshot']['slides'][0]['image']];
        app(TelegramProductDraftService::class)->rememberBotMessage($this->draft()->id, '555');
        $cancel = $this->click('ps:x');
        $this->assertSame('cancelled', $cancel['status']);
        $this->assertContains('555', $cancel['delete_message_ids']);
        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_renderer_deletes_consumed_user_messages_and_edits_panel_for_progress(): void
    {
        $renderer = app(\App\Services\TelegramProduct\TelegramProductBotRenderer::class);
        $renderer->deliver(['chat_id' => '881001', 'text' => 'پنل', 'status' => 'ps_slides', 'consume_input' => true], ['chat_id' => '881001', 'incoming_message_id' => '4321']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/deleteMessage') && (int) $request['message_id'] === 4321);

        $renderer->deliver(['chat_id' => '881001', 'text' => 'پیشرفت', 'status' => 'processing', 'edit_panel' => true], ['chat_id' => '881001']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/editMessageText') && (int) $request['message_id'] === 777);
    }

    public function test_legacy_open_product_draft_is_closed_safely(): void
    {
        $this->mockAi();
        $this->click('ps:type:product');
        $this->draft()->forceFill(['state' => 'ps_brief'])->save();
        $response = $this->text('متن قدیمی', 50);
        $this->assertSame('cancelled', $response['status']);
        $this->assertSame('cancelled', $this->draft()->state);
    }
}
