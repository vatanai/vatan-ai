<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'output_quality_selector_enabled')) {
            Schema::table('products', function (Blueprint $table): void {
                // خاموش بودن پیش‌فرض، انتخاب کیفیت را تا زمان فعال‌سازی مدیر پنهان می‌کند.
                $table->boolean('output_quality_selector_enabled')->default(false)->after('allowed_resolutions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'output_quality_selector_enabled')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('output_quality_selector_enabled');
            });
        }
    }
};
