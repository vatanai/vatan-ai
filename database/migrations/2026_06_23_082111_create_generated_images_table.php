<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_images', function (Blueprint $table) {
            $table->id();
            // اتصال به جدول کاربران (اگر کاربر حذف شد، عکس‌هایش هم پاک شوند)
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // اتصال به جدول پرامپت‌ها/سبک‌ها (اختیاری - برای اینکه بدانیم با چه سبکی ساخته شده)
            $table->foreignId('prompt_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('image_path'); // مسیر ذخیره فایل روی دیسک (مثلاً: uploads/users/user_1/...)
            $table->string('user_prompt')->nullable(); // دستوری که کاربر در تکست‌اریا نوشته بود
            $table->decimal('cost', 8, 4)->default(0); // هزینه پردازش تصویر (صرفاً جهت آمار)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_images');
    }
};