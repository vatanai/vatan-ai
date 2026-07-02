<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            // ۱. حذف کلید خارجی قدیمی که به جدول products وصل بود
            // اگر نام کلید خارجی در دیتابیس شما فرق دارد، فقط ['product_id'] را بگذارید، لاراول خودش نام را پیدا می‌کند
            $table->dropForeign(['product_id']);
            
            // ۲. تغییر نام ستون یا ساخت ستون جدید برای پرامپت‌ها
            $table->renameColumn('product_id', 'prompt_id');

            // ۳. ایجاد رابطه کلید خارجی جدید به جدول درست یعنی prompts
            $table->foreign('prompt_id')->references('id')->on('prompts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropForeign(['prompt_id']);
            $table->renameColumn('prompt_id', 'product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }
};