<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_experiments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('status', 24)->default('queued')->index();
            $table->longText('prompt_snapshot');
            $table->text('negative_prompt')->nullable();
            $table->json('settings')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->default(0);
            $table->decimal('actual_cost_usd', 12, 6)->default(0);
            $table->decimal('estimated_cost_irr', 18, 2)->default(0);
            $table->decimal('actual_cost_irr', 18, 2)->default(0);
            $table->decimal('exchange_rate_irr', 18, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('lab_experiment_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            $table->string('image_path');
            $table->string('role', 32)->default('reference');
            $table->string('source', 32)->default('product');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['lab_experiment_id', 'image_path']);
        });

        Schema::create('lab_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('model_id');
            $table->string('provider', 40)->nullable();
            $table->string('alias')->nullable();
            $table->string('status', 24)->default('queued')->index();
            $table->longText('prompt_snapshot');
            $table->json('model_snapshot')->nullable();
            $table->json('parameters')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->unsignedTinyInteger('max_retries')->default(2);
            $table->decimal('estimated_cost_usd', 12, 6)->default(0);
            $table->decimal('actual_cost_usd', 12, 6)->default(0);
            $table->decimal('exchange_rate_irr', 18, 2)->default(0);
            $table->string('request_id')->nullable()->index();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['lab_experiment_id', 'status']);
            $table->index(['model_id', 'provider']);
        });

        Schema::create('lab_run_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_run_id')->constrained('lab_runs')->cascadeOnDelete();
            $table->string('output_path')->nullable();
            $table->text('remote_url')->nullable();
            $table->string('status', 24)->default('completed');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->decimal('manual_score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index(['lab_run_id', 'is_winner']);
        });

        Schema::create('lab_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_run_output_id')->constrained('lab_run_outputs')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('evaluator_type', 24)->default('admin');
            $table->string('criterion', 64);
            $table->unsignedTinyInteger('score');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['lab_run_output_id', 'evaluator_type', 'criterion', 'admin_id'], 'lab_score_identity');
        });

        Schema::create('lab_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('action', 64);
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['lab_experiment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_audit_logs');
        Schema::dropIfExists('lab_scores');
        Schema::dropIfExists('lab_run_outputs');
        Schema::dropIfExists('lab_runs');
        Schema::dropIfExists('lab_experiment_images');
        Schema::dropIfExists('lab_experiments');
    }
};
