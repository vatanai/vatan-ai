<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feed_settings', function (Blueprint $table) {
            $table->json('include_filters')->nullable()->after('campaign_ratio');
            $table->json('exclude_filters')->nullable()->after('include_filters');
        });
    }

    public function down(): void
    {
        Schema::table('feed_settings', function (Blueprint $table) {
            $table->dropColumn(['include_filters', 'exclude_filters']);
        });
    }
};
