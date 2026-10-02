<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کتابخانه‌ی مشترک شات (Shot Library) و اتصال شات‌ها به هر محصول پروداکتی.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_library')) {
            Schema::create('shot_library', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 80)->unique();
                $table->string('name_fa');
                $table->string('name_en')->nullable();
                $table->text('description_fa')->nullable();
                // hero / lifestyle / model / detail / flatlay / ...
                $table->string('category', 40)->default('hero')->index();
                $table->json('niche_tags')->nullable();
                // توکن‌های «زبان شات»: framing, camera, lens, lighting, surface, props, human, mood
                $table->json('tokens')->nullable();
                // قالب اختیاری اختصاصی؛ خالی = قالب پیش‌فرض ShotPromptBuilder
                $table->text('prompt_template')->nullable();
                $table->unsignedInteger('default_credits')->default(12);
                $table->string('aspect_ratio_default', 10)->default('4:5');
                $table->string('sample_image')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('product_shots')) {
            Schema::create('product_shots', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('shot_id')->index();
                $table->boolean('enabled')->default(true);
                // آیا در «بسته‌ی آماده» از پیش تیک‌خورده باشد
                $table->boolean('is_default')->default(true);
                $table->unsignedInteger('credits_override')->nullable();
                // تصویر نمونه‌ی همین شات برای همین محصول (پیش‌نمایش با عکس تست ادمین)
                $table->string('sample_image')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'shot_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_shots');
        Schema::dropIfExists('shot_library');
    }
};
