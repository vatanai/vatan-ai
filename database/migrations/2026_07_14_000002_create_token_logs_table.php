<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * جدول تاریخچه‌ی تغییرات دستی توکن کاربران توسط ادمین
     * (افزودن / کسر / تنظیم مستقیم موجودی)
     */
    public function up(): void
    {
        if (Schema::hasTable('token_logs')) {
            return;
        }

        Schema::create('token_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('action', ['add', 'deduct', 'set']);
            $table->integer('amount'); // مقداری که وارد شده (همیشه مثبت)
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_logs');
    }
};
