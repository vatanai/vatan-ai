<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ویژگی «مدل‌های خروجی چندگانه» (Output Variants):
 * بعضی محصولات چند مدل/سبک خروجی مختلف دارند (مثلاً چند فضاسازی متفاوت برای شیشه عطر).
 * این ستون JSON لیست واریانت‌ها را نگه می‌دارد:
 * [{ "key": "v_xxx", "title": "...", "image": "products/variants/...", "prompt": "..." }, ...]
 * کاربر در صفحه ساخت انتخاب می‌کند کدام مدل‌ها ساخته شوند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'output_variants')) {
                $table->json('output_variants')->nullable()->after('output_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'output_variants')) {
                $table->dropColumn('output_variants');
            }
        });
    }
};
