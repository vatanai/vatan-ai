<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'cost_per_generation_usd')) {
                $table->decimal('cost_per_generation_usd', 12, 6)->nullable()->after('cost_per_generation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (Schema::hasColumn('ai_models', 'cost_per_generation_usd')) {
                $table->dropColumn('cost_per_generation_usd');
            }
        });
    }
};
