<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'allowed_aspect_ratios')) {
                $table->json('allowed_aspect_ratios')->nullable()->after('aspect_ratio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'allowed_aspect_ratios')) {
                $table->dropColumn('allowed_aspect_ratios');
            }
        });
    }
};
