<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\TelegramDeepLinkService;
use App\Services\TelegramFlowService;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelegramFlowServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('route_slug')->nullable();
            $table->string('slug')->nullable();
            $table->string('name_fa')->nullable();
            $table->string('name_en')->nullable();
            $table->text('description_fa')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('telegram_users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('telegram_id')->unique(); $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('username')->nullable(); $table->string('first_name')->nullable(); $table->string('last_name')->nullable(); $table->string('language_code')->nullable();
            $table->text('phone')->nullable(); $table->timestamp('phone_verified_at')->nullable(); $table->boolean('is_premium')->default(false); $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable(); $table->timestamp('started_at')->nullable(); $table->timestamp('last_active_at')->nullable(); $table->timestamp('registration_completed_at')->nullable();
            $table->string('registration_state')->default('idle'); $table->json('registration_payload')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
        Schema::create('telegram_events', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('update_id')->nullable()->unique(); $table->unsignedBigInteger('telegram_user_id')->nullable(); $table->string('event_type'); $table->string('chat_id')->nullable(); $table->json('payload')->nullable(); $table->timestamp('occurred_at'); $table->timestamps();
        });
        Schema::create('telegram_product_clicks', function (Blueprint $table): void {
            $table->id(); $table->uuid('launch_token')->unique(); $table->unsignedBigInteger('telegram_user_id'); $table->unsignedBigInteger('product_id')->nullable(); $table->string('product_key')->nullable(); $table->string('source')->nullable(); $table->string('source_channel')->nullable(); $table->string('source_campaign')->nullable(); $table->string('channel_id')->nullable(); $table->string('channel_username')->nullable(); $table->string('message_id')->nullable(); $table->string('start_payload')->nullable(); $table->json('metadata')->nullable(); $table->timestamp('clicked_at'); $table->timestamp('opened_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('telegram_deep_links', function (Blueprint $table): void {
            $table->id(); $table->string('token', 24)->unique(); $table->unsignedBigInteger('product_id')->nullable(); $table->string('source'); $table->string('source_channel')->nullable(); $table->string('source_campaign')->nullable(); $table->string('message_id')->nullable(); $table->unsignedInteger('click_count')->default(0); $table->timestamp('last_clicked_at')->nullable(); $table->boolean('is_active')->default(true); $table->json('metadata')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('telegram_deep_links'); Schema::dropIfExists('telegram_product_clicks'); Schema::dropIfExists('telegram_events'); Schema::dropIfExists('telegram_users'); Schema::dropIfExists('products');
        parent::tearDown();
    }

    public function test_product_start_returns_the_selected_product_and_registration_button(): void
    {
        $product = Product::query()->create(['route_slug' => 'cinematic-portrait', 'slug' => 'cinematic-portrait', 'name_fa' => 'پرتره سینمایی', 'description_fa' => 'ساخت پرتره', 'status' => 'active']);
        config()->set('services.telegram.bot_username', 'vatan_test_bot');
        $link = app(TelegramDeepLinkService::class)->productLink($product, 'channel_post', ['channel' => 'ai_vatan']);
        $payload = 'tl_' . last(explode('tl_', $link));

        $response = app(TelegramFlowService::class)->handle([
            'update_id' => 5001,
            'event' => 'start',
            'telegram' => ['id' => 555001, 'first_name' => 'آزمایشی'],
            'chat_id' => '555001',
            'start_payload' => $payload,
            'is_channel_member' => true,
        ]);

        $this->assertSame('send_message', $response['type']);
        $this->assertStringContainsString('پرتره سینمایی', $response['text']);
        $this->assertSame('register', $response['buttons'][0]['callback_data']);
        $this->assertSame('channel_post', \DB::table('telegram_product_clicks')->value('source'));
    }

    public function test_membership_prompt_uses_the_configured_invite_link(): void
    {
        $response = app(TelegramFlowService::class)->handle([
            'update_id' => 5002,
            'event' => 'start',
            'telegram' => ['id' => 555002, 'first_name' => 'آزمایشی'],
            'chat_id' => '555002',
            'is_channel_member' => false,
        ]);

        $this->assertSame('send_message', $response['type']);
        $this->assertSame('https://t.me/+R90JNkLlW7M4ZTk0', $response['buttons'][0]['url']);
        $this->assertArrayNotHasKey('action', $response['buttons'][0]);
    }

    public function test_build_returns_a_web_app_button_for_the_selected_product(): void
    {
        $product = Product::query()->create([
            'route_slug' => 'cinematic-portrait',
            'slug' => 'cinematic-portrait',
            'name_fa' => 'پرتره سینمایی',
            'status' => 'active',
        ]);
        $telegramUser = \App\Models\TelegramUser::query()->create([
            'telegram_id' => 555003,
            'user_id' => 700003,
            'first_name' => 'آزمایشی',
        ]);
        $click = $telegramUser->productClicks()->create([
            'launch_token' => (string) \Illuminate\Support\Str::uuid(),
            'product_id' => $product->id,
            'product_key' => $product->route_slug,
            'source' => 'channel',
            'clicked_at' => now(),
        ]);

        config()->set('services.telegram.mini_app_url', 'https://aivatan.com/telegram/mini-app');
        $response = app(TelegramFlowService::class)->handle([
            'event' => 'build',
            'telegram' => ['id' => 555003, 'first_name' => 'آزمایشی'],
            'chat_id' => '555003',
            'launch_token' => $click->launch_token,
        ]);

        $this->assertSame('send_message', $response['type']);
        $this->assertSame($click->launch_token, $response['launch_token']);
        $this->assertSame($response['url'], $response['buttons'][0]['web_app']['url']);
        $this->assertStringContainsString('launch=', $response['buttons'][0]['web_app']['url']);
    }

    public function test_membership_is_checked_through_telegram_when_not_supplied_by_n8n(): void
    {
        config()->set('services.telegram.bot_token', 'test-token');
        config()->set('services.telegram.channel_username', 'ai_vatan');
        Http::fake(['*' => Http::response([
            'ok' => true,
            'result' => ['status' => 'member'],
        ])]);

        $response = app(TelegramFlowService::class)->handle([
            'update_id' => 5004,
            'event' => 'start',
            'telegram' => ['id' => 555004, 'first_name' => 'عضو'],
            'chat_id' => '555004',
        ]);
        $this->assertSame('send_message', $response['type']);
        $this->assertSame('register', $response['buttons'][0]['callback_data']);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/getChatMember'));
    }

    public function test_all_products_returns_a_web_app_button(): void
    {
        \App\Models\TelegramUser::query()->create([
            'telegram_id' => 555005,
            'user_id' => 700005,
            'first_name' => 'آزمایشی',
        ]);

        config()->set('services.telegram.mini_app_url', 'https://aivatan.com/telegram/mini-app');
        $response = app(TelegramFlowService::class)->handle([
            'event' => 'all_products',
            'telegram' => ['id' => 555005, 'first_name' => 'آزمایشی'],
            'chat_id' => '555005',
        ]);

        $this->assertSame('send_message', $response['type']);
        $this->assertSame($response['url'], $response['buttons'][0]['web_app']['url']);
        $this->assertStringContainsString('all=1', $response['url']);
    }

    public function test_registered_user_gets_a_plan_button_that_opens_the_authenticated_pricing_page(): void
    {
        \App\Models\TelegramUser::query()->create([
            'telegram_id' => 555006,
            'user_id' => 700006,
            'first_name' => 'آزمایشی',
        ]);

        config()->set('services.telegram.mini_app_url', 'https://aivatan.com/telegram/mini-app');
        $response = app(TelegramFlowService::class)->handle([
            'event' => 'message',
            'telegram' => ['id' => 555006, 'first_name' => 'آزمایشی'],
            'chat_id' => '555006',
            'text' => 'منو',
        ]);

        $this->assertSame('https://aivatan.com/telegram/mini-app?all=1&target=plans', $response['buttons'][1]['web_app']['url']);
    }

    public function test_interaction_service_sends_inline_buttons_as_telegram_reply_markup(): void
    {
        config()->set('services.telegram.bot_token', 'test-token');
        Http::fake(['https://api.telegram.org/*' => Http::response(['ok' => true])]);

        app(\App\Services\TelegramInteractionService::class)->sendResponse([
            'type' => 'send_message',
            'chat_id' => '555007',
            'text' => 'پیام آزمایشی',
            'buttons' => [['text' => 'ادامه', 'callback_data' => 'continue']],
        ]);

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/sendMessage')
                && data_get($request->data(), 'reply_markup.inline_keyboard.0.0.callback_data') === 'continue';
        });
    }
}
