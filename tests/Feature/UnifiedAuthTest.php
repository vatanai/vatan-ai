<?php

namespace Tests\Feature;

use App\Models\AuthEvent;
use App\Models\Otp;
use App\Models\User;
use App\Services\SmsEventService;
use App\Services\ReferralProgramService;
use App\Support\Jalali;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UnifiedAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name')->nullable(); $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique(); $table->string('phone')->nullable()->unique();
            $table->date('birth_date')->nullable(); $table->string('password')->nullable(); $table->text('password_reveal')->nullable();
            $table->string('status')->default('active'); $table->integer('tokens')->default(0);
            $table->integer('tokens_purchased')->default(0); $table->integer('tokens_used')->default(0);
            $table->string('referral_code')->nullable(); $table->unsignedBigInteger('referred_by')->nullable();
            $table->timestamp('referral_attributed_at')->nullable(); $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_login_at')->nullable(); $table->unsignedInteger('login_count')->default(0);
            $table->rememberToken(); $table->timestamps();
        });
        Schema::create('otps', function (Blueprint $table): void {
            $table->id(); $table->string('phone'); $table->string('purpose'); $table->string('code');
            $table->timestamp('expires_at'); $table->boolean('used')->default(false); $table->unsignedTinyInteger('attempts')->default(0); $table->timestamps();
        });
        Schema::create('auth_events', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('phone')->nullable();
            $table->string('event'); $table->string('method'); $table->boolean('successful');
            $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable(); $table->json('metadata')->nullable();
            $table->timestamp('occurred_at'); $table->timestamps();
        });
        Schema::create('user_gallery_settings', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->unique(); $table->boolean('enabled')->default(false);
            $table->timestamp('consented_at')->nullable(); $table->timestamp('consent_revoked_at')->nullable(); $table->timestamps();
        });
        Schema::create('user_gallery_events', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('user_gallery_item_id')->nullable();
            $table->string('action'); $table->string('source_type')->nullable(); $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable(); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_gallery_events'); Schema::dropIfExists('user_gallery_settings');
        Schema::dropIfExists('auth_events'); Schema::dropIfExists('otps'); Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_duplicate_send_is_blocked_and_only_one_sms_is_sent(): void
    {
        $phone = '09129999001';
        RateLimiter::clear('unified-auth-otp:'.$phone.'|127.0.0.1');
        $sms = $this->mock(SmsEventService::class);
        $sms->shouldReceive('send')->once()->andReturn(true);

        $this->postJson('/auth/unified/send-otp', ['phone' => $phone])->assertOk();
        $this->postJson('/auth/unified/send-otp', ['phone' => $phone])->assertStatus(429);
        $this->assertSame(1, Otp::query()->where('phone', $phone)->where('purpose', 'auth')->count());
    }

    public function test_unified_otp_remains_valid_when_sms_arrives_after_five_minutes(): void
    {
        $phone = '09129999007';
        $plainCode = null;
        $sms = $this->mock(SmsEventService::class);
        $sms->shouldReceive('send')->once()->withArgs(function ($event, $recipient, $data) use (&$plainCode, $phone): bool {
            $plainCode = $data['code'] ?? null;

            return $event === 'otp_code' && $recipient === $phone;
        })->andReturnTrue();

        $this->postJson('/auth/unified/send-otp', ['phone' => $phone])
            ->assertOk()
            ->assertJsonPath('expires_in', 600);

        $otp = Otp::query()->where('phone', $phone)->where('purpose', 'auth')->latest()->firstOrFail();
        $this->assertSame(10, (int) $otp->created_at->diffInMinutes($otp->expires_at));
        $this->assertNotNull($plainCode);

        $now = now();
        Carbon::setTestNow($now->copy()->addMinutes(5));
        try {
            $this->postJson('/auth/unified/verify-otp', ['phone' => $phone, 'code' => $plainCode])
                ->assertOk()
                ->assertJsonPath('next', 'profile');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_existing_user_is_logged_in_after_valid_otp_and_login_is_tracked(): void
    {
        $user = User::query()->create(['phone' => '09129999002', 'status' => 'active', 'name' => 'کاربر', 'tokens' => 0]);
        Otp::query()->create(['phone' => $user->phone, 'purpose' => 'auth', 'code' => Hash::make('12345'), 'expires_at' => now()->addMinutes(3)]);

        $this->postJson('/auth/unified/verify-otp', ['phone' => $user->phone, 'code' => '12345'])
            ->assertOk()->assertJsonPath('next', 'redirect');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertSame(1, $user->fresh()->login_count);
        $this->assertTrue(AuthEvent::query()->where('user_id', $user->id)->where('event', 'login_success')->exists());
    }

    public function test_existing_user_receives_the_returning_user_otp_template_event(): void
    {
        $phone = '09129999005';
        $user = User::query()->create(['phone' => $phone, 'status' => 'active', 'name' => 'محسن', 'tokens' => 0]);
        RateLimiter::clear('unified-auth-otp:'.$phone.'|127.0.0.1');
        $sms = $this->mock(SmsEventService::class);
        $sms->shouldReceive('send')->once()->withArgs(function ($event, $recipient, $data, $template, $type) use ($user): bool {
            return $event === 'login_otp'
                && $recipient === $user->phone
                && $data['name'] === 'محسن'
                && preg_match('/^\d{5}$/', $data['code']) === 1
                && $template === null
                && $type === 'authentication';
        })->andReturnTrue();

        $this->postJson('/auth/unified/send-otp', ['phone' => $phone])->assertOk();
    }

    public function test_password_login_updates_the_same_login_audit_fields(): void
    {
        $this->mock(SmsEventService::class)->shouldReceive('send')->once()->andReturnTrue();
        $user = User::query()->create([
            'phone' => '09129999004',
            'status' => 'active',
            'name' => 'ورود رمزی',
            'password' => Hash::make('12345678'),
            'tokens' => 0,
        ]);

        $this->postJson('/auth/login-submit', ['phone' => $user->phone, 'password' => '12345678'])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $event = AuthEvent::query()->where('user_id', $user->id)->where('event', 'login_success')->sole();
        $fresh = $user->fresh();
        $this->assertSame(1, $fresh->login_count);
        $this->assertNotNull($fresh->last_login_at);
        $this->assertSame($user->phone, $event->phone);
        $this->assertSame('password', $event->method);
    }

    public function test_new_phone_continues_to_profile_and_registration_keeps_audit_dates(): void
    {
        $this->mock(ReferralProgramService::class)->shouldReceive('completeRegistration')->once()->andReturn([
            'registration_gift' => 0, 'invitee_reward' => 0, 'inviter_reward' => 0, 'conversion' => null,
        ]);
        $phone = '09129999003';
        Otp::query()->create(['phone' => $phone, 'purpose' => 'auth', 'code' => Hash::make('12345'), 'expires_at' => now()->addMinutes(3)]);

        $this->postJson('/auth/unified/verify-otp', ['phone' => $phone, 'code' => '12345'])
            ->assertOk()->assertJsonPath('next', 'profile');
        $response = $this->postJson('/auth/unified/register', [
            'phone' => $phone, 'name' => 'علی', 'last_name' => 'آزمایشی', 'email' => null,
            'gallery_consent' => 1,
            'birth_day' => '१', 'birth_month' => '١', 'birth_year' => '۱۳۷۰',
        ])->assertOk();

        $user = User::query()->where('phone', $phone)->firstOrFail();
        [$gy, $gm, $gd] = Jalali::toGregorianYmd(1370, 1, 1);
        $response->assertJsonPath('status', 'success');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(sprintf('%04d-%02d-%02d', $gy, $gm, $gd), $user->birth_date->format('Y-m-d'));
        $this->assertNotNull($user->registered_at);
        $this->assertNotNull($user->last_login_at);
        $this->assertSame(1, $user->login_count);
        $this->assertTrue(AuthEvent::query()->where('user_id', $user->id)->where('event', 'registration_completed')->exists());
        $this->assertDatabaseHas('user_gallery_settings', ['user_id' => $user->id, 'enabled' => 1]);
        $this->assertDatabaseHas('user_gallery_events', ['user_id' => $user->id, 'action' => 'consent_granted']);
    }

    public function test_registration_rejects_an_out_of_range_birth_day_with_a_clear_message(): void
    {
        $this->postJson('/auth/unified/register', [
            'phone' => '09129999006',
            'name' => 'علی',
            'last_name' => 'آزمایشی',
            'email' => null,
            'gallery_consent' => 1,
            'birth_day' => '۳۲',
            'birth_month' => '۱',
            'birth_year' => '۱۳۷۰',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'روز تولد باید عددی بین ۱ تا ۳۱ باشد.');
    }
}
