<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رفع خطای «Unknown column generations.product_id».
 *
 * مهاجرتِ قدیمیِ 2026_06_22_144910_fix_generations_foreign_key_columns
 * ستون product_id را در جدول generations به prompt_id تغییر نام داده بود،
 * اما کل کدِ برنامه (LogService، مدل Generation، مدل Product و ProductController)
 * همچنان از product_id استفاده می‌کند. این مهاجرت نام ستون را به product_id برمی‌گرداند
 * تا همه‌جا هماهنگ شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        // اگر از قبل ستون درست وجود دارد کاری نکن
        if (Schema::hasColumn('generations', 'product_id')) {
            return;
        }

        if (Schema::hasColumn('generations', 'prompt_id')) {
            // ۱) حذف کلید خارجیِ prompt_id (اگر وجود دارد) تا rename بدون خطا انجام شود
            try {
                Schema::table('generations', function (Blueprint $table) {
                    $table->dropForeign(['prompt_id']);
                });
            } catch (\Throwable $e) {
                // کلید خارجی وجود نداشت — مشکلی نیست
            }

            // ۲) تغییر نام ستون به product_id
            Schema::table('generations', function (Blueprint $table) {
                $table->renameColumn('prompt_id', 'product_id');
            });

            // ۳) تلاش برای افزودن کلید خارجی به جدول products
            //    (در صورت وجود دادهٔ ناسازگار نادیده گرفته می‌شود تا مهاجرت متوقف نشود)
            try {
                Schema::table('generations', function (Blueprint $table) {
                    $table->foreign('product_id')
                          ->references('id')->on('products')
                          ->onDelete('cascade');
                });
            } catch (\Throwable $e) {
                // اگر دادهٔ قدیمیِ ناسازگار وجود داشته باشد، فقط کلید خارجی اضافه نمی‌شود
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('generations', 'prompt_id') && Schema::hasColumn('generations', 'product_id')) {
            try {
                Schema::table('generations', function (Blueprint $table) {
                    $table->dropForeign(['product_id']);
                });
            } catch (\Throwable $e) {
            }

            Schema::table('generations', function (Blueprint $table) {
                $table->renameColumn('product_id', 'prompt_id');
            });
        }
    }
};
