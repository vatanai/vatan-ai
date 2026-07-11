<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}