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
        // این کاتالوگ دادهٔ عملیاتی است و rollback نباید مدل‌های ثبت‌شده را پاک کند.
    }
};
