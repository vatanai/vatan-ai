<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * فعال‌سازی اولیه‌ی سیستم رفرال برای دیتابیس‌هایی که با مقدار پیش‌فرض قبلی
     * ساخته شده‌اند. مدیر بعد از این مرحله همچنان می‌تواند کمپین را خاموش کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('referral_settings') || ! Schema::hasColumn('referral_settings', 'referral_enabled')) {
            return;
        }

        DB::table('referral_settings')
            ->where('referral_enabled', false)
            ->update(['referral_enabled' => true, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // بازگرداندن خودکار این گزینه می‌تواند انتخاب مدیر را بعد از rollback
        // ناخواسته خاموش کند؛ فعال/غیرفعال‌کردن از داشبورد انجام می‌شود.
    }
};
