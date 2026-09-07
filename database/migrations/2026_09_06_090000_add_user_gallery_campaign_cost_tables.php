<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_gallery_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_gallery_item_id')->nullable()->constrained('user_gallery_items')->nullOnDelete();
            $table->string('code', 120)->unique();
            $table->string('campaign_type', 40)->index();
            $table->string('status', 30)->default('prepared')->index();
            $table->string('title', 180);
            $table->text('body')->nullable();
            $table->json('consent_snapshot')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('user_gallery_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_gallery_campaign_id')->nullable()->constrained('user_gallery_campaigns')->nullOnDelete();
            $table->foreignId('user_gallery_suggestion_id')->nullable()->constrained('user_gallery_suggestions')->nullOnDelete();
            $table->string('channel', 30)->default('in_app')->index();
            $table->string('status', 30)->default('prepared')->index();
            $table->boolean('consent_checked')->default(false);
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel', 'status']);
        });

        Schema::create('user_gallery_cost_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_gallery_item_id')->nullable()->constrained('user_gallery_items')->nullOnDelete();
            $table->foreignId('user_gallery_recreation_id')->nullable()->constrained('user_gallery_recreations')->nullOnDelete();
            $table->foreignId('user_gallery_notification_id')->nullable()->constrained('user_gallery_notifications')->nullOnDelete();
            $table->string('cost_type', 30)->index();
            $table->string('status', 30)->default('estimated')->index();
            $table->decimal('units', 18, 6)->default(0);
            $table->string('unit', 40)->nullable();
            $table->unsignedBigInteger('unit_cost_toman')->default(0);
            $table->unsignedBigInteger('cost_toman')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('incurred_at')->index();
            $table->timestamps();

            $table->index(['cost_type', 'incurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gallery_cost_events');
        Schema::dropIfExists('user_gallery_notifications');
        Schema::dropIfExists('user_gallery_campaigns');
    }
};
