<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ReferralSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'registration_gift_enabled' => 'boolean',
            'registration_gift_tokens' => 'integer',
            'prelogin_credit_text' => 'string',
            'registration_sms_enabled' => 'boolean',
            'registration_gift_review_repeated_ip' => 'boolean',
            'registration_gift_review_repeated_device' => 'boolean',
            'registration_gift_cooldown_days' => 'integer',
            'referral_enabled' => 'boolean',
            'invitee_reward_tokens' => 'integer',
            'inviter_reward_tokens' => 'integer',
            'referral_discount_percent' => 'float',
            'purchase_commission_percent' => 'float',
            'minimum_purchase_amount' => 'integer',
            'attribution_window_days' => 'integer',
            'daily_inviter_reward_limit' => 'integer',
            'monthly_inviter_reward_limit' => 'integer',
            'campaign_token_budget' => 'integer',
            'campaign_starts_at' => 'datetime',
            'campaign_ends_at' => 'datetime',
            'review_repeated_ip' => 'boolean',
            'review_repeated_device' => 'boolean',
            'profile_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return once(function () {
            try {
                if (Schema::hasTable('referral_settings')) {
                    return static::query()->firstOrCreate([], static::defaults());
                }
            } catch (\Throwable $exception) {
                // تنظیمات ارجاع نباید در زمان اختلال یا عقب‌بودن migrationها
                // رندر هدر و صفحه‌ی عمومی را با خطای ۵۰۰ متوقف کند.
                report($exception);
            }

            return new static(static::defaults());
        });
    }

    public static function defaults(): array
    {
        return [
            'registration_gift_enabled' => true,
            'registration_gift_tokens' => 50,
            'prelogin_credit_text' => 'هدیه ۵۰ اعتبار',
            'registration_sms_enabled' => true,
            'registration_gift_review_repeated_ip' => true,
            'registration_gift_review_repeated_device' => true,
            'registration_gift_cooldown_days' => 90,
            // لینک رفرال باید در نصب تازه از ابتدا قابلیت ثبت بازدید و انتساب داشته باشد.
            // خاموش‌کردن کمپین همچنان از بخش تنظیمات داشبورد ممکن است.
            'referral_enabled' => true,
            'invitee_reward_tokens' => 0,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'registration',
            'referral_discount_percent' => 10,
            'purchase_commission_percent' => 10,
            'attribution_window_days' => 30,
            'review_repeated_ip' => true,
            'review_repeated_device' => true,
            'profile_enabled' => false,
            'profile_title' => 'همکاری در فروش وطن',
            'profile_subtitle' => 'لینکت را به اشتراک بگذار؛ دوستت هدیه می‌گیرد و تو پاداش.',
            'profile_description' => 'لینک اختصاصی خودت را در شبکه‌های اجتماعی یا برای دوستانت بفرست. هر کاربر جدیدی که با لینک تو ثبت‌نام کند، دعوت موفق ثبت می‌شود و پاداش توکنی‌ات خودکار به حسابت می‌آید.',
            'share_message' => 'با لینک دعوت من به وطن بپیوند، ابزارهای هوش مصنوعی را تجربه کن و هدیه شروع بگیر: {referral_link}',
        ];
    }

    public function referralIsActive(): bool
    {
        return $this->referral_enabled
            && (! $this->campaign_starts_at || $this->campaign_starts_at->lte(now()))
            && (! $this->campaign_ends_at || $this->campaign_ends_at->gte(now()));
    }
}
