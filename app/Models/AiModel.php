<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    use HasFactory;

    /**
     * نام جدول در دیتابیس (در صورتی که نام جدول طبق قاعده پیش‌فرض لاراول
     * یعنی پلورال snake_case کلاس، یعنی "ai_models" است، نوشتن این خط
     * اجباری نیست، ولی برای وضوح بیشتر نگه داشته شده)
     */
    protected $table = 'ai_models';

    /**
     * فیلدهایی که با Mass Assignment (مثل AiModel::create([...])) قابل پر شدن هستند
     */
    protected $fillable = [
        'name',
        'model_id',
        'provider',
        'supports_vision',
        'is_active',
        'fallback_models',
    ];

    /**
     * تبدیل خودکار نوع داده هنگام خواندن/نوشتن از دیتابیس
     * مهم‌ترین مورد: fallback_models باید آرایه باشد نه رشته JSON خام،
     * در غیر این صورت ترتیب اولویت مدل‌ها قابل استفاده در PHP نخواهد بود.
     */
    protected $casts = [
        'supports_vision' => 'boolean',
        'is_active' => 'boolean',
        'fallback_models' => 'array',
    ];
}