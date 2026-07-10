<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // اضافه کردن فیلدهای جدید با رعایت ترتیب قرارگیری (after)
            $table->string('plan_code')->unique()->after('id'); // کد یکتای پلن مانند PRO001
            $table->string('slug')->unique()->after('name'); // اسلاگ آدرس
            $table->text('short_description')->nullable()->after('tokens'); // توضیح کوتاه کارت
            $table->longText('description')->nullable()->after('short_description'); // توضیحات کامل
            $table->string('icon')->default('fa-solid fa-gem')->after('image_path'); // آیکون فونت‌آوسم
            $table->string('card_color')->default('#a07af5')->after('icon'); // رنگ پس‌زمینه/کادر کارت
            $table->string('badge_text')->nullable()->after('card_color'); // بج مانند: ویژه، پرفروش
            $table->json('tags')->nullable()->after('badge_text'); // تگ‌ها به صورت آرایه جی‌سان
            $table->json('features')->nullable()->after('tags'); // ویژگی‌های داینامیک به صورت آرایه جی‌سان
            $table->integer('sort_order')->default(0)->after('features'); // اولویت ترتیب نمایش
            $table->enum('status', ['draft', 'active', 'inactive'])->default('active')->after('sort_order'); // وضعیت انتشار
            $table->unsignedInteger('version')->default(1)->after('status'); // نسخه‌بندی پلن

            // حذف فیلد قدیمی is_active برای جلوگیری از همپوشانی با وضعیت status
            if (Schema::hasColumn('plans', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // حذف فیلدهای اضافه شده در صورت رول‌بک
            $table->dropColumn([
                'plan_code',
                'slug',
                'short_description',
                'description',
                'icon',
                'card_color',
                'badge_text',
                'tags',
                'features',
                'sort_order',
                'status',
                'version'
            ]);

            // بازگرداندن فیلد قدیمی در صورت رول‌بک
            $table->boolean('is_active')->default(true)->after('image_path');
        });
    }
};