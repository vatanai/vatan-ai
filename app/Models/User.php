<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

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
        'password',
        'avatar',
        'status',
        'tokens',            // موجودی فعلی توکن
        'tokens_purchased',  // کل توکن‌های خریداری شده از اول تا الان
        'tokens_used',       // کل توکن‌های مصرف شده
        'plan_id',           // پلن فعلی کاربر (ارتباط با جدول plans)
        'referral_earnings', // موجودی درآمد رفرال به تومان
    ];

    /**
     * ویژگی‌هایی که باید در خروجی‌های آرایه یا JSON مخفی بمانند.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * تبدیل فرمت داده‌های دیتابیس به داده‌های استاندارد در زبان PHP.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tokens' => 'integer',
            'tokens_purchased' => 'integer',
            'tokens_used' => 'integer',
            'referral_earnings' => 'integer',
        ];
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if ($value === null) {
                    return null;
                }

                $phone = strtr(trim((string) $value), [
                    '۰'=>'0', '۱'=>'1', '۲'=>'2', '۳'=>'3', '۴'=>'4',
                    '۵'=>'5', '۶'=>'6', '۷'=>'7', '۸'=>'8', '۹'=>'9',
                    '٠'=>'0', '١'=>'1', '٢'=>'2', '٣'=>'3', '٤'=>'4',
                    '٥'=>'5', '٦'=>'6', '٧'=>'7', '٨'=>'8', '٩'=>'9',
                ]);
                $phone = preg_replace('/[\s\-()]/', '', $phone) ?? $phone;

                if (str_starts_with($phone, '+98')) {
                    return '0' . substr($phone, 3);
                }
                if (str_starts_with($phone, '0098')) {
                    return '0' . substr($phone, 4);
                }

                return preg_match('/^9\d{9}$/', $phone) ? '0' . $phone : $phone;
            },
        );
    }

    /** موجودی استاندارد برای تمام نقاط نمایش سایت. */
    public function getTokenBalanceAttribute(): int
    {
        return (int) $this->tokens;
    }

    /**
     * رابطه با تصاویر خلق شده توسط هوش مصنوعی (ساب‌تب: خلق شده)
     */
    public function generatedImages(): HasMany
    {
        return $this->hasMany(GeneratedImage::class, 'user_id');
    }

    /**
     * رابطه با تصاویر شخصی آپلود شده توسط کاربر (ساب‌تب: عکس‌های شخصی)
     */
    public function uploadedImages(): HasMany
    {
        return $this->hasMany(UserUpload::class, 'user_id');
    }

    /**
     * پلن فعلی کاربر (در صورت null، کاربر پلن رایگان محسوب می‌شود)
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
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
