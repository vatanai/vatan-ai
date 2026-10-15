<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بات ثبت محصول: نوع درفت (پرتره یا پروداکتی).
 * additive و امن: همه‌ی درفت‌های قبلی portrait می‌مانند و رفتار مسیر پرتره عوض نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_product_drafts') || Schema::hasColumn('telegram_product_drafts', 'product_mode')) {
            return;
        }

        Schema::table('telegram_product_drafts', function (Blueprint $table): void {
            $table->string('product_mode', 20)->default('portrait')->after('state')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('telegram_product_drafts') || ! Schema::hasColumn('telegram_product_drafts', 'product_mode')) {
            return;
        }

        Schema::table('telegram_product_drafts', function (Blueprint $table): void {
            $table->dropIndex(['product_mode']);
            $table->dropColumn('product_mode');
        });
    }
};
