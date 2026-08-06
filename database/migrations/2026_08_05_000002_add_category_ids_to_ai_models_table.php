<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models') && ! Schema::hasColumn('ai_models', 'recommended_category_ids')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->json('recommended_category_ids')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_models') && Schema::hasColumn('ai_models', 'recommended_category_ids')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->dropColumn('recommended_category_ids');
            });
        }
    }
};
