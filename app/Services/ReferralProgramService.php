<?php

namespace App\Services;

use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\Admin;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class ReferralProgramService
{
    public function captureVisit(User $inviter, Request $request): ?ReferralVisit
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

        $visit = ReferralVisit::query()->create([
            'inviter_id' => $inviter->id,
            'referral_code' => $inviter->referral_code,
            'visitor_token' => $visitorToken,
            'landing_url' => Str::limit($request->fullUrl(), 2048, ''),
            'ip_hash' => $this->hashValue($request->ip()),
            'device_hash' => $this->deviceHash($request),
            'visited_at' => now(),
        ]);

        $request->session()->put('referral.attribution', [
            'visit_id' => $visit->id,
            'inviter_id' => $inviter->id,
            'referral_code' => $inviter->referral_code,
            'captured_at' => now()->toIso8601String(),
        ]);

        return $visit;
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
