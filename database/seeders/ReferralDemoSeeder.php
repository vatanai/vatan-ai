<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\PlanPurchase;
use App\Models\ReferralConversion;
use App\Models\ReferralReward;
use App\Models\ReferralSetting;
use App\Models\ReferralVisit;
use App\Models\TokenLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralDemoSeeder extends Seeder
{
    private const EMAILS = [
        'demo.referral.owner@vatan.local',
        'demo.referral.waiting@vatan.local',
        'demo.referral.success@vatan.local',
        'demo.referral.review@vatan.local',
        'demo.referral.rejected@vatan.local',
    ];

    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new \RuntimeException('این Seeder فقط برای محیط local قابل اجرا است.');
        }

        DB::transaction(function () {
            $this->removePreviousDemoData();

            $settings = ReferralSetting::current();
            $settings->update([
                'registration_gift_enabled' => true,
                'registration_gift_tokens' => 3,
                'registration_sms_enabled' => false,
                'registration_gift_review_repeated_ip' => true,
                'registration_gift_review_repeated_device' => true,
                'registration_gift_cooldown_days' => 90,
                'referral_enabled' => true,
                'invitee_reward_tokens' => 3,
                'inviter_reward_tokens' => 5,
                'reward_trigger' => 'first_purchase',
                'attribution_window_days' => 30,
                'review_repeated_ip' => true,
                'review_repeated_device' => true,
                'profile_enabled' => true,
                'profile_title' => 'همکاری در فروش وطن',
                'profile_subtitle' => 'لینکت را به اشتراک بگذار؛ دوستت هدیه می‌گیرد و تو پاداش.',
                'profile_description' => 'لینک اختصاصی‌ات را برای دوستانت یا در شبکه‌های اجتماعی منتشر کن. دوستت پس از ثبت‌نام هدیه شروع می‌گیرد و وقتی اولین خرید موفقش را انجام دهد، پاداش تو هم خودکار به کیف توکن اضافه می‌شود.',
                'share_message' => 'با لینک دعوت من به وطن بپیوند، ابزارهای هوش مصنوعی را تجربه کن و هدیه شروع بگیر: {referral_link}',
            ]);

            $users = $this->createUsers();
            $reviewer = Admin::query()->orderByRaw("role = 'leader' desc")->orderBy('id')->first();
            $snapshot = [
                'registration_gift_tokens' => 3,
                'invitee_reward_tokens' => 3,
                'inviter_reward_tokens' => 5,
                'reward_trigger' => 'first_purchase',
            ];

            $this->createUnconvertedVisits($users['owner']);
            $this->createWaitingCase($users, $snapshot);
            $this->createSuccessfulCase($users, $snapshot);
            $this->createReviewCase($users, $snapshot);
            $this->createRejectedCase($users, $snapshot, $reviewer);
        });
    }

    private function removePreviousDemoData(): void
    {
        $userIds = User::query()->whereIn('email', self::EMAILS)->pluck('id');

        TokenLog::query()->where('event_key', 'like', 'demo-referral:%')->delete();
        ReferralReward::query()->where('event_key', 'like', 'demo-referral:%')->delete();
        PlanPurchase::query()->where('payment_reference', 'like', 'DEMO-REF-%')->delete();

        if ($userIds->isNotEmpty()) {
            ReferralConversion::query()
                ->whereIn('inviter_id', $userIds)
                ->orWhereIn('invitee_id', $userIds)
                ->delete();
            ReferralVisit::query()
                ->whereIn('inviter_id', $userIds)
                ->orWhereIn('converted_user_id', $userIds)
                ->delete();
            User::query()->whereIn('id', $userIds)->delete();
        }
    }

    private function createUsers(): array
    {
        $rows = [
            'owner' => ['آرمان', 'دعوت‌گر', '09120001900', self::EMAILS[0], 'VATANDEMO', 40, null],
            'waiting' => ['سارا', 'منتظر خرید', '09120001901', self::EMAILS[1], 'DEMOWAIT01', 10, 'owner'],
            'success' => ['محمد', 'خرید موفق', '09120001902', self::EMAILS[2], 'DEMOPAID02', 10, 'owner'],
            'review' => ['نیلوفر', 'در حال بررسی', '09120001903', self::EMAILS[3], 'DEMOREVIEW', 10, 'owner'],
            'rejected' => ['علیرضا', 'دعوت ردشده', '09120001904', self::EMAILS[4], 'DEMOREJECT', 10, 'owner'],
        ];

        $users = [];
        foreach ($rows as $key => [$name, $lastName, $phone, $email, $code, $tokens, $referrer]) {
            $users[$key] = User::query()->create([
                'name' => $name,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email,
                'password' => 'Demo12345!',
                'status' => 'active',
                'customer_segment' => 'personal',
                'tokens' => $tokens,
                'tokens_purchased' => 0,
                'tokens_used' => 0,
                'referral_earnings' => 0,
                'referral_code' => $code,
                'referred_by' => $referrer ? $users[$referrer]->id : null,
                'referral_attributed_at' => $referrer ? now()->subDays(6) : null,
            ]);
        }

        return $users;
    }

    private function createUnconvertedVisits(User $owner): void
    {
        foreach ([9, 8, 7] as $index => $daysAgo) {
            ReferralVisit::query()->create([
                'inviter_id' => $owner->id,
                'referral_code' => $owner->referral_code,
                'visitor_token' => (string) Str::uuid(),
                'landing_url' => url('/?ref=VATANDEMO&utm_source=demo-'.$index),
                'ip_hash' => hash('sha256', 'demo-unconverted-ip-'.$index),
                'device_hash' => hash('sha256', 'demo-unconverted-device-'.$index),
                'visited_at' => now()->subDays($daysAgo),
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    private function createWaitingCase(array $users, array $snapshot): void
    {
        $date = now()->subDays(6);
        $visit = $this->convertedVisit($users['owner'], $users['waiting'], $date, 'waiting');
        $conversion = $this->conversion($visit, $users['owner'], $users['waiting'], 'qualified', $date, [
            'qualified_at' => $date,
        ]);

        $this->paidReward($conversion, $users['waiting'], 'registration_gift', 3, 10, 13, 'demo-referral:waiting:registration', $snapshot, $date);
        $users['waiting']->update(['tokens' => 13]);
    }

    private function createSuccessfulCase(array $users, array $snapshot): void
    {
        $date = now()->subDays(4);
        $visit = $this->convertedVisit($users['owner'], $users['success'], $date, 'success');
        $conversion = $this->conversion($visit, $users['owner'], $users['success'], 'qualified', $date, [
            'qualified_at' => $date,
        ]);

        PlanPurchase::query()->create([
            'user_id' => $users['success']->id,
            'plan_code' => 'demo-starter',
            'plan_name' => 'پلن شروع نمونه',
            'customer_segment' => 'personal',
            'paid_amount' => 149000,
            'granted_tokens' => 25,
            'plan_snapshot' => ['demo' => true, 'source' => 'referral'],
            'status' => 'completed',
            'payment_reference' => 'DEMO-REF-PAID-001',
            'purchased_at' => $date->copy()->addHours(3),
        ]);

        $this->paidReward($conversion, $users['success'], 'registration_gift', 3, 10, 13, 'demo-referral:success:registration', $snapshot, $date);
        $this->paidReward($conversion, $users['success'], 'invitee_reward', 3, 13, 16, 'demo-referral:success:invitee', $snapshot, $date->copy()->addHours(3));
        $this->paidReward($conversion, $users['owner'], 'inviter_reward', 5, 40, 45, 'demo-referral:success:inviter', $snapshot, $date->copy()->addHours(3));
        $users['success']->update(['tokens' => 16]);
        $users['owner']->update(['tokens' => 45]);
    }

    private function createReviewCase(array $users, array $snapshot): void
    {
        $date = now()->subDays(2);
        $sharedIp = hash('sha256', 'demo-shared-risk-ip');
        $sharedDevice = hash('sha256', 'demo-shared-risk-device');
        $visit = $this->convertedVisit($users['owner'], $users['review'], $date, 'review', $sharedIp, $sharedDevice);
        $conversion = $this->conversion($visit, $users['owner'], $users['review'], 'under_review', $date, [
            'risk_reason' => 'تکرار دستگاه و نشانی شبکه با ثبت‌نام قبلی',
            'signup_ip_hash' => $sharedIp,
            'signup_device_hash' => $sharedDevice,
        ]);

        PlanPurchase::query()->create([
            'user_id' => $users['review']->id,
            'plan_code' => 'demo-starter',
            'plan_name' => 'پلن شروع نمونه',
            'customer_segment' => 'personal',
            'paid_amount' => 149000,
            'granted_tokens' => 25,
            'plan_snapshot' => ['demo' => true, 'source' => 'referral', 'review' => true],
            'status' => 'completed',
            'payment_reference' => 'DEMO-REF-REVIEW-002',
            'purchased_at' => $date->copy()->addHour(),
        ]);

        $this->pendingReward($conversion, $users['review'], 'registration_gift', 3, 'demo-referral:review:registration', $snapshot, $sharedIp, $sharedDevice, 'ثبت‌نام مشکوک؛ نیازمند بررسی مدیر');
        $this->pendingReward($conversion, $users['review'], 'invitee_reward', 3, 'demo-referral:review:invitee', $snapshot, $sharedIp, $sharedDevice, 'خرید انجام شده اما الگوی تکراری نیازمند بررسی است');
        $this->pendingReward($conversion, $users['owner'], 'inviter_reward', 5, 'demo-referral:review:inviter', $snapshot, $sharedIp, $sharedDevice, 'خرید انجام شده اما الگوی تکراری نیازمند بررسی است');
    }

    private function createRejectedCase(array $users, array $snapshot, ?Admin $reviewer): void
    {
        $date = now()->subDay();
        $ip = hash('sha256', 'demo-rejected-ip');
        $device = hash('sha256', 'demo-rejected-device');
        $visit = $this->convertedVisit($users['owner'], $users['rejected'], $date, 'rejected', $ip, $device);
        $conversion = $this->conversion($visit, $users['owner'], $users['rejected'], 'rejected', $date, [
            'risk_reason' => 'حساب‌های تکراری و غیرواقعی',
            'signup_ip_hash' => $ip,
            'signup_device_hash' => $device,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now()->subHours(12),
            'review_note' => 'نمونه نمایشی: دعوت به دلیل الگوی سوءاستفاده رد شد.',
        ]);

        foreach ([
            [$users['rejected'], 'registration_gift', 3, 'registration'],
            [$users['rejected'], 'invitee_reward', 3, 'invitee'],
            [$users['owner'], 'inviter_reward', 5, 'inviter'],
        ] as [$user, $type, $amount, $suffix]) {
            ReferralReward::query()->create([
                'conversion_id' => $conversion->id,
                'user_id' => $user->id,
                'reward_type' => $type,
                'amount' => $amount,
                'balance_before' => $user->tokens,
                'balance_after' => $user->tokens,
                'status' => 'rejected',
                'event_key' => 'demo-referral:rejected:'.$suffix,
                'reason' => 'ردشده به دلیل تشخیص الگوی سوءاستفاده',
                'ip_hash' => $ip,
                'device_hash' => $device,
                'settings_snapshot' => $snapshot,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now()->subHours(12),
                'created_at' => $date,
                'updated_at' => now()->subHours(12),
            ]);
        }
    }

    private function convertedVisit(User $owner, User $invitee, $date, string $suffix, ?string $ip = null, ?string $device = null): ReferralVisit
    {
        return ReferralVisit::query()->create([
            'inviter_id' => $owner->id,
            'referral_code' => $owner->referral_code,
            'visitor_token' => (string) Str::uuid(),
            'landing_url' => url('/?ref=VATANDEMO&utm_source='.$suffix),
            'ip_hash' => $ip ?: hash('sha256', 'demo-'.$suffix.'-ip'),
            'device_hash' => $device ?: hash('sha256', 'demo-'.$suffix.'-device'),
            'visited_at' => $date,
            'converted_user_id' => $invitee->id,
            'converted_at' => $date->copy()->addMinutes(15),
            'created_at' => $date,
            'updated_at' => $date->copy()->addMinutes(15),
        ]);
    }

    private function conversion(ReferralVisit $visit, User $owner, User $invitee, string $status, $date, array $extra = []): ReferralConversion
    {
        return ReferralConversion::query()->create(array_merge([
            'visit_id' => $visit->id,
            'inviter_id' => $owner->id,
            'invitee_id' => $invitee->id,
            'status' => $status,
            'signup_ip_hash' => $visit->ip_hash,
            'signup_device_hash' => $visit->device_hash,
            'created_at' => $date->copy()->addMinutes(15),
            'updated_at' => $date->copy()->addMinutes(15),
        ], $extra));
    }

    private function paidReward(ReferralConversion $conversion, User $user, string $type, int $amount, int $before, int $after, string $eventKey, array $snapshot, $date): void
    {
        ReferralReward::query()->create([
            'conversion_id' => $conversion->id,
            'user_id' => $user->id,
            'reward_type' => $type,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => 'paid',
            'event_key' => $eventKey,
            'settings_snapshot' => $snapshot,
            'processed_at' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        TokenLog::query()->create([
            'user_id' => $user->id,
            'action' => 'add',
            'source' => $type,
            'event_key' => $eventKey,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'note' => 'پاداش نمونه برنامه دعوت و همکاری در فروش',
            'metadata' => ['demo' => true, 'conversion_id' => $conversion->id],
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }

    private function pendingReward(ReferralConversion $conversion, User $user, string $type, int $amount, string $eventKey, array $snapshot, string $ip, string $device, string $reason): void
    {
        ReferralReward::query()->create([
            'conversion_id' => $conversion->id,
            'user_id' => $user->id,
            'reward_type' => $type,
            'amount' => $amount,
            'balance_before' => $user->tokens,
            'balance_after' => $user->tokens,
            'status' => 'pending',
            'event_key' => $eventKey,
            'reason' => $reason,
            'ip_hash' => $ip,
            'device_hash' => $device,
            'settings_snapshot' => $snapshot,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
    }
}
