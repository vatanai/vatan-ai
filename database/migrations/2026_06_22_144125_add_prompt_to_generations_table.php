<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            // اضافه کردن ستون prompt برای ذخیره اطلاعات متنی و لاگ‌ها بعد از ستون status
            $table->text('prompt')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('generations', function (Blueprint $table) {
            $table->dropColumn('prompt');
        });
    }
};