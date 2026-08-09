<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // همگام‌سازی provider یک عملیات اجرایی است، نه migration دیتابیس.
    }

    public function down(): void
    {
        // قابلیت‌های مدل از کاتالوگ خارجی هستند و نباید هنگام rollback حذف شوند.
    }
};
