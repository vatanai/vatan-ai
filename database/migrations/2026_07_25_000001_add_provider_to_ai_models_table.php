<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * افزودن ستون `provider` به جدول ai_models
 *
 * این ستون مشخص می‌کند مدل از کدام سرویس‌دهنده فراخوانی می‌شود:
 *   - 'openrouter' : مدل‌های موجود از OpenRouter (پیش‌فرض برای همه ردیف‌های قدیمی)
 *   - 'liara'      : مدل‌های لیارا (OpenAI-compatible API)
 *
 * مقدار پیش‌فرض 'openrouter' است تا همه مدل‌های موجود بدون تغییر کار کنند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            // بعد از ستون is_active اضافه می‌شود
            $table->string('provider', 30)->default('openrouter')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
