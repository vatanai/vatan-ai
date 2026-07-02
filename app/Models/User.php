<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'tokens',
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
}