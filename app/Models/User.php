<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * ویژگی‌هایی که به صورت گروهی قابل مقداردهی و ذخیره هستند.
     */
    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'password',
        'password_reveal',
        'avatar',
        'status',
        'customer_segment',
        'tokens',            // موجودی فعلی توکن
        'tokens_purchased',  // کل توکن‌های خریداری شده از اول تا الان
        'tokens_used',       // کل توکن‌های مصرف شده
        'promotional_tokens', // بخش باقی‌مانده از اعتبار هدیه در موجودی فعلی
        'plan_id',           // پلن فعلی کاربر (ارتباط با جدول plans)
        'referral_earnings', // موجودی درآمد رفرال به تومان
        'referral_code',
        'referred_by',
        'referral_attributed_at',
        'registered_at',
        'telegram_gift_claimed_at',
        'last_login_at',
        'login_count',
    ];

    /**
     * ویژگی‌هایی که باید در خروجی‌های آرایه یا JSON مخفی بمانند.
     */
    protected $hidden = [
        'password',
        'password_reveal',
        'remember_token',
    ];

    /**
     * تبدیل فرمت داده‌های دیتابیس به داده‌های استاندارد در زبان PHP.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'password_reveal' => 'encrypted',
            'tokens' => 'integer',
            'tokens_purchased' => 'integer',
            'tokens_used' => 'integer',
            'promotional_tokens' => 'integer',
            'referral_earnings' => 'integer',
            'referral_attributed_at' => 'datetime',
            'registered_at' => 'datetime',
            'telegram_gift_claimed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'login_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->referral_code ??= Str::upper(Str::random(10));
        });
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                return PhoneNumber::normalize($value === null ? null : (string) $value);
            },
        );
    }

    /** موجودی استاندارد برای تمام نقاط نمایش سایت. */
    public function getTokenBalanceAttribute(): int
    {
        return (int) $this->tokens;
    }

    /** موجودی قابل‌استفاده پس از کنار گذاشتن دسته‌های هدیه‌ی منقضی. */
    public function getEffectiveTokenBalanceAttribute(): int
    {
        $balance = (int) $this->getRawOriginal('tokens');
        try {
            if (Schema::hasTable('user_token_grants')) {
                $expired = $this->relationLoaded('tokenGrants')
                    ? (int) $this->tokenGrants->filter(fn ($grant) => (int) $grant->remaining_amount > 0 && $grant->expires_at && $grant->expires_at->isPast())->sum('remaining_amount')
                    : (int) $this->tokenGrants()->where('remaining_amount', '>', 0)->whereNotNull('expires_at')->where('expires_at', '<=', now())->sum('remaining_amount');
                $balance -= $expired;
            }
        } catch (\Throwable $exception) {
            // پیش از اجرای migration جدید، همان موجودی legacy نمایش داده می‌شود.
        }
        return max(0, $balance);
    }

    /** بخش قابل‌مصرف اعتبار هدیه از موجودی؛ هرگز از موجودی کل بزرگ‌تر نیست. */
    public function promotionalTokenBalance(): int
    {
        return max(0, min((int) $this->promotional_tokens, (int) $this->tokens));
    }

    /** اعتبار خریداری‌شده یا دستیِ قابل استفاده برای همه مدل‌ها. */
    public function paidTokenBalance(): int
    {
        return max(0, (int) $this->tokens - $this->promotionalTokenBalance());
    }

    /**
     * رابطه با تصاویر خلق شده توسط هوش مصنوعی (ساب‌تب: خلق شده)
     */
    public function generatedImages(): HasMany
    {
        return $this->hasMany(GeneratedImage::class, 'user_id');
    }

    /** آخرین ورود موفق ثبت‌شده، همراه با شماره‌ای که برای ورود استفاده شده است. */
    public function lastSuccessfulLogin(): HasOne
    {
        return $this->hasOne(AuthEvent::class)
            ->where('event', 'login_success')
            ->where('successful', true)
            ->latestOfMany('occurred_at');
    }

    /**
     * رابطه با تصاویر شخصی آپلود شده توسط کاربر (ساب‌تب: عکس‌های شخصی)
     */
    public function uploadedImages(): HasMany
    {
        return $this->hasMany(UserUpload::class, 'user_id');
    }

    public function faceProfiles(): HasMany
    {
        return $this->hasMany(FaceProfile::class);
    }

    public function generatedVideos(): HasMany
    {
        return $this->hasMany(GeneratedVideo::class);
    }

    /**
     * پلن فعلی کاربر (در صورت null، کاربر پلن رایگان محسوب می‌شود)
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** نام استاندارد پلن برای تمام بخش‌های کاربری؛ نبود یا نام خالی یعنی پلن رایگان. */
    public function getPlanDisplayNameAttribute(): string
    {
        return trim((string) ($this->plan?->name ?? '')) ?: 'رایگان';
    }

    public function planPurchases(): HasMany
    {
        return $this->hasMany(PlanPurchase::class);
    }

    public function financeCases(): HasMany
    {
        return $this->hasMany(FinanceCase::class);
    }

    public function financeCreditLots(): HasMany
    {
        return $this->hasMany(FinanceCreditLot::class);
    }

    public function tokenGrants(): HasMany
    {
        return $this->hasMany(UserTokenGrant::class);
    }

    public function telegramUser(): HasOne
    {
        return $this->hasOne(TelegramUser::class);
    }

    public function hasFreePlan(): bool
    {
        return ($this->plan?->model_tier_key ?? 'free') === 'free';
    }

    public function faceProfileLimit(): int
    {
        return max(0, (int) ($this->plan?->face_profile_limit ?? 0));
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    public function invitedUsers(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    /** نسبت‌دادن ثبت‌نام کاربر به لینک دعوت؛ برای نمایش منبع جذب در پنل مدیریت. */
    public function referralConversion(): HasOne
    {
        return $this->hasOne(ReferralConversion::class, 'invitee_id');
    }

    public function referralVisits(): HasMany
    {
        return $this->hasMany(ReferralVisit::class, 'inviter_id');
    }

    public function referralRewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    public function getReferralUrlAttribute(): string
    {
        return route('referral.visit', ['code' => $this->referral_code]);
    }

    /**
     * محصولات ذخیره‌شده (سیو) توسط این کاربر — بخش «ذخیره شده‌ها» در صفحه پروفایل.
     */
    public function savedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'saved_products')->withTimestamps();
    }

    /**
     * آیا این کاربر محصول مشخص‌شده را قبلاً سیو کرده است؟
     */
    public function hasSavedProduct(int $productId): bool
    {
        return $this->savedProducts()->where('product_id', $productId)->exists();
    }

    /**
     * محصولات لایک‌شده توسط این کاربر — دکمه قلب (لایک) صفحه محصول.
     */
    public function likedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'liked_products')->withTimestamps();
    }

    /**
     * آیا این کاربر محصول مشخص‌شده را قبلاً لایک کرده است؟
     */
    public function hasLikedProduct(int $productId): bool
    {
        return $this->likedProducts()->where('product_id', $productId)->exists();
    }
}
