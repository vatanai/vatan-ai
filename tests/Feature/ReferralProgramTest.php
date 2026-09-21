<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\ReferralConversion;
use App\Models\ReferralEvent;
use App\Models\ReferralLink;
use App\Models\Product;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\ReferralProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReferralProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_link_keeps_the_first_valid_attribution_in_session(): void
    {
        self::assertTrue(ReferralSetting::current()->referral_enabled);
        $firstInviter = User::factory()->create(['status' => 'active']);
        $secondInviter = User::factory()->create(['status' => 'active']);

        $this->get(route('referral.visit', $firstInviter->referral_code))
            ->assertRedirect(route('site.home.root'))
            ->assertSessionHas('referral.attribution.inviter_id', $firstInviter->id);

        $this->get(route('referral.visit', $secondInviter->referral_code))
            ->assertSessionHas('referral.attribution.inviter_id', $firstInviter->id);

        self::assertSame(1, ReferralVisit::query()->count());
    }

    public function test_product_link_redirects_to_the_product_and_remains_usable_after_attribution(): void
    {
        $inviter = User::factory()->create(['status' => 'active']);
        $product = $this->createActiveProduct('referral-product-link-test');
        $link = ReferralLink::query()->create([
            'inviter_id' => $inviter->id,
            'product_id' => $product->id,
            'slug' => 'productlinktest',
            'destination_url' => route('app.product', $product->route_slug),
            'status' => 'active',
        ]);

        $this->get(route('referral.link', $link->slug))
            ->assertRedirect(route('app.product', $product->route_slug));

        $this->get(route('referral.link', $link->slug))
            ->assertRedirect(route('app.product', $product->route_slug));

        self::assertDatabaseHas('referral_visits', [
            'inviter_id' => $inviter->id,
            'link_id' => $link->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_known_link_preview_agents_do_not_inflate_referral_clicks(): void
    {
        $inviter = User::factory()->create(['status' => 'active']);

        $this->withHeader('User-Agent', 'WhatsApp/2.24 Link Preview')->get(route('referral.visit', $inviter->referral_code))
            ->assertRedirect(route('site.home.root'));

        self::assertSame(0, ReferralVisit::query()->count());
    }

    public function test_existing_user_can_be_attributed_from_the_saved_link_without_entering_a_code_again(): void
    {
        $inviter = User::factory()->create(['status' => 'active']);
        $invitee = User::factory()->create(['status' => 'active']);

        $conversion = app(ReferralProgramService::class)->attributeExistingUser(
            $invitee,
            null,
            $this->attributedRequest($inviter),
        );

        self::assertNotNull($conversion);
        self::assertSame($inviter->id, $invitee->fresh()->referred_by);
        self::assertSame($inviter->id, $conversion->inviter_id);
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
        self::assertSame(1, ReferralConversion::query()->where('inviter_id', $inviter->id)->count());
        self::assertDatabaseHas('referral_visits', [
            'inviter_id' => $inviter->id,
            'converted_user_id' => $invitee->id,
        ]);
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

    public function test_global_admin_approval_holds_referral_rewards_until_approved(): void
    {
        ReferralSetting::current()->update([
            'registration_gift_enabled' => false,
            'referral_enabled' => true,
            'referral_rewards_require_admin_approval' => true,
            'invitee_reward_tokens' => 3,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'registration',
            'review_repeated_ip' => false,
            'review_repeated_device' => false,
        ]);

        $inviter = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $invitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $service = app(ReferralProgramService::class);

        $service->completeRegistration($invitee, $this->attributedRequest($inviter));

        self::assertSame(0, (int) $invitee->fresh()->tokens);
        self::assertSame(0, (int) $inviter->fresh()->tokens);

        $conversion = ReferralConversion::query()->where('invitee_id', $invitee->id)->firstOrFail();
        self::assertSame('under_review', $conversion->status);
        self::assertSame(2, ReferralReward::query()->where('conversion_id', $conversion->id)->where('status', 'pending')->count());

        $admin = Admin::query()->create([
            'name' => 'مدیر تأیید رفرال',
            'email' => 'referral-approval@example.test',
            'password' => 'password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $service->reviewConversion($conversion, 'approve', $admin);

        self::assertSame(3, (int) $invitee->fresh()->tokens);
        self::assertSame(5, (int) $inviter->fresh()->tokens);
        self::assertSame(2, ReferralReward::query()->where('conversion_id', $conversion->id)->where('status', 'paid')->count());
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

    public function test_completed_purchase_pays_plan_percentage_as_tokens_and_recovers_missing_reward(): void
    {
        ReferralSetting::current()->update([
            'registration_gift_enabled' => false,
            'referral_enabled' => true,
            'referral_rewards_require_admin_approval' => false,
            'invitee_reward_tokens' => 0,
            'inviter_reward_tokens' => 0,
            'purchase_reward_tokens' => 999,
            'purchase_commission_percent' => 10,
            'reward_trigger' => 'registration',
            'review_repeated_ip' => false,
            'review_repeated_device' => false,
        ]);

        $plan = Plan::query()->create([
            'plan_code' => 'PLN-REF-TEST',
            'name' => 'پلن تست رفرال',
            'slug' => 'referral-commission-test',
            'price' => 599000,
            'tokens' => 25,
            'referral_commission_percent' => 20,
            'status' => 'active',
            'billing_type' => 'monthly',
            'version' => 1,
        ]);
        $inviter = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $invitee = User::factory()->create(['status' => 'active', 'tokens' => 0]);
        $service = app(ReferralProgramService::class);

        $service->completeRegistration($invitee, $this->attributedRequest($inviter));
        $purchase = PlanPurchase::query()->create([
            'user_id' => $invitee->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'paid_amount' => 599000,
            'granted_tokens' => 25,
            'plan_snapshot' => ['tokens' => 25, 'bonus_tokens' => 0],
            'status' => PlanPurchase::COMPLETED,
            'payment_reference' => 'REFERRAL-COMMISSION-TEST-1',
            'purchased_at' => now(),
        ]);

        $reward = $service->handleCompletedPurchase($purchase);
        $service->handleCompletedPurchase($purchase->fresh());

        self::assertNotNull($reward);
        self::assertSame(5, (int) $inviter->fresh()->tokens);
        self::assertDatabaseHas('referral_rewards', [
            'plan_purchase_id' => $purchase->id,
            'reward_type' => 'purchase_reward',
            'currency' => 'token',
            'amount' => 5,
            'status' => 'paid',
        ]);
        self::assertSame(1, ReferralReward::query()->where('plan_purchase_id', $purchase->id)->count());
        self::assertSame(1, TokenLog::query()->where('event_key', 'referral-purchase-reward:'.$purchase->id)->count());
        self::assertSame(0, ReferralReward::query()->where('plan_purchase_id', $purchase->id)->where('reward_type', 'purchase_commission')->count());
        self::assertSame(599000, (int) ReferralConversion::query()->where('invitee_id', $invitee->id)->value('purchase_amount'));

        $secondPurchase = PlanPurchase::query()->create([
            'user_id' => $invitee->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'paid_amount' => 599000,
            'granted_tokens' => 25,
            'plan_snapshot' => ['tokens' => 25, 'bonus_tokens' => 0],
            'status' => PlanPurchase::COMPLETED,
            'payment_reference' => 'REFERRAL-COMMISSION-TEST-2',
            'purchased_at' => now(),
        ]);
        $conversion = ReferralConversion::query()->where('invitee_id', $invitee->id)->firstOrFail();
        ReferralEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => 'purchase_completed',
            'event_key' => 'purchase-completed:'.$secondPurchase->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
            'conversion_id' => $conversion->id,
            'plan_purchase_id' => $secondPurchase->id,
            'source' => 'payment',
            'amount' => 599000,
            'currency' => 'IRT',
            'metadata' => [],
            'occurred_at' => now(),
        ]);

        $service->handleCompletedPurchase($secondPurchase);
        self::assertSame(10, (int) $inviter->fresh()->tokens);
        self::assertSame(2, ReferralReward::query()->where('user_id', $inviter->id)->where('reward_type', 'purchase_reward')->count());
        self::assertSame(599000, (int) ReferralConversion::query()->where('invitee_id', $invitee->id)->value('purchase_amount'));
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

    private function createActiveProduct(string $slug): Product
    {
        return Product::query()->create([
            'name_fa' => 'محصول تست رفرال',
            'name_en' => 'Referral Test Product',
            'slug' => $slug,
            'category' => 'TEST',
            'status' => 'active',
            'thumbnail' => 'products/test.jpg',
            'primary_model' => 'test-model',
            'prompt_template' => 'یک تصویر آزمایشی بساز.',
        ]);
    }
}
