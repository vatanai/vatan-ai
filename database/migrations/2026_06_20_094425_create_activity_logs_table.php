<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * اجرای مایگریشن - ساخت جدول لاگ فعالیت‌های کاربران
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // کاربر مربوطه - nullable چون ممکنه کاربر هنوز مهمان باشه (قبل از ثبت‌نام)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // نوع رویداد. مثال‌ها:
            // register, login, login_failed, logout,
            // otp_sent, otp_verified, otp_failed,
            // upload, generate_attempt, generate_success, generate_failed,
            // validation_error, unauthenticated_attempt
            $table->string('type', 50)->index();

            // پیام قابل‌نمایش فارسی برای ادمین (مثلاً: "ثبت‌نام کرد", "تصویر آپلود کرد")
            $table->string('message');

            // ارتباط اختیاری با رکورد Generation (در صورتی که رویداد به یک جنریت خاص مربوط باشه)
            $table->foreignId('generation_id')->nullable()->constrained('generations')->nullOnDelete();

            // ارتباط اختیاری با Prompt (سبک انتخاب شده)
            $table->foreignId('prompt_id')->nullable();

            // داده‌های اضافی به‌صورت JSON - شماره موبایل، آیپی، خطای دقیق، یوزرایجنت و غیره
            $table->json('meta')->nullable();

            // وضعیت کلی رویداد برای فیلتر سریع در پنل ادمین
            $table->enum('level', ['info', 'success', 'warning', 'error'])->default('info')->index();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * بازگشت مایگریشن
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};