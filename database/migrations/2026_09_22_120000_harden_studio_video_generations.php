<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_videos', function (Blueprint $table): void {
            $table->string('idempotency_key', 80)->nullable()->unique()->after('external_request_id');
            $table->uuid('correlation_id')->nullable()->index()->after('idempotency_key');
            $table->unsignedInteger('credits_reserved')->default(0)->after('credit_reservation');
            $table->unsignedInteger('credits_settled')->default(0)->after('credits_reserved');
            $table->unsignedInteger('credits_refunded')->default(0)->after('credits_settled');
            $table->decimal('actual_cost_usd', 12, 6)->nullable()->after('cost');
            $table->string('error_code', 100)->nullable()->after('error_message');
            $table->boolean('retryable')->default(false)->after('error_code');
            $table->unsignedTinyInteger('retry_count')->default(0)->after('retryable');
            $table->timestamp('submitted_at')->nullable()->after('retry_count');
            $table->timestamp('next_poll_at')->nullable()->index()->after('submitted_at');
            $table->timestamp('cancel_requested_at')->nullable()->after('next_poll_at');
        });

        Schema::table('user_uploads', function (Blueprint $table): void {
            $table->string('status', 30)->default('stored')->index()->after('mime_type');
            $table->text('error_message')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->index()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('user_uploads', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['status', 'error_message', 'expires_at']);
        });

        Schema::table('generated_videos', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['correlation_id']);
            $table->dropIndex(['next_poll_at']);
            $table->dropColumn([
                'idempotency_key', 'correlation_id', 'credits_reserved', 'credits_settled',
                'credits_refunded', 'actual_cost_usd', 'error_code', 'retryable',
                'retry_count', 'submitted_at', 'next_poll_at', 'cancel_requested_at',
            ]);
        });
    }
};
