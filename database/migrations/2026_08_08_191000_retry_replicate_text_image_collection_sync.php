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
        // داده‌های کاتالوگ عملیاتی هستند و rollback نباید آن‌ها را حذف کند.
    }
};
