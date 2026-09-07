<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('model_quality_presets')) return;

        if (! Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation')) {
            Schema::table('model_quality_presets', function (Blueprint $table): void {
                $table->boolean('is_default_for_product_creation')->default(false)->after('name');
            });
        }

        if (! DB::table('model_quality_presets')->where('is_default_for_product_creation', true)->exists()) {
            $first = DB::table('model_quality_presets')->orderBy('id')->value('id');
            if ($first) {
                DB::table('model_quality_presets')->where('id', $first)->update([
                    'is_default_for_product_creation' => true,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('model_quality_presets') && Schema::hasColumn('model_quality_presets', 'is_default_for_product_creation')) {
            Schema::table('model_quality_presets', function (Blueprint $table): void {
                $table->dropColumn('is_default_for_product_creation');
            });
        }
    }
};
