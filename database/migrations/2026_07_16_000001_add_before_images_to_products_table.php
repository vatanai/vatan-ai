<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فیلد «عکس‌های قبل» (before_images):
 * جایگزین بخش قدیمی Thumbnail در فرم گام اول ثبت محصول شده است.
 * این ستون JSON لیست مسیر عکس‌های خامی را نگه می‌دارد که مدل هوش مصنوعی
 * با استفاده از آن‌ها ساخته شده — همان تصاویری که در صفحه محصول برای
 * کاربر با عنوان «عکس‌های قبل» نمایش داده می‌شوند:
 * ["products/before_images/xxx.jpg", "products/before_images/yyy.jpg", ...]
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'before_images')) {
                $table->json('before_images')->nullable()->after('sample_outputs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'before_images')) {
                $table->dropColumn('before_images');
            }
        });
    }
};
