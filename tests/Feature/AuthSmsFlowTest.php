<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\MeliPayamakService;
use App\Services\SmsEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AuthSmsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_and_verify_login_otp_without_redirecting_to_admin(): void
    {
        $user = User::factory()->create([
            'phone' => '09127573116',
            'status' => 'active',
        ]);

        $plainCode = null;
        $sms = Mockery::mock(SmsEventService::class);
        $sms->shouldReceive('send')
            ->once()
            ->withArgs(function ($event, $phone, $data, $template, $type) use (&$plainCode) {
                $plainCode = $data['code'] ?? null;

                return $event === 'otp_code'
                    && $phone === '09127573116'
                    && $template === null
                    && $type === 'authentication';
            })
            ->andReturnTrue();
        $this->app->instance(SmsEventService::class, $sms);

        $this->get('/login?redirect=/admin/sms/templates')->assertOk();

        $this->postJson('/auth/send-otp', [
            'phone' => '09127573116',
            'purpose' => 'login',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertNotNull($plainCode);
        $otp = Otp::query()->where('phone', '09127573116')->latest()->firstOrFail();
        $this->assertTrue(Hash::check($plainCode, $otp->code));

        $persianCode = strtr($plainCode, [
            '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
            '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        ]);

        $this->postJson('/auth/verify-otp', [
            'phone' => '09127573116',
            'purpose' => 'login',
            'code' => $persianCode,
        ])->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('redirect', '/app/home');

        $this->assertAuthenticatedAs($user);
    }

    public function test_shared_otp_uses_https_once_and_does_not_store_the_code_in_history(): void
    {
        config()->set('services.melipayamak.api_key', 'test-key');
        config()->set('services.melipayamak.base_url', 'http://console.melipayamak.com/api');

        Http::fake([
            'https://console.melipayamak.com/api/send/shared/test-key' => Http::response([
                'recId' => 123456,
                'status' => 'عملیات موفق',
            ]),
        ]);

        app(MeliPayamakService::class)->sendShared(
            '09127573116',
            ['39307'],
            '506694',
            'کد ورود: 39307',
            'authentication:otp_code',
        );

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://console.melipayamak.com/api/send/shared/test-key'
            && $request['args'] === ['39307']
        );

        $message = SmsMessage::query()->sole();
        $this->assertSame('رمز یک‌بارمصرف (مخفی‌شده)', $message->body);
        $this->assertArrayNotHasKey('values', $message->metadata ?? []);
        $this->assertSame('sent', $message->status);
    }
}
