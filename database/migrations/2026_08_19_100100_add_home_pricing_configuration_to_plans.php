<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plans', 'home_pricing_config')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->json('home_pricing_config')->nullable()->after('card_style');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('plans', 'home_pricing_config')) {
            Schema::table('plans', function (Blueprint $table): void {
                $table->dropColumn('home_pricing_config');
            });
        }
    }
};
