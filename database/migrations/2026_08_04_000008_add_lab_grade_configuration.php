<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('lab_grade_config')->nullable()->after('fallback_model_providers');
        });

        Schema::table('lab_experiments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_experiment_id')->nullable()->after('product_id')->index();
            $table->decimal('overall_score', 5, 2)->nullable()->after('actual_cost_irr');
            $table->timestamp('applied_at')->nullable()->after('completed_at');
            $table->unsignedBigInteger('applied_by')->nullable()->after('applied_at')->index();
        });

        Schema::table('lab_runs', function (Blueprint $table) {
            $table->string('grade_key', 32)->nullable()->after('lab_experiment_id')->index();
            $table->string('grade_label', 80)->nullable()->after('grade_key');
            $table->string('role', 24)->default('primary')->after('status');
            $table->unsignedTinyInteger('attempt_order')->default(1)->after('role');
            $table->decimal('final_score', 5, 2)->nullable()->after('actual_cost_usd');
            $table->unsignedTinyInteger('rank')->nullable()->after('final_score');
            $table->boolean('is_selected')->default(false)->after('rank');
            $table->unsignedBigInteger('fallback_of_run_id')->nullable()->after('is_selected')->index();
        });
    }

    public function down(): void
    {
        Schema::table('lab_runs', function (Blueprint $table) {
            $table->dropColumn(['grade_key', 'grade_label', 'role', 'attempt_order', 'final_score', 'rank', 'is_selected', 'fallback_of_run_id']);
        });

        Schema::table('lab_experiments', function (Blueprint $table) {
            $table->dropColumn(['parent_experiment_id', 'overall_score', 'applied_at', 'applied_by']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('lab_grade_config');
        });
    }
};
