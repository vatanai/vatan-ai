<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feed_settings') && ! Schema::hasColumn('feed_settings', 'product_ratio')) {
            Schema::table('feed_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('product_ratio')->default(65)->after('campaign_ratio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('feed_settings') && Schema::hasColumn('feed_settings', 'product_ratio')) {
            Schema::table('feed_settings', function (Blueprint $table): void {
                $table->dropColumn('product_ratio');
            });
        }
    }
};
