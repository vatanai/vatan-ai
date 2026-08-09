<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // تماس با provider خارجی نباید بخشی از migration باشد؛ نبود کلید یا
        // اختلال شبکه نباید deploy و migrationهای دیتابیس را متوقف کند.
        // همگام‌سازی از طریق ai:sync-catalog یا دکمهٔ داشبورد اجرا می‌شود.
    }

    public function down(): void
    {
        // کاتالوگ خارجی داده‌ی عملیاتی است؛ rollback نباید مدل‌های قابل‌استفاده را حذف کند.
    }
};
