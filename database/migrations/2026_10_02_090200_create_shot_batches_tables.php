<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هر ساخت پک = یک batch؛ هر شات = یک item با سفارش، رزرو اعتبار و بازگشت مستقل.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shot_batches')) {
            Schema::create('shot_batches', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                // pending / running / completed / partial / failed
                $table->string('status', 20)->default('pending')->index();
                $table->string('aspect_ratio', 10)->default('4:5');
                $table->json('source_paths')->nullable();
                $table->json('preflight')->nullable();
                $table->unsignedInteger('shots_total')->default(0);
                $table->unsignedInteger('credits_quoted')->default(0);
                $table->string('source', 20)->default('app');
                $table->timestamp('sources_deleted_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shot_batch_items')) {
            Schema::create('shot_batch_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('shot_batch_id')->index();
                $table->unsignedBigInteger('shot_id')->nullable()->index();
                $table->string('shot_key', 80);
                $table->string('shot_name_fa')->nullable();
                // pending / running / completed / failed
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedInteger('credits')->default(0);
                $table->unsignedInteger('credits_charged')->default(0);
                $table->unsignedInteger('credits_refunded')->default(0);
                $table->unsignedBigInteger('order_id')->nullable()->index();
                $table->unsignedBigInteger('generated_image_id')->nullable()->index();
                $table->string('image_path')->nullable();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->unsignedTinyInteger('qc_retries')->default(0);
                $table->json('qc')->nullable();
                $table->decimal('cost_usd', 10, 5)->nullable();
                $table->string('ai_model')->nullable();
                $table->text('error_message')->nullable();
                $table->text('prompt')->nullable();
                $table->unsignedInteger('sort')->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shot_batch_items');
        Schema::dropIfExists('shot_batches');
    }
};
