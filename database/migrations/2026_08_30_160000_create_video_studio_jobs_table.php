<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_jobs')) return;
        Schema::create('video_studio_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('source_mode', 20)->default('auto');
            $table->text('source_url')->nullable();
            $table->json('selected_images')->nullable();
            $table->string('aspect_ratio', 20)->default('9:16');
            $table->text('hook_text')->nullable();
            $table->text('caption_text')->nullable();
            $table->string('keyword', 80)->nullable();
            $table->text('dm_template')->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->string('n8n_execution_id', 120)->nullable()->index();
            $table->text('video_url')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_jobs');
    }
};
