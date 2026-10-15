<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * اطمینان از utf8mb4 برای همه‌ی جدول‌های اینستاگرام هوشمند (ایموجی ۴بایتی مثل 😍🔥 در utf8 قدیمی ذخیره نمی‌شود).
 * فقط روی MySQL/MariaDB و فقط جدولی که هنوز utf8mb4 نیست؛ تکرار اجرا بی‌اثر است.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $tables = DB::select(
            "SELECT TABLE_NAME AS name, TABLE_COLLATION AS collation FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'instagram\\_%'"
        );
        foreach ($tables as $table) {
            if (str_starts_with((string) $table->collation, 'utf8mb4')) {
                continue;
            }
            try {
                DB::statement('ALTER TABLE `'.str_replace('`', '', $table->name).'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            } catch (\Throwable $e) {
                Log::warning('utf8mb4 conversion failed', ['table' => $table->name, 'error' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
    }
};
