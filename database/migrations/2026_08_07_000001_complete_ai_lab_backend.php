<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_experiments', function (Blueprint $table) {
            $table->boolean('lab_enabled')->default(true)->after('status');
            $table->string('draft_token', 64)->nullable()->unique()->after('uuid');
            $table->string('input_image_path')->nullable()->after('negative_prompt');
            $table->string('input_image_original_name')->nullable()->after('input_image_path');
            $table->unsignedInteger('input_image_width')->nullable()->after('input_image_original_name');
            $table->unsignedInteger('input_image_height')->nullable()->after('input_image_width');
            $table->string('input_image_ratio', 20)->nullable()->after('input_image_height');
            $table->unsignedBigInteger('input_image_size')->nullable()->after('input_image_ratio');
            $table->string('input_image_format', 40)->nullable()->after('input_image_size');
            $table->string('input_image_color', 40)->nullable()->after('input_image_format');
            $table->unsignedBigInteger('evaluator_model_id')->nullable()->after('input_image_color')->index();
            $table->string('evaluator_provider', 40)->nullable()->after('evaluator_model_id');
            $table->decimal('exchange_rate_usd', 18, 2)->nullable()->after('exchange_rate_irr');
            $table->decimal('exchange_rate_eur', 18, 2)->nullable()->after('exchange_rate_usd');
            $table->decimal('exchange_rate_usdt', 18, 2)->nullable()->after('exchange_rate_eur');
            $table->decimal('estimated_cost_toman', 18, 2)->default(0)->after('estimated_cost_irr');
            $table->decimal('actual_cost_toman', 18, 2)->default(0)->after('actual_cost_irr');
            $table->decimal('total_cost_usd', 12, 6)->default(0)->after('actual_cost_toman');
            $table->decimal('total_cost_toman', 18, 2)->default(0)->after('total_cost_usd');
            $table->decimal('lab_cost_usd', 12, 6)->default(0)->after('total_cost_toman');
            $table->decimal('lab_cost_toman', 18, 2)->default(0)->after('lab_cost_usd');
            $table->unsignedSmallInteger('models_count')->default(0)->after('total_cost_toman');
            $table->string('report_status', 24)->default('draft')->after('models_count')->index();
            $table->string('report_code', 80)->nullable()->unique()->after('report_status');
            $table->timestamp('tested_at')->nullable()->after('started_at');
            $table->timestamp('evaluated_at')->nullable()->after('tested_at');
            $table->string('product_name_snapshot')->nullable()->after('report_code');
            $table->string('product_code_snapshot', 80)->nullable()->after('product_name_snapshot');
        });

        Schema::table('lab_runs', function (Blueprint $table) {
            $table->string('model_name_snapshot')->nullable()->after('alias');
            $table->string('provider_name_snapshot')->nullable()->after('model_name_snapshot');
            $table->string('quality', 20)->nullable()->after('parameters');
            $table->string('size', 20)->nullable()->after('quality');
            $table->boolean('preserve_face')->default(true)->after('size');
            $table->decimal('estimated_cost_toman', 18, 2)->default(0)->after('estimated_cost_usd');
            $table->decimal('actual_cost_toman', 18, 2)->default(0)->after('actual_cost_usd');
            $table->unsignedInteger('build_seconds')->nullable()->after('duration_ms');
            $table->unsignedBigInteger('tokens_used')->nullable()->after('build_seconds');
        });

        Schema::table('lab_run_outputs', function (Blueprint $table) {
            $table->string('ratio', 20)->nullable()->after('height');
            $table->string('color_profile', 40)->nullable()->after('mime_type');
        });

        Schema::create('lab_manager_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_run_output_id')->unique()->constrained('lab_run_outputs')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->string('similarity_score', 24)->nullable();
            $table->string('detail_quality', 24)->nullable();
            $table->unsignedSmallInteger('usage_priority')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('rated_at')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'rated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_manager_scores');

        Schema::table('lab_run_outputs', function (Blueprint $table) {
            $table->dropColumn(['ratio', 'color_profile']);
        });

        Schema::table('lab_runs', function (Blueprint $table) {
            $table->dropColumn([
                'model_name_snapshot', 'provider_name_snapshot', 'quality', 'size', 'preserve_face',
                'estimated_cost_toman', 'actual_cost_toman', 'build_seconds', 'tokens_used',
            ]);
        });

        Schema::table('lab_experiments', function (Blueprint $table) {
            $table->dropColumn([
                'lab_enabled', 'draft_token', 'input_image_path', 'input_image_original_name',
                'input_image_width', 'input_image_height', 'input_image_ratio', 'input_image_size',
                'input_image_format', 'input_image_color', 'evaluator_model_id', 'evaluator_provider',
                'exchange_rate_usd', 'exchange_rate_eur', 'exchange_rate_usdt', 'estimated_cost_toman',
                'actual_cost_toman', 'total_cost_usd', 'total_cost_toman', 'models_count', 'report_status',
                'report_code', 'tested_at', 'evaluated_at', 'product_name_snapshot', 'product_code_snapshot',
            ]);
        });
    }
};
