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
        Schema::table('ai_models', function (Blueprint $table) {
            // اضافه کردن ستون پشتیبانی از ویژن (پیش‌فرض غیرفعال) بعد از فیلد provider
            $table->boolean('supports_vision')->default(false)->after('provider');
            
            // اضافه کردن ستون مدل‌های جایگزین به صورت متن/JSON
            $table->text('fallback_models')->nullable()->after('supports_vision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn(['supports_vision', 'fallback_models']);
        });
    }
};