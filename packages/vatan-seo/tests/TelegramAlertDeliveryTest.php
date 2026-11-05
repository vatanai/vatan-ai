<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;
use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\TelegramAdmin;
use Vatan\Seo\Services\Notifier;

class TelegramAlertDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('seo_sites', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('domain'); $table->string('base_url');
            $table->decimal('monthly_budget_usd', 8, 2)->default(20); $table->json('settings')->nullable(); $table->timestamps();
        });
        Schema::create('seo_telegram_admins', function (Blueprint $table) {
            $table->id(); $table->string('chat_id')->unique(); $table->string('name')->nullable(); $table->string('username')->nullable();
            $table->unsignedBigInteger('telegram_user_id')->nullable(); $table->unsignedBigInteger('message_thread_id')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('seo_alerts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('site_id'); $table->string('level'); $table->string('type');
            $table->string('title'); $table->text('body')->nullable(); $table->json('data')->nullable();
            $table->timestamp('telegram_sent_at')->nullable(); $table->timestamp('read_at')->nullable(); $table->timestamps();
        });
        config(['seo-engine.telegram.bot_token' => 'test-token-never-real', 'seo-engine.telegram.api_base' => 'https://api.telegram.org']);
    }

    protected function tearDown(): void
    {
        foreach (['seo_alerts', 'seo_telegram_admins', 'seo_sites'] as $table) Schema::dropIfExists($table);
        parent::tearDown();
    }

    #[RunInSeparateProcess]
    public function test_failed_alert_delivery_is_retried_and_thread_target_is_preserved(): void
    {
        Http::fakeSequence()->push(['ok' => false, 'description' => 'temporary failure'], 503)->push(['ok' => true, 'result' => ['message_id' => 7]], 200);
        $site = Site::create(['name' => 'وطن', 'domain' => 'aivatan.com', 'base_url' => 'https://aivatan.com']);
        TelegramAdmin::create([
            'chat_id' => '-1003903926314', 'name' => 'مدیر تست', 'username' => 'seo-test',
            'telegram_user_id' => 1001, 'message_thread_id' => 18, 'is_active' => true,
        ]);

        $alert = app(Notifier::class)->alert($site, 'danger', 'scenario_failure', 'شکست آزمون', 'پیام آزمون');
        self::assertNull($alert->fresh()->telegram_sent_at);

        self::assertSame(1, app(Notifier::class)->retryUndelivered($site));
        self::assertNotNull($alert->fresh()->telegram_sent_at);
        self::assertSame(0, app(Notifier::class)->retryUndelivered($site));
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['chat_id'] === '-1003903926314' && $request['message_thread_id'] === 18);
        self::assertSame(1, Alert::whereNotNull('telegram_sent_at')->count());
    }
}
