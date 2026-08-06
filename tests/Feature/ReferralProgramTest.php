<?php

namespace Tests\Feature;

use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\ReferralProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_link_keeps_the_first_valid_attribution_in_session(): void
    {
        ReferralSetting::current()->update(['referral_enabled' => true]);
        $firstInviter = User::factory()->create(['status' => 'active']);
        $secondInviter = User::factory()->create(['status' => 'active']);

        $this->get(route('referral.visit', $firstInviter->referral_code))
            ->assertRedirect(route('site.home.root'))
            ->assertSessionHas('referral.attribution.inviter_id', $firstInviter->id);

        $this->get(route('referral.visit', $secondInviter->referral_code))
            ->assertSessionHas('referral.attribution.inviter_id', $firstInviter->id);

        self::assertSame(1, ReferralVisit::query()->count());
    }

    public function test_registration_and_referral_rewards_are_paid_once_and_logged(): void
    {
        $settings = ReferralSetting::current();
        $settings->update([
            'registration_gift_enabled' => true,
            'registration_gift_tokens' => 3,
            'referral_enabled' => true,
            'invitee_reward_tokens' => 2,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'registration',
        ]);

        $inviter = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $invitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $request = $this->attributedRequest($inviter);

        $service = app(ReferralProgramService::class);
        $service->completeRegistration($invitee, $request);
        $service->completeRegistration($invitee, $request);

        self::assertSame(5, (int) $invitee->fresh()->tokens);
        self::assertSame(5, (int) $inviter->fresh()->tokens);
        self::assertSame($inviter->id, $invitee->fresh()->referred_by);
        self::assertSame(3, ReferralReward::query()->where('status', 'paid')->count());
        self::assertSame(3, TokenLog::query()->whereNotNull('event_key')->count());
    }

    public function test_repeated_device_or_ip_is_held_for_admin_review(): void
    {
        ReferralSetting::current()->update([
            'registration_gift_enabled' => true,
            'registration_gift_tokens' => 3,
            'referral_enabled' => true,
            'invitee_reward_tokens' => 2,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'registration',
            'review_repeated_ip' => true,
            'review_repeated_device' => true,
        ]);

        $inviter = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $firstInvitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $secondInvitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $service = app(ReferralProgramService::class);

        $service->completeRegistration($firstInvitee, $this->attributedRequest($inviter));
        $service->completeRegistration($secondInvitee, $this->attributedRequest($inviter));

        self::assertSame(5, (int) $inviter->fresh()->tokens);
        self::assertSame(3, (int) $secondInvitee->fresh()->tokens);
        self::assertSame(2, ReferralReward::query()->where('status', 'pending')->count());
        self::assertDatabaseHas('referral_conversions', [
            'invitee_id' => $secondInvitee->id,
            'status' => 'under_review',
        ]);
    }

    public function test_referral_rewards_wait_for_the_first_successful_purchase(): void
    {
        ReferralSetting::current()->update([
            'registration_gift_tokens' => 3,
            'referral_enabled' => true,
            'invitee_reward_tokens' => 2,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'first_purchase',
        ]);

        $inviter = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $invitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $service = app(ReferralProgramService::class);
        $service->completeRegistration($invitee, $this->attributedRequest($inviter));

        self::assertSame(3, (int) $invitee->fresh()->tokens);
        self::assertSame(0, (int) $inviter->fresh()->tokens);

        PlanPurchase::query()->create([
            'user_id' => $invitee->id,
            'plan_name' => 'پلن آزمایشی',
            'paid_amount' => 1000,
            'granted_tokens' => 1,
            'plan_snapshot' => [],
            'status' => 'completed',
            'payment_reference' => 'REFERRAL-FIRST-PURCHASE-TEST',
            'purchased_at' => now(),
        ]);

        $service->handleFirstPurchase($invitee);
        $service->handleFirstPurchase($invitee);

        self::assertSame(5, (int) $invitee->fresh()->tokens);
        self::assertSame(5, (int) $inviter->fresh()->tokens);
    }

    private function attributedRequest(User $inviter): Request
    {
        $request = Request::create('/r/'.$inviter->referral_code, 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.0.2.10',
            'HTTP_USER_AGENT' => 'Vatan Referral Test Browser',
        ]);
        $session = app('session')->driver();
        $session->flush();
        $request->setLaravelSession($session);
        app(ReferralProgramService::class)->captureVisit($inviter, $request);

        return $request;
    }
}
