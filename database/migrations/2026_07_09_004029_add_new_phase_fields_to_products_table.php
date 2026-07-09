<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // فیلدهای اطلاعات اصلی و آینده
            $table->string('new_status')->default('draft')->after('status'); // draft, active, inactive
            $table->integer('new_display_order')->default(1)->after('new_status');
            $table->string('new_internal_code')->nullable()->after('new_display_order');
            $table->text('new_admin_note')->nullable()->after('new_internal_code');

            // برچسب‌های توسعه فاز بعد
            $table->boolean('new_is_premium')->default(false);
            $table->boolean('new_is_recommended')->default(false);
            $table->boolean('new_is_beta')->default(false);

            // رسانه‌ها و ظاهر کارت
            $table->string('new_product_icon')->nullable(); 
            $table->string('new_card_color')->default('#A07AF5');
            $table->string('new_gallery_preview_mode')->default('grid'); // grid, slider, carousel

            // تنظیمات پیشرفته واترمارک
            $table->string('new_watermark_corner_precise')->default('tr'); // tl, tr, bl, br
            $table->integer('new_watermark_opacity')->default(70);
            $table->integer('new_watermark_size')->default(30);
            $table->string('new_watermark_type')->default('logo'); // logo, text
            $table->string('new_watermark_text_color')->default('#FFFFFF');

            // منطق قیمت‌گذاری و محدودیت‌ها
            $table->integer('new_min_credit_required')->default(0);
            $table->integer('new_max_run_per_user')->nullable(); // NULL یعنی بدون محدودیت
            $table->boolean('new_show_free_badge')->default(false);
            $table->string('new_price_custom_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'new_status', 'new_display_order', 'new_internal_code', 'new_admin_note',
                'new_is_premium', 'new_is_recommended', 'new_is_beta',
                'new_product_icon', 'new_card_color', 'new_gallery_preview_mode',
                'new_watermark_corner_precise', 'new_watermark_opacity', 'new_watermark_size',
                'new_watermark_type', 'new_watermark_text_color',
                'new_min_credit_required', 'new_max_run_per_user', 'new_show_free_badge', 'new_price_custom_label'
            ]);
        });
    }
};