<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ستون‌های واقعی مورد نیاز فرم و مدل AIModel (app/Models/AiModel.php) که
     * در migration های قبلی هرگز ساخته نشده بودند و باعث خطای
     * «Column not found: openrouter_model_id» هنگام ثبت مدل جدید می‌شدند.
     */
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_models', 'openrouter_model_id')) {
                $table->string('openrouter_model_id')->nullable()->after('name');
            }
            if (!Schema::hasColumn('ai_models', 'provider_name')) {
                $table->string('provider_name')->nullable()->after('openrouter_model_id');
            }
            if (!Schema::hasColumn('ai_models', 'output_modality')) {
                $table->string('output_modality')->default('image')->after('provider_name');
            }
            if (!Schema::hasColumn('ai_models', 'supports_image_input')) {
                $table->boolean('supports_image_input')->default(false)->after('output_modality');
            }
            if (!Schema::hasColumn('ai_models', 'cost_per_generation')) {
                $table->unsignedInteger('cost_per_generation')->default(0)->after('supports_image_input');
            }
            if (!Schema::hasColumn('ai_models', 'default_width')) {
                $table->unsignedInteger('default_width')->default(1024)->after('cost_per_generation');
            }
            if (!Schema::hasColumn('ai_models', 'default_height')) {
                $table->unsignedInteger('default_height')->default(1024)->after('default_width');
            }
            if (!Schema::hasColumn('ai_models', 'default_parameters')) {
                $table->text('default_parameters')->nullable()->after('default_height');
            }
            if (!Schema::hasColumn('ai_models', 'description')) {
                $table->text('description')->nullable()->after('default_parameters');
            }
        });

        // انتقال داده‌های قدیمی (در صورت وجود ردیف از قبل) از ستون‌های منسوخ‌شده
        // model_id/provider به ستون‌های جدید openrouter_model_id/provider_name
        if (Schema::hasColumn('ai_models', 'model_id')) {
            DB::statement('UPDATE ai_models SET openrouter_model_id = model_id WHERE openrouter_model_id IS NULL');
        }
        if (Schema::hasColumn('ai_models', 'provider')) {
            DB::statement('UPDATE ai_models SET provider_name = provider WHERE provider_name IS NULL');
        }
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $columns = [
                'openrouter_model_id',
                'provider_name',
                'output_modality',
                'supports_image_input',
                'cost_per_generation',
                'default_width',
                'default_height',
                'default_parameters',
                'description',
            ];
            $existing = array_filter($columns, fn ($c) => Schema::hasColumn('ai_models', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });
    }
};
