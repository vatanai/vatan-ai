<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ایمپورت یک‌بارهٔ دسته‌بندی‌هایی که توی پنل ادمین لوکال ثبت شده بودن
     * ولی هیچ‌وقت به‌صورت خودکار به Production منتقل نمی‌شدن (دیپلوی فقط
     * کد رو می‌بره، نه ردیف‌های دیتابیس لوکال رو).
     *
     * idempotent است: بر اساس یکی از path های یکتای همین دامپ چک می‌کنه که
     * قبلاً وارد نشده باشه، پس با هر بار migrate دوباره اجرا/تکرار نمی‌شه.
     */
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        $alreadyImported = DB::table('categories')->where('path', 'styles/neon')->exists();
        if ($alreadyImported) {
            return;
        }

        $sqlFile = database_path('data-import/categories.sql');
        if (!file_exists($sqlFile)) {
            return;
        }

        try {
            DB::unprepared(file_get_contents($sqlFile));
        } catch (\Throwable $e) {
            // اگر بخشی از ردیف‌ها (مثلاً به خاطر id تکراری) از قبل موجود بود،
            // کل صف migrate متوقف نشه.
            report($e);
        }
    }

    public function down(): void
    {
        // این migration فقط داده وارد می‌کنه؛ rollback عمداً خالیه تا
        // داده‌های واقعی حذف نشن.
    }
};
