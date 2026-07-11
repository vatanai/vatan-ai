<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * تلاش دوم برای ایمپورت دسته‌بندی‌های لوکال.
     *
     * دلیل شکست تلاش قبلی (migration 2026_07_11_999999): فایل دامپ یک
     * INSERT بزرگ تک‌دستوری بود با idهای ثابت؛ چون خیلی از این ردیف‌ها
     * (همون‌هایی که از سیدر پایه مشترکن) از قبل روی Production با همون id
     * وجود داشتن، اولین برخورد به یک id تکراری کل دستور رو fail می‌کرد و
     * هیچ ردیفی—even ردیف‌های واقعاً جدید—اضافه نمی‌شد. علاوه بر این،
     * چون خطا داخل try/catch قورت داده می‌شد، migration بدون خطا "Ran"
     * علامت می‌خورد و دیگه دوباره اجرا نمی‌شد.
     *
     * فایل database/data-import/categories.sql حالا به INSERT IGNORE
     * تغییر کرده: ردیف‌های تکراری فقط skip می‌شن، بقیه (ردیف‌های واقعاً
     * جدید مثل چیزهایی که از پنل ادمین لوکال اضافه شدن) درست insert می‌شن.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        $sqlFile = database_path('data-import/categories.sql');
        if (!file_exists($sqlFile)) {
            return;
        }

        DB::unprepared(file_get_contents($sqlFile));
    }

    public function down(): void
    {
        // این migration فقط داده وارد می‌کنه؛ rollback عمداً خالیه.
    }
};
