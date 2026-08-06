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
            'registration_sms_enabled' => 'boolean',
            'registration_gift_review_repeated_ip' => 'boolean',
            'registration_gift_review_repeated_device' => 'boolean',
            'registration_gift_cooldown_days' => 'integer',
            'referral_enabled' => 'boolean',
            'invitee_reward_tokens' => 'integer',
            'inviter_reward_tokens' => 'integer',
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
            if (Schema::hasTable('referral_settings')) {
                return static::query()->firstOrCreate([], static::defaults());
            }

            return new static(static::defaults());
        });
    }

    public static function defaults(): array
    {
        return [
            'registration_gift_enabled' => true,
            'registration_gift_tokens' => 50,
            'registration_sms_enabled' => true,
            'registration_gift_review_repeated_ip' => true,
            'registration_gift_review_repeated_device' => true,
            'registration_gift_cooldown_days' => 90,
            'referral_enabled' => false,
            'invitee_reward_tokens' => 0,
            'inviter_reward_tokens' => 5,
            'reward_trigger' => 'first_purchase',
            'attribution_window_days' => 30,
            'review_repeated_ip' => true,
            'review_repeated_device' => true,
            'profile_enabled' => false,
            'profile_title' => 'همکاری در فروش وطن',
            'profile_subtitle' => 'لینکت را به اشتراک بگذار؛ دوستت هدیه می‌گیرد و تو پاداش.',
            'profile_description' => 'لینک اختصاصی خودت را در شبکه‌های اجتماعی یا برای دوستانت بفرست. هر کاربر جدیدی که با لینک تو ثبت‌نام کند و اولین خرید موفقش را انجام دهد، یک دعوت موفق برای تو ثبت می‌شود و پاداش توکنی‌ات خودکار به حسابت می‌آید.',
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
