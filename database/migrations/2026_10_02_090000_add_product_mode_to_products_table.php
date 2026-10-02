<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * محصول پروداکتی «سبک جدید» (پک شات) — فقط افزودنی.
 * همه‌ی محصولات فعلی با مقدار پیش‌فرض portrait می‌مانند و هیچ ستون قبلی معنایش عوض نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'product_mode')) {
                $table->string('product_mode', 20)->default('portrait')->index();
            }
            if (! Schema::hasColumn('products', 'shot_settings')) {
                // نیش، توضیح فیزیکی محصول، سبک برند و پالت؛ فقط برای product_mode=product خوانده می‌شود.
                $table->json('shot_settings')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'product_mode')) {
                $table->dropIndex(['product_mode']);
                $table->dropColumn('product_mode');
            }
            if (Schema::hasColumn('products', 'shot_settings')) {
                $table->dropColumn('shot_settings');
            }
        });
    }
};
