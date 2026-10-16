<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اسلاید اختصاصی محصول (عکس نمونه + پرامپت ساخت مخصوص همان محصول).
 *
 * هر اسلاید اختصاصی یک ردیف shot_library با product_id است تا همه‌ی مسیرهای موجود
 * (صفحه‌ی بساز، ساخت، پیش‌نمایش، QC) بدون تغییر با آن کار کنند؛ ردیف‌های product_id دار
 * در کتابخانه‌ی عمومی و پیشنهاد بات دیده نمی‌شوند. کاملاً افزودنی و تکرارپذیر.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_library') || Schema::hasColumn('shot_library', 'product_id')) {
            return;
        }

        Schema::table('shot_library', function (Blueprint $table): void {
            $table->unsignedBigInteger('product_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('shot_library') && Schema::hasColumn('shot_library', 'product_id')) {
            Schema::table('shot_library', function (Blueprint $table): void {
                $table->dropIndex(['product_id']);
                $table->dropColumn('product_id');
            });
        }
    }
};
