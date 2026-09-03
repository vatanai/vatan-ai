<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('generated_videos')) return;

        Schema::create('generated_videos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_provider_request_id')->nullable()->constrained('ai_provider_requests')->nullOnDelete();
            $table->string('external_request_id')->nullable()->index();
            $table->string('status', 30)->default('queued');
            $table->string('video_path')->nullable();
            $table->text('video_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->text('user_prompt')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('credit_reservation')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->decimal('cost', 12, 6)->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('credits_settled_at')->nullable();
            $table->timestamp('credits_restored_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_videos');
    }
};
