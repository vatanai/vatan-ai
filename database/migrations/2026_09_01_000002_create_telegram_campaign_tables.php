<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_campaigns')) {
            Schema::create('telegram_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('name', 180);
                $table->json('segment_definition')->nullable();
                $table->string('status', 30)->default('draft');
                $table->text('body')->nullable();
                $table->string('media_type', 30)->nullable();
                $table->text('media_file_id')->nullable();
                $table->json('buttons')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->unsignedInteger('recipient_count')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->timestamps();

                $table->index(['status', 'scheduled_at']);
            });
        }

        if (! Schema::hasTable('telegram_campaign_logs')) {
            Schema::create('telegram_campaign_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('telegram_campaigns')->cascadeOnDelete();
                $table->foreignId('telegram_user_id')->constrained('telegram_users')->cascadeOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->string('delivery_status', 30)->default('pending');
                $table->string('provider_message_id', 120)->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['campaign_id', 'telegram_user_id']);
                $table->index(['delivery_status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_campaign_logs');
        Schema::dropIfExists('telegram_campaigns');
    }
};
