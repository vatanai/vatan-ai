<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // هویت و محتوا
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_fa')->nullable();
            $table->string('category');
            $table->json('tags')->nullable();
            $table->string('status')->default('active');

            // رسانه و دمو
            $table->string('thumbnail_path')->nullable();
            $table->json('sample_images')->nullable();
            $table->boolean('show_before_after')->default(true);

            // تنظیمات اختصاصی هوش مصنوعی
            $table->string('primary_model')->default('test-model');
            $table->string('media_type')->default('photo'); // نوع عکس مجاز
            $table->text('prompt_template');
            $table->text('negative_prompt')->nullable();
            $table->string('button_text')->default('ساخت تصویر'); // متن دکمه ساخت تصویر

            // سیستم قیمت‌گذاری و توکن (بخش عملکردی فعلی + فیلدهای آینده محسن)
            $table->boolean('is_free')->default(false);
            $table->integer('credit_cost')->default(0); // تعداد اعتبار مصرفی
            $table->integer('single_price')->default(0); // قیمت تکی (غیرفعال فعلی)
            $table->string('required_subscription_tier')->nullable(); // اشتراک خاص (غیرفعال فعلی)

            // فیلدهای بک‌اندر سیستمی (دارای مقدار پیش‌فرض جهت عدم خرابی مدل‌های قبل)
            $table->string('description_en')->nullable();
            $table->string('subcategory')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(true);
            $table->boolean('is_trending')->default(false);
            $table->string('output_type')->default('image');
            $table->string('output_format')->default('png');
            $table->integer('output_count')->default(1);
            $table->string('resolution')->default('1024x1024');
            $table->string('aspect_ratio')->default('1:1');
            $table->string('delivery_method')->default('instant');
            $table->integer('estimated_time')->default(30);
            $table->boolean('watermark_enabled')->default(false);
            $table->string('watermark_position')->default('corner');
            $table->string('pricing_model')->default('per_credit');
            $table->string('price_tier')->default('standard');
            $table->integer('discount_percent')->default(0);
            $table->string('display_mode')->default('card');
            $table->string('card_shape')->default('portrait');
            $table->string('gallery_layout')->default('grid');
            $table->string('platform')->default('both');
            $table->string('accent_color')->default('#a07af5');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};