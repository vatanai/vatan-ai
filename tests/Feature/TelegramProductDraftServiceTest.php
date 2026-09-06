<?php

namespace Tests\Feature;

use App\Models\TelegramProductDraft;
use App\Models\TelegramProductManager;
use App\Services\TelegramProductDraftService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TelegramProductDraftServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('telegram_product_managers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
            $table->string('name');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('telegram_product_drafts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('telegram_product_manager_id');
            $table->unsignedBigInteger('telegram_id');
            $table->string('chat_id');
            $table->string('state');
            $table->string('pending_edit_field')->nullable();
            $table->text('description')->nullable();
            $table->json('image_paths')->nullable();
            $table->json('image_file_ids')->nullable();
            $table->json('ai_result')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('message_ids')->nullable();
            $table->string('last_message_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('telegram_product_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('update_id')->nullable()->unique();
            $table->unsignedBigInteger('telegram_product_manager_id')->nullable();
            $table->uuid('draft_id')->nullable();
            $table->string('event_type');
            $table->json('payload')->nullable();
            $table->json('response')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
        config()->set('services.telegram_product.webhook_secret', 'test-secret');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('telegram_product_events');
        Schema::dropIfExists('telegram_product_drafts');
        Schema::dropIfExists('telegram_product_managers');
        parent::tearDown();
    }

    public function test_only_an_active_manager_can_start_a_product_draft(): void
    {
        $manager = TelegramProductManager::query()->create([
            'telegram_id' => 991001,
            'name' => 'محسن آقاجانی',
            'is_active' => true,
        ]);

        $response = app(TelegramProductDraftService::class)->handle([
            'update_id' => 1001,
            'event' => 'start',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991001',
            'message_id' => 77,
        ]);

        $this->assertSame('awaiting_image', $response['status']);
        $this->assertSame('product:cancel', $response['buttons'][0]['callback_data']);
        $draft = TelegramProductDraft::query()->firstOrFail();
        $this->assertSame('awaiting_image', $draft->state);
        $this->assertSame(['77'], $draft->message_ids);

        $forbidden = app(TelegramProductDraftService::class)->handle([
            'update_id' => 1002,
            'event' => 'start',
            'telegram' => ['id' => 991002],
            'chat_id' => '991002',
        ]);
        $this->assertSame('forbidden', $forbidden['status']);
        $this->assertSame(1, TelegramProductDraft::query()->count());
    }

    public function test_the_same_update_id_returns_the_cached_response_without_creating_a_second_draft(): void
    {
        TelegramProductManager::query()->create(['telegram_id' => 991003, 'name' => 'مدیر تست']);
        $service = app(TelegramProductDraftService::class);
        $input = [
            'update_id' => 1003,
            'event' => 'start',
            'telegram' => ['id' => 991003],
            'chat_id' => '991003',
        ];

        $first = $service->handle($input);
        $second = $service->handle($input);

        $this->assertNotEmpty($first['draft_id']);
        $this->assertTrue($second['duplicate']);
        $this->assertSame($first['draft_id'], $second['draft_id']);
        $this->assertSame(1, TelegramProductDraft::query()->count());
        $this->assertSame(1, \App\Models\TelegramProductEvent::query()->count());
    }

    public function test_cancel_marks_the_flow_cancelled_without_deleting_any_product(): void
    {
        TelegramProductManager::query()->create(['telegram_id' => 991004, 'name' => 'مدیر تست']);
        $service = app(TelegramProductDraftService::class);
        $started = $service->handle([
            'update_id' => 1004,
            'event' => 'start',
            'telegram' => ['id' => 991004],
            'chat_id' => '991004',
            'message_id' => 10,
        ]);
        $cancelled = $service->handle([
            'update_id' => 1005,
            'event' => 'callback',
            'callback_data' => 'product:cancel',
            'telegram' => ['id' => 991004],
            'chat_id' => '991004',
            'message_id' => 11,
        ]);

        $this->assertSame('cancelled', $cancelled['status']);
        $this->assertSame('cancelled', TelegramProductDraft::query()->findOrFail($started['draft_id'])->state);
    }

    public function test_product_webhook_requires_its_private_secret(): void
    {
        TelegramProductManager::query()->create(['telegram_id' => 991005, 'name' => 'مدیر تست']);

        $response = $this->postJson('/webhooks/telegram/product', [
            'update_id' => 1006,
            'event' => 'start',
            'telegram_id' => 991005,
            'chat_id' => '991005',
        ]);
        $response->assertForbidden();

        $this->withHeader('X-Vatan-Product-Webhook-Secret', 'test-secret')
            ->postJson('/webhooks/telegram/product', [
                'update_id' => 1007,
                'event' => 'start',
                'telegram_id' => 991005,
                'chat_id' => '991005',
                'message_id' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'awaiting_image');
    }

    public function test_photo_file_id_is_downloaded_and_optimized_without_sending_it_to_ai(): void
    {
        config(['services.telegram_product.bot_token' => 'product-bot-token']);
        Storage::fake('public');
        Http::fake([
            'https://api.telegram.org/botproduct-bot-token/getFile*' => Http::response([
                'ok' => true,
                'result' => ['file_path' => 'photos/sample.png'],
            ]),
            'https://api.telegram.org/file/botproduct-bot-token/photos/sample.png' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);
        $manager = TelegramProductManager::query()->create(['telegram_id' => 991006, 'name' => 'مدیر تصویر']);
        $service = app(TelegramProductDraftService::class);

        $started = $service->handle([
            'update_id' => 1008,
            'event' => 'start',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991006',
        ]);
        $photo = $service->handle([
            'update_id' => 1009,
            'event' => 'photo',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991006',
            'message_id' => 13,
            'file_id' => 'telegram-file-id',
        ]);

        $this->assertSame('awaiting_description', $photo['status']);
        $this->assertNotEmpty(TelegramProductDraft::query()->findOrFail($started['draft_id'])->image_paths);
        Http::assertSentCount(2);
    }

    public function test_processing_draft_can_be_checked_with_a_button_until_review_is_ready(): void
    {
        $manager = TelegramProductManager::query()->create(['telegram_id' => 991007, 'name' => 'مدیر پردازش']);
        $service = app(TelegramProductDraftService::class);
        $started = $service->handle([
            'update_id' => 1010,
            'event' => 'start',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991007',
        ]);
        $draft = TelegramProductDraft::query()->findOrFail($started['draft_id']);
        $draft->forceFill(['state' => 'processing'])->save();

        $waiting = $service->handle([
            'update_id' => 1011,
            'event' => 'callback',
            'callback_data' => 'product:status',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991007',
        ]);
        $this->assertSame('processing', $waiting['status']);
        $this->assertSame('product:status', $waiting['buttons'][0]['callback_data']);

        $draft->forceFill([
            'state' => 'review',
            'ai_result' => [
                'name_fa' => 'محصول آزمایشی',
                'name_en' => 'Test Product',
                'category' => 'عمومی',
                'category_name' => 'عمومی',
                'description_fa' => 'توضیح محصول آزمایشی',
            ],
        ])->save();
        $ready = $service->handle([
            'update_id' => 1012,
            'event' => 'callback',
            'callback_data' => 'product:status',
            'telegram' => ['id' => $manager->telegram_id],
            'chat_id' => '991007',
        ]);
        $this->assertSame('review', $ready['status']);
        $this->assertSame('product:save_draft', $ready['buttons'][3]['callback_data']);
    }
}
