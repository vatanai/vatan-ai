<?php

namespace App\Services;

use App\Models\ReferralConversion;
use App\Models\ReferralEvent;
use App\Models\ReferralLink;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\Admin;
use App\Models\GeneratedImage;
use App\Models\PlanPurchase;
use App\Models\Plan;
use App\Models\Product;
use App\Models\TokenLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReferralProgramService
{
    public function captureVisit(
        User $inviter,
        Request $request,
        ?ReferralLink $link = null,
        ?Product $product = null,
        string $source = 'link',
    ): ?ReferralVisit
    {
        $settings = ReferralSetting::current();

        if (! $settings->referralIsActive()
            || $inviter->status !== 'active'
            || auth()->id() === $inviter->id
            || $request->session()->has('referral.attribution')) {
            return null;
        }

        $visitorToken = $request->session()->get('referral.visitor_token', (string) Str::uuid());
        $request->session()->put('referral.visitor_token', $visitorToken);

        $destination = $product
            ? route('app.product', $product->route_slug)
            : ($link?->destination_url ?: route('site.home.root'));
        $request->session()->put('referral.destination', $destination);

        $visit = ReferralVisit::query()->create([
            'inviter_id' => $inviter->id,
            'link_id' => $link?->id,
            'product_id' => $product?->id ?: $link?->product_id,
            'referral_code' => $inviter->referral_code,
            'visitor_token' => $visitorToken,
            'landing_url' => Str::limit($request->fullUrl(), 2048, ''),
            'ip_hash' => $this->hashValue($request->ip()),
            'device_hash' => $this->deviceHash($request),
            'source' => $source,
            'utm_source' => Str::limit((string) $request->query('utm_source'), 120, '' ) ?: null,
            'utm_medium' => Str::limit((string) $request->query('utm_medium'), 120, '' ) ?: null,
            'utm_campaign' => Str::limit((string) $request->query('utm_campaign'), 120, '' ) ?: null,
            'visited_at' => now(),
        ]);

        $request->session()->put('referral.attribution', [
            'visit_id' => $visit->id,
            'inviter_id' => $inviter->id,
            'referral_code' => $inviter->referral_code,
            'link_id' => $link?->id,
            'product_id' => $product?->id ?: $link?->product_id,
            'source' => $source,
            'captured_at' => now()->toIso8601String(),
        ]);

        $this->recordEvent('click', 'visit:'.$visit->id, [
            'inviter_id' => $inviter->id,
            'link_id' => $link?->id,
            'product_id' => $product?->id ?: $link?->product_id,
            'source' => $source,
            'metadata' => ['visit_id' => $visit->id],
        ]);

        return $visit;
    }

    public function captureLinkVisit(Request $request, ReferralLink $link): ?ReferralVisit
    {
        if (! $link->isActive()) {
            return null;
        }

        $link->loadMissing(['inviter', 'product']);
        if (! $link->inviter || $link->inviter->status !== 'active') {
            return null;
        }
        if ($link->product && $link->product->status !== 'active') {
            return null;
        }

        return $this->captureVisit($link->inviter, $request, $link, $link->product, 'product_link');
    }

    public function pullDestination(Request $request): ?string
    {
        $destination = $request->session()->pull('referral.destination');

        return is_string($destination) && $destination !== '' ? $destination : null;
    }

    /** کد دستی فقط می‌تواند انتساب اول را ثبت کند؛ با لینک قبلی رقابت نمی‌کند. */
    public function prepareManualAttribution(Request $request, ?string $code): ?User
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '' || ! $this->referralIsActive()) {
            return null;
        }

        $inviter = User::query()->where('referral_code', $code)->where('status', 'active')->first();
        if (! $inviter || auth()->id() === $inviter->id) {
            return null;
        }

        $existing = $request->session()->get('referral.attribution');
        if (is_array($existing)) {
            if ((int) ($existing['inviter_id'] ?? 0) !== $inviter->id) {
                $this->recordEvent('attribution_conflict', 'manual-conflict:'.sha1($request->session()->getId().':'.$code), [
                    'inviter_id' => $inviter->id,
                    'source' => 'manual',
                    'metadata' => ['kept_inviter_id' => $existing['inviter_id'] ?? null],
                ]);
            }
            return null;
        }

        $visit = $this->captureVisit($inviter, $request, null, null, 'manual');
        if (! $visit) {
            return null;
        }

        $this->recordEvent('code_entered', 'manual-code:'.$visit->id, [
            'inviter_id' => $inviter->id,
            'source' => 'manual',
            'metadata' => ['visit_id' => $visit->id],
        ]);

        return $inviter;
    }

    /** انتساب کد در زمان خرید برای کاربر قدیمی؛ بدون پاداش ثبت‌نام مجدد. */
    public function attributeExistingUser(User $invitee, ?string $code, Request $request): ?ReferralConversion
    {
        if ($invitee->referred_by || ! $this->referralIsActive()) {
            return $this->conversionFor($invitee);
        }

        $inviter = User::query()
            ->where('referral_code', strtoupper(trim((string) $code)))
            ->where('status', 'active')
            ->first();
        if (! $inviter || $inviter->id === $invitee->id) {
            return null;
        }

        $attribution = $request->session()->get('referral.attribution');
        $visit = is_array($attribution)
            ? ReferralVisit::query()->whereKey($attribution['visit_id'] ?? null)->first()
            : $this->captureVisit($inviter, $request, null, null, 'manual_purchase');
        if (! $visit || (int) $visit->inviter_id !== (int) $inviter->id) {
            return null;
        }

        $conversion = DB::transaction(function () use ($invitee, $inviter, $visit): ReferralConversion {
            $conversion = ReferralConversion::query()->firstOrCreate(
                ['invitee_id' => $invitee->id],
                [
                    'visit_id' => $visit->id,
                    'inviter_id' => $inviter->id,
                    'link_id' => $visit->link_id,
                    'product_id' => $visit->product_id,
                    'status' => 'qualified',
                    'qualified_at' => now(),
                    'signup_ip_hash' => $visit->ip_hash,
                    'signup_device_hash' => $visit->device_hash,
                ],
            );
            $invitee->forceFill(['referred_by' => $inviter->id, 'referral_attributed_at' => now()])->save();
            $visit->update(['converted_user_id' => $invitee->id, 'converted_at' => now()]);
            $this->recordEvent('registered', 'existing-user-attributed:'.$conversion->id, [
                'inviter_id' => $inviter->id,
                'invitee_id' => $invitee->id,
                'conversion_id' => $conversion->id,
                'source' => 'manual_purchase',
                'metadata' => ['visit_id' => $visit->id],
            ]);

            return $conversion;
        });

        return $conversion;
    }

    /**
     * هدیه ثبت‌نام و پاداش‌های رفرال را یک‌بار و به‌شکل تراکنشی اعمال می‌کند.
     *
     * @return array{registration_gift:int,invitee_reward:int,inviter_reward:int,conversion:?ReferralConversion}
     */
    public function completeRegistration(User $invitee, Request $request): array
    {
        return DB::transaction(function () use ($invitee, $request) {
            $settings = ReferralSetting::current();
            $result = [
                'registration_gift' => 0,
                'invitee_reward' => 0,
                'inviter_reward' => 0,
                'conversion' => null,
            ];

            $signupIpHash = $this->hashValue($request->ip());
            $signupDeviceHash = $this->deviceHash($request);

            if ($settings->registration_gift_enabled && $settings->registration_gift_tokens > 0) {
                $registrationRisk = $this->registrationGiftRiskReason(
                    $settings,
                    $invitee,
                    $signupIpHash,
                    $signupDeviceHash,
                );
                $reward = $this->createAndPayReward(
                    user: $invitee,
                    amount: $settings->registration_gift_tokens,
                    type: 'registration_gift',
                    eventKey: 'registration-gift:'.$invitee->id,
                    settings: $settings,
                    pendingReason: $registrationRisk,
                    ipHash: $signupIpHash,
                    deviceHash: $signupDeviceHash,
                );
                $result['registration_gift'] = $reward?->status === 'paid' ? $reward->amount : 0;
            }

            $this->prepareManualAttribution($request, $request->input('referral_code'));
            $attribution = $request->session()->pull('referral.attribution');
            if (! $settings->referralIsActive() || ! is_array($attribution)) {
                return $result;
            }

            $visit = ReferralVisit::query()
                ->whereKey($attribution['visit_id'] ?? null)
                ->where('inviter_id', $attribution['inviter_id'] ?? null)
                ->where('visited_at', '>=', now()->subDays($settings->attribution_window_days))
                ->lockForUpdate()
                ->first();

            $inviter = $visit?->inviter()->where('status', 'active')->lockForUpdate()->first();
            if (! $visit || ! $inviter || $inviter->id === $invitee->id) {
                return $result;
            }

            $riskReason = $this->riskReason($settings, $inviter, $invitee, $signupIpHash, $signupDeviceHash);

            $conversion = ReferralConversion::query()->firstOrCreate(
                ['invitee_id' => $invitee->id],
                [
                    'visit_id' => $visit->id,
                    'inviter_id' => $inviter->id,
                    'status' => $riskReason ? 'under_review' : 'qualified',
                    'link_id' => $visit->link_id,
                    'product_id' => $visit->product_id,
                    'risk_reason' => $riskReason,
                    'signup_ip_hash' => $signupIpHash,
                    'signup_device_hash' => $signupDeviceHash,
                    'qualified_at' => $riskReason ? null : now(),
                ],
            );

            if (! $conversion->wasRecentlyCreated) {
                $result['conversion'] = $conversion;
                return $result;
            }

            $invitee->forceFill([
                'referred_by' => $inviter->id,
                'referral_attributed_at' => now(),
            ])->save();

            $visit->update(['converted_user_id' => $invitee->id, 'converted_at' => now()]);
            $this->recordEvent('registered', 'registered:'.$conversion->id, [
                'inviter_id' => $inviter->id,
                'invitee_id' => $invitee->id,
                'link_id' => $visit->link_id,
                'product_id' => $visit->product_id,
                'conversion_id' => $conversion->id,
                'source' => $visit->source,
                'metadata' => ['visit_id' => $visit->id],
            ]);
            $result['conversion'] = $conversion;

            if ($settings->reward_trigger !== 'registration') {
                return $result;
            }

            $pendingReason = $riskReason ?: null;
            if ($settings->invitee_reward_tokens > 0) {
                $reward = $this->createAndPayReward(
                    user: $invitee,
                    amount: $settings->invitee_reward_tokens,
                    type: 'invitee_reward',
                    eventKey: 'referral-invitee:'.$conversion->id,
                    settings: $settings,
                    conversion: $conversion,
                    pendingReason: $pendingReason,
                );
                $result['invitee_reward'] = $reward?->status === 'paid' ? $reward->amount : 0;
            }

            if ($settings->inviter_reward_tokens > 0) {
                $limitReason = $pendingReason ?: $this->inviterLimitReason($settings, $inviter);
                $reward = $this->createAndPayReward(
                    user: $inviter,
                    amount: $settings->inviter_reward_tokens,
                    type: 'inviter_reward',
                    eventKey: 'referral-inviter:'.$conversion->id,
                    settings: $settings,
                    conversion: $conversion,
                    pendingReason: $limitReason,
                );
                $result['inviter_reward'] = $reward?->status === 'paid' ? $reward->amount : 0;
            }

            return $result;
        });
    }

    /** پاداش دعوت را فقط پس از ثبت اولین خرید موفق آزاد می‌کند. */
    public function handleFirstPurchase(User $invitee): array
    {
        return DB::transaction(function () use ($invitee) {
            $settings = ReferralSetting::current();
            $result = ['invitee_reward' => 0, 'inviter_reward' => 0];

            if ($settings->reward_trigger !== 'first_purchase'
                || ! PlanPurchase::query()->where('user_id', $invitee->id)->where('status', 'completed')->exists()) {
                return $result;
            }

            $conversion = ReferralConversion::query()
                ->where('invitee_id', $invitee->id)
                ->lockForUpdate()
                ->first();
            $inviter = $conversion?->inviter()->where('status', 'active')->lockForUpdate()->first();

            if (! $conversion || ! $inviter) {
                return $result;
            }

            if ($conversion->status === 'rejected') {
                return $result;
            }

            $pendingReason = $conversion->status === 'under_review'
                ? ($conversion->risk_reason ?: 'این دعوت نیازمند بررسی مدیر است.')
                : null;

            if ($settings->invitee_reward_tokens > 0) {
                $reward = $this->createAndPayReward(
                    user: $invitee,
                    amount: $settings->invitee_reward_tokens,
                    type: 'invitee_reward',
                    eventKey: 'referral-invitee:'.$conversion->id,
                    settings: $settings,
                    conversion: $conversion,
                    pendingReason: $pendingReason,
                );
                $result['invitee_reward'] = $reward?->status === 'paid' ? $reward->amount : 0;
            }

            if ($settings->inviter_reward_tokens > 0) {
                $reward = $this->createAndPayReward(
                    user: $inviter,
                    amount: $settings->inviter_reward_tokens,
                    type: 'inviter_reward',
                    eventKey: 'referral-inviter:'.$conversion->id,
                    settings: $settings,
                    conversion: $conversion,
                    pendingReason: $pendingReason ?: $this->inviterLimitReason($settings, $inviter),
                );
                $result['inviter_reward'] = $reward?->status === 'paid' ? $reward->amount : 0;
            }

            return $result;
        });
    }

    /** اولین تولید موفق کاربر دعوت‌شده را فقط یک‌بار ثبت می‌کند. */
    public function handleSuccessfulGeneration(GeneratedImage $image): void
    {
        if (! $image->user_id || ! Schema::hasTable('referral_conversions') || ! Schema::hasTable('referral_events')) {
            return;
        }

        DB::transaction(function () use ($image): void {
            $conversion = ReferralConversion::query()
                ->where('invitee_id', $image->user_id)
                ->lockForUpdate()
                ->first();
            if (! $conversion) {
                return;
            }

            $event = $this->recordEvent('first_image_created', 'first-image:'.$conversion->id, [
                'inviter_id' => $conversion->inviter_id,
                'invitee_id' => $conversion->invitee_id,
                'link_id' => $conversion->link_id,
                'product_id' => $image->product_id ?: $conversion->product_id,
                'conversion_id' => $conversion->id,
                'source' => 'generation',
                'metadata' => ['generated_image_id' => $image->id],
            ]);

            if ($event && ! $conversion->first_image_at) {
                $conversion->update(['first_image_at' => now()]);
            }
        });
    }

    /** قیمت خرید را قبل از ساخت سفارش با تخفیف رفرال محاسبه می‌کند. */
    public function purchaseOffer(User $user, Plan $plan, array $offer): array
    {
        $conversion = $this->conversionFor($user);
        $settings = ReferralSetting::current();
        $original = (int) ($offer['price'] ?? 0);
        $discount = 0;

        if ($conversion && $original > 0
            && (int) ($settings->minimum_purchase_amount ?? 0) <= $original) {
            $discount = (int) round($original * ((float) ($settings->referral_discount_percent ?? 10) / 100));
        }

        $offer['original_price'] = $original;
        $offer['discount_amount'] = max(0, min($original, $discount));
        $offer['price'] = max(0, $original - $offer['discount_amount']);
        $offer['referral_conversion_id'] = $conversion?->id;
        $offer['referral_discount_percent'] = $conversion ? (float) ($settings->referral_discount_percent ?? 10) : 0;

        return $offer;
    }

    public function attachPurchaseReferral(PlanPurchase $purchase, ?string $code = null): PlanPurchase
    {
        $conversion = $this->conversionFor($purchase->user ?: User::find($purchase->user_id));
        if (! $conversion) {
            return $purchase;
        }

        $settings = ReferralSetting::current();
        $snapshot = [
            'conversion_id' => $conversion->id,
            'inviter_id' => $conversion->inviter_id,
            'discount_percent' => (float) ($settings->referral_discount_percent ?? 10),
            'commission_percent' => (float) ($settings->purchase_commission_percent ?? 10),
            'code' => $code ?: $conversion->inviter?->referral_code,
        ];
        $purchase->forceFill([
            'original_amount' => $purchase->original_amount ?: (int) $purchase->paid_amount + (int) $purchase->discount_amount,
            'referral_conversion_id' => $conversion->id,
            'referral_snapshot' => $snapshot,
        ])->save();

        $this->recordEvent('purchase_started', 'purchase-started:'.$purchase->id, [
            'inviter_id' => $conversion->inviter_id,
            'invitee_id' => $purchase->user_id,
            'conversion_id' => $conversion->id,
            'plan_purchase_id' => $purchase->id,
            'source' => 'checkout',
            'amount' => (int) $purchase->paid_amount,
            'currency' => 'IRT',
            'metadata' => ['discount_amount' => (int) $purchase->discount_amount],
        ]);

        return $purchase->fresh();
    }

    /** پرداخت موفق را به‌صورت idempotent ثبت و کمیسیون را در دفتر مالی رفرال pending می‌کند. */
    public function handleCompletedPurchase(PlanPurchase $purchase): ?ReferralReward
    {
        return DB::transaction(function () use ($purchase): ?ReferralReward {
            $conversion = ReferralConversion::query()->where('invitee_id', $purchase->user_id)->lockForUpdate()->first();
            if (! $conversion || $purchase->status !== PlanPurchase::COMPLETED) {
                return null;
            }

            $settings = ReferralSetting::current();
            $finalAmount = (int) $purchase->paid_amount;
            $commission = (int) round($finalAmount * ((float) ($settings->purchase_commission_percent ?? 10) / 100));
            $purchaseEvent = $this->recordEvent('purchase_completed', 'purchase-completed:'.$purchase->id, [
                'inviter_id' => $conversion->inviter_id,
                'invitee_id' => $purchase->user_id,
                'conversion_id' => $conversion->id,
                'plan_purchase_id' => $purchase->id,
                'source' => 'payment',
                'amount' => $finalAmount,
                'currency' => 'IRT',
                'metadata' => ['discount_amount' => (int) $purchase->discount_amount],
            ]);

            if ($purchaseEvent && ! $purchaseEvent->wasRecentlyCreated) {
                return ReferralReward::query()->where('event_key', 'referral-commission:'.$purchase->id)->first();
            }

            $conversion->update([
                'first_purchase_at' => $conversion->first_purchase_at ?: now(),
                'purchase_amount' => (int) $conversion->purchase_amount + $finalAmount,
                'discount_amount' => (int) $conversion->discount_amount + (int) $purchase->discount_amount,
                'commission_amount' => (int) $conversion->commission_amount + $commission,
            ]);

            if ($commission < 1 || ! $conversion->inviter_id) {
                return null;
            }

            return ReferralReward::query()->firstOrCreate(
                ['event_key' => 'referral-commission:'.$purchase->id],
                [
                    'conversion_id' => $conversion->id,
                    'plan_purchase_id' => $purchase->id,
                    'user_id' => $conversion->inviter_id,
                    'reward_type' => 'purchase_commission',
                    'currency' => 'IRT',
                    'amount' => $commission,
                    'status' => 'pending',
                    'reason' => 'کمیسیون خرید موفق دعوت‌شده؛ آماده تسویه مالی',
                    'settings_snapshot' => $this->settingsSnapshot($settings),
                ],
            );
        });
    }

    /** بازگشت کمیسیون خرید در صورت بازپرداخت؛ رکورد اصلی حذف نمی‌شود و سند بدهکار جدا می‌ماند. */
    public function reversePurchaseCommission(PlanPurchase $purchase, ?string $reason = null): ?ReferralReward
    {
        return DB::transaction(function () use ($purchase, $reason): ?ReferralReward {
            $original = ReferralReward::query()
                ->where('event_key', 'referral-commission:'.$purchase->id)
                ->lockForUpdate()
                ->first();
            if (! $original) {
                return null;
            }

            $reversalKey = 'referral-commission-reversal:'.$purchase->id;
            $existing = ReferralReward::query()->where('event_key', $reversalKey)->first();
            if ($existing) {
                return $existing;
            }

            $original->update([
                'status' => 'reversed',
                'reason' => $reason ?: 'کمیسیون به‌دلیل بازگشت وجه برگشت خورد.',
            ]);

            $reversal = ReferralReward::query()->create([
                'conversion_id' => $original->conversion_id,
                'plan_purchase_id' => $purchase->id,
                'reversal_of_id' => $original->id,
                'user_id' => $original->user_id,
                'reward_type' => 'purchase_commission_reversal',
                'currency' => $original->currency ?: 'IRT',
                'direction' => 'debit',
                'amount' => $original->amount,
                'status' => 'pending',
                'event_key' => $reversalKey,
                'reason' => $reason ?: 'برگشت کمیسیون به‌دلیل بازپرداخت خرید',
                'settings_snapshot' => $original->settings_snapshot,
            ]);

            $this->recordEvent('purchase_refunded', 'purchase-refunded:'.$purchase->id, [
                'inviter_id' => $original->user_id,
                'invitee_id' => $purchase->user_id,
                'conversion_id' => $original->conversion_id,
                'plan_purchase_id' => $purchase->id,
                'source' => 'refund',
                'amount' => $original->amount,
                'currency' => $original->currency ?: 'IRT',
                'metadata' => ['reversal_reward_id' => $reversal->id],
            ]);

            return $reversal;
        });
    }

    private function conversionFor(?User $user): ?ReferralConversion
    {
        if (! $user || ! $user->referred_by || ! Schema::hasTable('referral_conversions')) {
            return null;
        }

        return ReferralConversion::query()
            ->where('invitee_id', $user->id)
            ->where('inviter_id', $user->referred_by)
            ->whereNotIn('status', ['rejected'])
            ->with('inviter')
            ->first();
    }

    private function referralIsActive(): bool
    {
        return ReferralSetting::current()->referralIsActive();
    }

    /** @param array<string,mixed> $data */
    private function recordEvent(string $type, string $key, array $data): ?ReferralEvent
    {
        if (! Schema::hasTable('referral_events')) {
            return null;
        }

        return ReferralEvent::query()->firstOrCreate(
            ['event_key' => $key],
            [
                'event_uuid' => (string) Str::uuid(),
                'event_type' => $type,
                'inviter_id' => $data['inviter_id'] ?? null,
                'invitee_id' => $data['invitee_id'] ?? null,
                'link_id' => $data['link_id'] ?? null,
                'product_id' => $data['product_id'] ?? null,
                'conversion_id' => $data['conversion_id'] ?? null,
                'plan_purchase_id' => $data['plan_purchase_id'] ?? null,
                'source' => $data['source'] ?? null,
                'currency' => $data['currency'] ?? null,
                'amount' => $data['amount'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'occurred_at' => now(),
            ],
        );
    }

    public function reviewConversion(ReferralConversion $conversion, string $action, Admin $admin, ?string $note = null): ReferralConversion
    {
        return DB::transaction(function () use ($conversion, $action, $admin, $note) {
            $locked = ReferralConversion::query()->lockForUpdate()->findOrFail($conversion->id);
            if ($locked->status !== 'under_review') {
                return $locked->fresh(['rewards']);
            }

            $approved = $action === 'approve';
            $locked->update([
                'status' => $approved ? 'qualified' : 'rejected',
                'qualified_at' => $approved ? now() : null,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $locked->rewards()->where('status', 'pending')->lockForUpdate()->get()
                ->each(fn (ReferralReward $reward) => $this->reviewReward($reward, $action, $admin, $note));

            return $locked->fresh(['rewards']);
        });
    }

    public function reviewReward(ReferralReward $reward, string $action, Admin $admin, ?string $note = null): ReferralReward
    {
        return DB::transaction(function () use ($reward, $action, $admin, $note) {
            $locked = ReferralReward::query()->lockForUpdate()->findOrFail($reward->id);
            if ($locked->status !== 'pending') {
                return $locked;
            }

            if ($action === 'reject') {
                $locked->update([
                    'status' => 'rejected',
                    'reason' => $note ?: $locked->reason,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                ]);

                return $locked->fresh();
            }

            // کمیسیون نقدی/تومانی دفتر مالی است و نباید به موجودی توکن کاربر اضافه شود.
            if (($locked->currency ?: 'token') !== 'token') {
                $locked->update([
                    'status' => 'paid',
                    'processed_at' => now(),
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'reason' => $note ?: $locked->reason,
                ]);

                return $locked->fresh();
            }

            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $existingLog = TokenLog::query()->where('event_key', $locked->event_key)->first();
            if ($existingLog) {
                $locked->update([
                    'balance_before' => $existingLog->balance_before,
                    'balance_after' => $existingLog->balance_after,
                    'status' => 'paid',
                    'processed_at' => $existingLog->created_at,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'reason' => $note,
                ]);

                return $locked->fresh();
            }

            $before = (int) $user->tokens;
            $after = $before + (int) $locked->amount;
            $user->forceFill(['tokens' => $after])->save();

            TokenLog::query()->create([
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'action' => 'add',
                'source' => $locked->reward_type,
                'event_key' => $locked->event_key,
                'amount' => $locked->amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'note' => $note ?: $this->rewardLabel($locked->reward_type).' پس از تأیید مدیر',
                'metadata' => ['conversion_id' => $locked->conversion_id, 'reward_id' => $locked->id],
            ]);

            $locked->update([
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => 'paid',
                'processed_at' => now(),
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'reason' => $note,
            ]);

            return $locked->fresh();
        });
    }

    private function createAndPayReward(
        User $user,
        int $amount,
        string $type,
        string $eventKey,
        ReferralSetting $settings,
        ?ReferralConversion $conversion = null,
        ?string $pendingReason = null,
        ?string $ipHash = null,
        ?string $deviceHash = null,
    ): ?ReferralReward {
        $existing = ReferralReward::query()->where('event_key', $eventKey)->first();
        if ($existing) {
            return $existing;
        }

        if ($type !== 'registration_gift' && ! $pendingReason && $settings->campaign_token_budget) {
            $spent = ReferralReward::query()
                ->whereIn('reward_type', ['invitee_reward', 'inviter_reward'])
                ->where('status', 'paid')
                ->sum('amount');
            if ($spent + $amount > $settings->campaign_token_budget) {
                $pendingReason = 'سقف کل توکن کمپین تکمیل شده است.';
            }
        }

        $reward = ReferralReward::query()->create([
            'conversion_id' => $conversion?->id,
            'user_id' => $user->id,
            'reward_type' => $type,
            'amount' => $amount,
            'status' => $pendingReason ? 'pending' : 'processing',
            'event_key' => $eventKey,
            'reason' => $pendingReason,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'settings_snapshot' => $this->settingsSnapshot($settings),
        ]);

        if ($pendingReason) {
            return $reward;
        }

        $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
        $before = (int) $lockedUser->tokens;
        $after = $before + $amount;
        $lockedUser->forceFill(['tokens' => $after])->save();

        $reward->update([
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => 'paid',
            'processed_at' => now(),
        ]);

        TokenLog::query()->create([
            'user_id' => $lockedUser->id,
            'admin_id' => null,
            'action' => 'add',
            'source' => $type,
            'event_key' => $eventKey,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'note' => $this->rewardLabel($type),
            'metadata' => ['conversion_id' => $conversion?->id, 'reward_id' => $reward->id],
        ]);

        return $reward->fresh();
    }

    private function riskReason(
        ReferralSetting $settings,
        User $inviter,
        User $invitee,
        ?string $ipHash,
        ?string $deviceHash,
    ): ?string {
        if ($inviter->phone && $inviter->phone === $invitee->phone) {
            return 'شماره موبایل دعوت‌کننده و دعوت‌شده یکسان است.';
        }

        $previous = ReferralConversion::query()
            ->where('inviter_id', $inviter->id)
            ->where('invitee_id', '!=', $invitee->id);

        if ($settings->review_repeated_ip && $ipHash && (clone $previous)->where('signup_ip_hash', $ipHash)->exists()) {
            return 'نشانی اینترنتی با دعوت قبلی این کاربر تکراری است.';
        }

        if ($settings->review_repeated_device && $deviceHash && (clone $previous)->where('signup_device_hash', $deviceHash)->exists()) {
            return 'دستگاه با دعوت قبلی این کاربر تکراری است.';
        }

        return null;
    }

    private function registrationGiftRiskReason(
        ReferralSetting $settings,
        User $user,
        ?string $ipHash,
        ?string $deviceHash,
    ): ?string {
        $recentClaims = ReferralReward::query()
            ->where('reward_type', 'registration_gift')
            ->whereIn('status', ['paid', 'pending'])
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>=', now()->subDays($settings->registration_gift_cooldown_days));

        if ($settings->registration_gift_review_repeated_device
            && $deviceHash
            && (clone $recentClaims)->where('device_hash', $deviceHash)->exists()) {
            return 'این دستگاه در بازه محدودیت، هدیه ثبت‌نام دیگری دریافت کرده است.';
        }

        if ($settings->registration_gift_review_repeated_ip
            && $ipHash
            && (clone $recentClaims)->where('ip_hash', $ipHash)->exists()) {
            return 'این نشانی اینترنتی در بازه محدودیت، هدیه ثبت‌نام دیگری دریافت کرده است.';
        }

        return null;
    }

    private function inviterLimitReason(ReferralSetting $settings, User $inviter): ?string
    {
        $paid = ReferralReward::query()
            ->where('user_id', $inviter->id)
            ->where('reward_type', 'inviter_reward')
            ->where('status', 'paid');

        if ($settings->daily_inviter_reward_limit
            && (clone $paid)->whereDate('processed_at', today())->count() >= $settings->daily_inviter_reward_limit) {
            return 'سقف پاداش روزانه دعوت‌کننده تکمیل شده است.';
        }

        if ($settings->monthly_inviter_reward_limit
            && (clone $paid)->whereBetween('processed_at', [now()->startOfMonth(), now()->endOfMonth()])->count() >= $settings->monthly_inviter_reward_limit) {
            return 'سقف پاداش ماهانه دعوت‌کننده تکمیل شده است.';
        }

        return null;
    }

    private function settingsSnapshot(ReferralSetting $settings): array
    {
        return $settings->only([
            'registration_gift_tokens',
            'registration_gift_cooldown_days',
            'invitee_reward_tokens',
            'inviter_reward_tokens',
            'reward_trigger',
            'referral_discount_percent',
            'purchase_commission_percent',
            'minimum_purchase_amount',
            'attribution_window_days',
            'daily_inviter_reward_limit',
            'monthly_inviter_reward_limit',
            'campaign_token_budget',
        ]);
    }

    private function rewardLabel(string $type): string
    {
        return match ($type) {
            'registration_gift' => 'هدیه ثبت‌نام',
            'invitee_reward' => 'هدیه ورود از لینک دعوت',
            'inviter_reward' => 'پاداش دعوت موفق',
            default => 'پاداش سیستمی',
        };
    }

    private function hashValue(?string $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : hash_hmac('sha256', $value, (string) config('app.key'));
    }

    private function deviceHash(Request $request): string
    {
        $deviceId = $request->cookie('vatan_device_id')
            ?: $request->session()->get('referral.device_id');

        if (! $deviceId) {
            $deviceId = (string) Str::uuid();
            $request->session()->put('referral.device_id', $deviceId);
            Cookie::queue(cookie(
                name: 'vatan_device_id',
                value: $deviceId,
                minutes: 60 * 24 * 365,
                httpOnly: true,
                secure: $request->isSecure(),
                sameSite: 'lax',
            ));
        }

        return $this->hashValue($deviceId);
    }
}
