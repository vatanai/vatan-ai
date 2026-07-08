<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: کمپین/بنر تبلیغاتی — محتوای خودکفا (بدون نیاز به محصول)، مستقیم از ادمین مدیریت می‌شود ──
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title_fa');
            $table->string('image'); // مسیر روی دیسک public (مثل thumbnail محصولات)
            $table->string('link')->nullable(); // مقصد کلیک (لینک محصول/دسته/خارجی)
            $table->unsignedTinyInteger('weight')->default(50); // وزن در انتخاب/رتبه‌بندی
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_campaigns');
    }
};
