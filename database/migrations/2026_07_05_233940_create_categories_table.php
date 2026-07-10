<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ساختار نهایی جدول دسته‌بندی وطن
     * ─────────────────────────────────────────────────────────────
     * • درختی (Tree) با parent_id خود-ارجاع
     * • بدون حذف فیزیکی — فقط is_active (فعال/غیرفعال)
     * • فیلدهای دوزبانه name_fa / name_en + name (سازگاری با پنل فعلی)
     * • slug انگلیسی کوتاه؛ یکتایی در سطح هر والد (parent_id + slug)
     * • path مسیر کامل سئو (مثل business/logo) و یکتا در کل جدول
     * • فیلدهای کامل سئو، قابل شخصی‌سازی از پنل مدیریت
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            // ─── ساختار درختی ───
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            // ─── نام‌ها و شناسه‌ها ───
            $table->string('name');                 // نام نمایشی (=name_fa) — برای سازگاری پنل/محصول فعلی
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('slug');                 // انگلیسی، کوتاه، یکتا در سطح والد
            $table->string('path')->nullable();     // مسیر کامل سئو‌فرندلی: business/logo

            // ─── ظاهر ───
            $table->string('icon')->default('folder');   // نام آیکون Lucide
            $table->string('color', 32)->default('#6B7280');
            $table->string('image')->nullable();         // مسیر لوکال تصویر (بدون CDN)

            // ─── ترتیب و وضعیت ───
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            // ─── سئو (در صورت null، مقدار پیش‌فرض هوشمند در مدل ساخته می‌شود) ───
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image')->nullable();

            $table->timestamps();

            // ─── ایندکس‌ها و یکتایی‌ها ───
            $table->unique('path');                       // یکتایی سراسری مسیر سئو
            $table->unique(['parent_id', 'slug']);        // یکتایی slug در سطح هر والد
            $table->index(['parent_id', 'is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
