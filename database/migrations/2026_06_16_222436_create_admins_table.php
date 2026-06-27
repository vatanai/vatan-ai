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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Amir Admin');
            $table->string('email')->unique(); // ایمیل ادمین که یکتا خواهد بود
            $table->string('password'); // رمز عبور که به صورت هش شده ذخیره می‌شود
            $table->rememberToken(); // توکن ریممبر برای بخش لاگین
            $table->timestamps(); // ستون‌های ایجاد شده (created_at) و بروزرسانی (updated_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};