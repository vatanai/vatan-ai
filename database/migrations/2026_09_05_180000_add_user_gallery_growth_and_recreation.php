<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_gallery_configs', function (Blueprint $table): void {
            $table->boolean('suggestions_enabled')->default(true)->after('enabled');
            $table->unsignedSmallInteger('free_recreations_per_month')->default(1)->after('max_storage_mb');
        });

        Schema::create('user_gallery_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('suggestions_enabled')->default(false);
            $table->boolean('marketing_enabled')->default(false);
            $table->boolean('occasion_enabled')->default(false);
            $table->boolean('reminders_enabled')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_gallery_suggestions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_gallery_item_id')->nullable()->constrained('user_gallery_items')->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('suggestion_type', 40)->index();
            $table->string('status', 30)->default('suggested')->index();
            $table->string('title', 180);
            $table->text('body')->nullable();
            $table->json('preview_payload')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('user_gallery_recreations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_gallery_item_id')->nullable()->constrained('user_gallery_items')->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('pricing_mode', 30)->default('credits')->index();
            $table->unsignedInteger('list_credit_cost')->default(0);
            $table->unsignedInteger('charged_credit_cost')->default(0);
            $table->string('status', 30)->default('queued')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'pricing_mode', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gallery_recreations');
        Schema::dropIfExists('user_gallery_suggestions');
        Schema::dropIfExists('user_gallery_preferences');
        Schema::table('user_gallery_configs', function (Blueprint $table): void {
            $table->dropColumn(['suggestions_enabled', 'free_recreations_per_month']);
        });
    }
};
