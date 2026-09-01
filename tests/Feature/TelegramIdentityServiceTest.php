<?php

namespace Tests\Feature;

use App\Models\TelegramUser;
use App\Models\User;
use App\Services\TelegramIdentityService;
use App\Services\SmsEventService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class TelegramIdentityServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('password')->nullable();
            $table->text('password_reveal')->nullable();
            $table->string('status')->default('active');
            $table->unsignedInteger('tokens')->default(0);
            $table->unsignedInteger('tokens_purchased')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('promotional_tokens')->default(0);
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('telegram_gift_claimed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->string('referral_code')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('telegram_users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('language_code')->nullable();
            $table->text('phone')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('registration_completed_at')->nullable();
            $table->string('registration_state')->default('idle');
            $table->json('registration_payload')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('token_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('action');
            $table->string('source')->nullable();
            $table->string('event_key')->nullable()->unique();
            $table->integer('amount');
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
        Schema::create('user_token_grants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('token_log_id')->nullable();
            $table->unsignedInteger('amount');
            $table->unsignedInteger('remaining_amount');
            $table->timestamp('expires_at')->nullable();
            $table->string('source');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
        });
        Schema::create('otps', function (Blueprint $table): void {
            $table->id();
            $table->string('phone');
            $table->string('purpose')->nullable();
            $table->string('code');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('used')->default(false);
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_token_grants');
        Schema::dropIfExists('otps');
        Schema::dropIfExists('token_logs');
        Schema::dropIfExists('telegram_users');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_new_telegram_user_gets_one_shared_site_account_and_one_gift(): void
    {
        $telegramUser = app(TelegramIdentityService::class)->upsert([
            'id' => 987654,
            'first_name' => 'علی',
            'last_name' => 'آزمایشی',
            'username' => 'ali_test',
        ]);

        $user = app(TelegramIdentityService::class)->linkTrustedContact($telegramUser, '09120000000');
        $again = app(TelegramIdentityService::class)->linkTrustedContact($telegramUser->fresh(), '09120000000');

        $this->assertSame($user->id, $again->id);
        $this->assertSame(40, (int) $again->fresh()->tokens);
        $this->assertSame(40, (int) $again->fresh()->promotional_tokens);
        $this->assertSame(1, (int) \DB::table('token_logs')->where('source', 'telegram_registration_gift')->count());
        $this->assertSame(987654, (int) TelegramUser::query()->firstOrFail()->telegram_id);
    }

    public function test_existing_site_user_is_linked_without_receiving_telegram_gift(): void
    {
        $existing = User::query()->create([
            'name' => 'کاربر قبلی',
            'phone' => '09121111111',
            'status' => 'active',
            'tokens' => 7,
        ]);
        $telegramUser = app(TelegramIdentityService::class)->upsert(['id' => 123123, 'first_name' => 'کاربر']);

        $linked = app(TelegramIdentityService::class)->linkTrustedContact($telegramUser, $existing->phone);

        $this->assertSame($existing->id, $linked->id);
        $this->assertSame(7, (int) $linked->fresh()->tokens);
        $this->assertSame(0, (int) \DB::table('token_logs')->count());
    }

    public function test_phone_otp_uses_new_and_returning_site_sms_templates(): void
    {
        $sms = Mockery::mock(SmsEventService::class);
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event, string $phone, array $data, $template, string $type): bool =>
                $event === 'otp_code'
                && $phone === '09123334445'
                && $data['name'] === 'تازه‌وارد'
                && $template === null
                && $type === 'telegram_authentication'
            )
            ->andReturnTrue();
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(fn (string $event, string $phone, array $data, $template, string $type): bool =>
                $event === 'login_otp'
                && $phone === '09123334446'
                && $data['name'] === 'کاربر قدیمی'
                && $template === null
                && $type === 'telegram_authentication'
            )
            ->andReturnTrue();
        $this->app->instance(SmsEventService::class, $sms);

        $newTelegramUser = app(TelegramIdentityService::class)->upsert(['id' => 987655, 'first_name' => 'تازه‌وارد']);
        app(TelegramIdentityService::class)->requestPhoneOtp($newTelegramUser, '09123334445');

        User::query()->create(['name' => 'کاربر قدیمی', 'phone' => '09123334446', 'status' => 'active']);
        $returningTelegramUser = app(TelegramIdentityService::class)->upsert(['id' => 987656, 'first_name' => 'تلگرام']);
        app(TelegramIdentityService::class)->requestPhoneOtp($returningTelegramUser, '09123334446');

        $this->assertSame(2, \DB::table('otps')->where('purpose', 'telegram_auth')->count());
        $this->assertSame('awaiting_otp', $returningTelegramUser->fresh()->registration_state);
    }
}
