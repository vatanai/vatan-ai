<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prompt extends Model
{
    use HasFactory;

    // نام جدول در دیتابیس
    protected $table = 'prompts';

    // فیلدهای مجاز برای پر کردن به صورت انبوه
    protected $fillable = [
        'user_id', // این مورد اضافه شود
        'name',
        'prompt',
        'description',
        'image',
        'is_active',
    ];

    // تبدیل خودکار نوع داده‌ای وضعیت فعال بودن به بویلین
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 🟢 رابطه یک‌به‌چند با جدول لاگ‌های هوش مصنوعی (generations)
     * مشخص می‌کند چه تصاویر و دیتای ساختاریافته‌ای (JSON) با این پرامپت تولید شده‌اند.
     */
    public function generations()
    {
        // با توجه به اینکه در مدل Generation نام رابطه را product گذاشته‌اید، 
        // کلید خارجی احتمالاً product_id است.
        return $this->hasMany(Generation::class, 'product_id');
    }
}