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

            // ─── گام اول: هویت و رسانه ───
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_fa')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->json('tags')->nullable(); // ذخیره تگ‌ها به صورت آرایه جی‌سان
            
            // فلگ‌های وضعیت نمایش
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(true);
            $table->boolean('is_trending')->default(false);
            
            // فایل‌های رسانه‌ای
            $table->string('thumbnail');
            $table->string('cover')->nullable();
            $table->json('sample_outputs')->nullable(); // ذخیره مسیر تا ۱۰ تصویر نمونه
            $table->enum('media_type', ['photo', 'video', 'both'])->default('photo');
            $table->string('preview_video_url')->nullable();

            // ─── گام دوم: پایپ‌لاین هوش مصنوعی و پرامپت ───
            $table->string('primary_model');
            $table->integer('timeout')->default(60);
            $table->string('pipeline_type')->default('image_generation');
            $table->json('fallback_models')->nullable(); // لیست مدل‌های بک‌آپ
            $table->text('prompt_template');
            $table->text('negative_prompt')->nullable();
            
            // تگ‌های سوییچ تنظیمات AI
            $table->boolean('show_prompt_to_user')->default(false);
            $table->boolean('face_swap_enabled')->default(false);
            $table->boolean('multi_step_pipeline')->default(false);
            
            // سازنده فیلدهای ورودی پویا (Input Schema Builder)
            $table->json('input_schema')->nullable();

            // ─── گام سوم: خروجی، قیمت‌گذاری و دکوراسیون فرانت ───
            $table->string('output_type')->default('image');
            $table->string('output_format')->default('jpg');
            $table->integer('output_count')->default(1);
            $table->string('resolution')->default('1024×1024');
            $table->string('aspect_ratio')->default('1:1');
            $table->string('delivery_method')->default('instant');
            $table->integer('estimated_time')->default(30);
            $table->boolean('watermark_enabled')->default(true);
            $table->string('watermark_position')->default('corner');

            // قیمت‌گذاری
            $table->string('pricing_model')->default('per_credit');
            $table->integer('credit_cost')->default(5);
            $table->string('price_tier')->default('standard');
            $table->integer('discount_percentage')->default(0);
            $table->boolean('is_free')->default(false);

            // تنظیمات نمایش و دیزاین
            $table->string('display_mode')->default('card');
            $table->string('card_shape')->default('portrait');
            $table->string('gallery_layout')->default('grid');
            $table->string('card_label')->nullable();
            $table->string('platform')->default('both');
            $table->string('accent_color')->default('#a07af5');
            $table->boolean('show_before_after')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};