<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حالت‌های نمایش کاشیِ محصول در اکسپلور و اپ.
 * آرایه‌ای از حالت‌های فعال: 1x1 (مربع)، 2x2 (بزرگ)، 1x2 (عمودی)، 2x1 (افقی).
 * NULL/خالی یعنی «همه حالت‌ها مجاز» (سازگاری با محصولات قبلی).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('explore_tiles')->nullable()->after('gallery_layout');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('explore_tiles');
        });
    }
};
