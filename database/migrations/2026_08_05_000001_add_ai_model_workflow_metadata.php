<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_models')) return;

        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'task_type')) $table->string('task_type', 40)->nullable()->after('output_modality');
            if (!Schema::hasColumn('ai_models', 'supports_face_identity')) $table->boolean('supports_face_identity')->default(false)->after('supports_image_input');
            if (!Schema::hasColumn('ai_models', 'supports_multiple_faces')) $table->boolean('supports_multiple_faces')->default(false)->after('supports_face_identity');
            if (!Schema::hasColumn('ai_models', 'supports_audio')) $table->boolean('supports_audio')->default(false)->after('supports_multiple_faces');
            if (!Schema::hasColumn('ai_models', 'supports_video_input')) $table->boolean('supports_video_input')->default(false)->after('supports_audio');
            if (!Schema::hasColumn('ai_models', 'max_resolution')) $table->string('max_resolution', 30)->nullable()->after('default_height');
            if (!Schema::hasColumn('ai_models', 'max_duration')) $table->unsignedInteger('max_duration')->nullable()->after('max_resolution');
            if (!Schema::hasColumn('ai_models', 'pricing_type')) $table->string('pricing_type', 30)->nullable()->after('pricing_config');
            if (!Schema::hasColumn('ai_models', 'commercial_use')) $table->boolean('commercial_use')->nullable()->after('pricing_type');
        });

        DB::table('ai_models')->whereNull('task_type')->where('output_modality', 'image')->update(['task_type' => 'text_to_image']);
        DB::table('ai_models')->whereNull('task_type')->where('output_modality', 'video')->update(['task_type' => 'text_to_video']);

        if (config('services.ai.catalog_sync_on_migrate', true)) {
            try {
                app(\App\Services\AiCatalogSyncService::class)->sync('all');
            } catch (\Throwable $e) {
                Log::warning('AI workflow metadata sync during migration was skipped', ['message' => $e->getMessage()]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_models')) return;
        Schema::table('ai_models', function (Blueprint $table) {
            $columns = ['task_type', 'supports_face_identity', 'supports_multiple_faces', 'supports_audio', 'supports_video_input', 'max_resolution', 'max_duration', 'pricing_type', 'commercial_use'];
            $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('ai_models', $column)));
            if ($existing) $table->dropColumn($existing);
        });
    }
};
