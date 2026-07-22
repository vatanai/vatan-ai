<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['percent', 'fixed', 'free']);
            $table->unsignedInteger('value')->default(0);
            $table->unsignedInteger('max_discount_credits')->nullable();
            $table->unsignedInteger('min_order_credits')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);
            $table->enum('scope', ['all', 'products', 'categories'])->default('all');
            $table->json('product_ids')->nullable();
            $table->json('category_ids')->nullable();
            $table->boolean('first_order_only')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'pending', 'confirmed', 'processing', 'completed', 'cancelled', 'review'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'failed', 'partially_refunded', 'refunded'])->default('unpaid');
            $table->enum('processing_status', ['queued', 'processing', 'completed', 'failed', 'expired', 'stopped', 'retrying'])->default('queued');
            $table->unsignedInteger('original_credits')->default(0);
            $table->unsignedInteger('discount_credits')->default(0);
            $table->unsignedInteger('final_credits')->default(0);
            $table->unsignedInteger('refunded_credits')->default(0);
            $table->string('discount_code')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('ai_model')->nullable();
            $table->string('ai_provider')->nullable();
            $table->unsignedInteger('queue_duration_ms')->nullable();
            $table->unsignedInteger('processing_duration_ms')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->text('admin_note')->nullable();
            $table->string('source')->default('direct');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index(['processing_status', 'created_at']);
            $table->index(['payment_status', 'created_at']);
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('discounts');
    }
};
