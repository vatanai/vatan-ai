<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_requests', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30);
            $table->foreignId('ai_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('external_request_id', 255);
            $table->string('status', 30)->default('queued');
            $table->json('output_urls')->nullable();
            $table->json('raw_response')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->decimal('actual_cost_usd', 12, 6)->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'external_request_id']);
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_requests');
    }
};
