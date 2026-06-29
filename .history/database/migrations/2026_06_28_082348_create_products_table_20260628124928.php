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
            // هویت
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('slug')->unique();
            $table->text('description_fa')->nullable();
            $table->text('description_en')->nullable();
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->string('status')->default('draft');
            $table->json('tags')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(true);
            $table->boolean('is_trending')->default(false);

            // رسانه
            $table->string('thumbnail_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->json('sample_images')->nullable();
            $table->string('media_type')->default('photo');
            $table->string('preview_video_url')->nullable();

            // هوش مصنوعی
            $table->string('primary_model');
            $table->integer('primary_timeout')->default(60);
            $table->string('primary_type');
            $table->json('fallback_models')->nullable();
            $table->text('prompt_template');
            $table->text('negative_prompt')->nullable();
            $table->boolean('show_prompt_to_user')->default(false);
            $table->boolean('face_swap_enabled')->default(false);
            $table->boolean('multi_step_pipeline')->default(false);
            $table->json('input_schema')->nullable();

            // خروجی
            $table->string('output_type');
            $table->string('output_format');
            $table->integer('output_count')->default(1);
            $table->string('resolution');
            $table->string('aspect_ratio');
            $table->string('delivery_method')->default('instant');
            $table->integer('estimated_time')->default(30);
            $table->boolean('watermark_enabled')->default(true);
            $table->string('watermark_position')->default('corner');

            // قیمت‌گذاری
            $table->string('pricing_model')->default('per_credit');
            $table->integer('credit_cost')->default(0);
            $table->string('price_tier')->default('standard');
            $table->integer('discount_percent')->default(0);
            $table->boolean('is_free')->default(false);

            // نمایش
            $table->string('display_mode')->default('card');
            $table->string('card_shape')->default('portrait');
            $table->string('gallery_layout')->default('grid');
            $table->string('card_badge')->nullable();
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