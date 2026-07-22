<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول Sectionهای صفحه Home (فیچر Home Builder).
 * عمداً با ستون page_key ساخته شده تا در آینده برای صفحات دیگر هم قابل استفاده باشد،
 * بدون نیاز به migration جدید. تنظیمات اختصاصی هر نوع Section در ستون settings (JSON)
 * ذخیره می‌شود تا افزودن فیلد جدید در آینده هم نیازی به تغییر ساختار جدول نداشته باشد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();

            // صفحه‌ای که این Section به آن تعلق دارد — پیش‌فرض صفحه اصلی اپ.
            // آماده‌سازی برای توسعه‌ی آینده به صفحات دیگر (طبق درخواست فایل مشخصات فیچر).
            $table->string('page_key')->default('app_home')->index();

            // نوع Section: hero | product_slider | product_grid | category_slider | banner | collection | text | spacer
            $table->string('type')->index();

            // کلید لایوت انتخاب‌شده برای این نوع (از config/home_builder.php)
            $table->string('layout')->default('default');

            $table->string('title_fa')->nullable();
            $table->string('subtitle_fa')->nullable();

            // تنظیمات اختصاصی هر نوع Section (دسته‌بندی، تعداد آیتم، مرتب‌سازی، رنگ، فاصله و...)
            $table->json('settings')->nullable();

            // نمایش/عدم‌نمایش در دسکتاپ/تبلت/موبایل + لایوت اختصاصی موبایل
            $table->json('responsive')->nullable();

            // draft | published | hidden
            $table->string('status')->default('draft')->index();

            // ترتیب نمایش عمودی در صفحه
            $table->unsignedInteger('position')->default(0)->index();

            // برای آماده‌سازی معماری Schedule Publish در آینده (فعلاً فقط زمان انتشار واقعی ثبت می‌شود)
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
