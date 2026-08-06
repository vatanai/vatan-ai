<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'external_model_id')) {
                $table->string('external_model_id')->nullable()->after('openrouter_model_id');
            }
            if (!Schema::hasColumn('ai_models', 'external_version')) {
                $table->string('external_version')->nullable()->after('external_model_id');
            }
            if (!Schema::hasColumn('ai_models', 'input_schema')) {
                $table->json('input_schema')->nullable()->after('default_parameters');
            }
            if (!Schema::hasColumn('ai_models', 'capability_config')) {
                $table->json('capability_config')->nullable()->after('input_schema');
            }
            if (!Schema::hasColumn('ai_models', 'pricing_config')) {
                $table->json('pricing_config')->nullable()->after('capability_config');
            }
            if (!Schema::hasColumn('ai_models', 'supports_webhook')) {
                $table->boolean('supports_webhook')->default(false)->after('pricing_config');
            }
            if (!Schema::hasColumn('ai_models', 'terms_url')) {
                $table->string('terms_url')->nullable()->after('supports_webhook');
            }
            if (!Schema::hasColumn('ai_models', 'data_retention_notes')) {
                $table->text('data_retention_notes')->nullable()->after('terms_url');
            }
            if (!Schema::hasColumn('ai_models', 'last_verified_at')) {
                $table->timestamp('last_verified_at')->nullable()->after('data_retention_notes');
            }
        });

        if (Schema::hasColumn('ai_models', 'external_model_id')) {
            \Illuminate\Support\Facades\DB::table('ai_models')
                ->whereNull('external_model_id')
                ->update(['external_model_id' => \Illuminate\Support\Facades\DB::raw('openrouter_model_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $columns = [
                'external_model_id', 'external_version', 'input_schema', 'capability_config',
                'pricing_config', 'supports_webhook', 'terms_url', 'data_retention_notes',
                'last_verified_at',
            ];
            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('ai_models', $column)));
            if ($existing) {
                $table->dropColumn($existing);
            }
        });
    }
};
