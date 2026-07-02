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
        Schema::table('users', function (Blueprint $table) {
            // اضافه کردن ستون توکن با مقدار پیش‌فرض ۰ بعد از ستون پسورد
            $table->integer('tokens')->default(0)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حذف ستون توکن در صورت رول‌بک کردن مایگریشن
            $table->dropColumn('tokens');
        });
    }
};