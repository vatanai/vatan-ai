<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فیلدهای سئو برای هر محصول — عنوان متا، توضیحات متا، کلمات کلیدی و تصویر اشتراک‌گذاری (OG).
 * همه اختیاری‌اند؛ در صورت خالی بودن، سیستم هنگام رندر از نام/توضیح/کاور محصول fallback می‌سازد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('description_en');
            $table->string('meta_description', 300)->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'og_image']);
        });
    }
};
