<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'pipeline_enabled')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->boolean('pipeline_enabled')->default(false)->after('output_quality_selector_enabled');
            });
        }

        // انتخاب کیفیت خروجی باید برای محصولات قبلی هم بازگردد؛ مدیر همچنان می‌تواند
        // آن را برای هر محصول از گام اول خاموش کند.
        if (Schema::hasColumn('products', 'output_quality_selector_enabled')) {
            DB::table('products')->update(['output_quality_selector_enabled' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'pipeline_enabled')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('pipeline_enabled');
            });
        }
    }
};
