<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                if (! Schema::hasColumn('products', 'creator_reward_owner_id')) {
                    $table->foreignId('creator_reward_owner_id')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('products', 'creator_reward_enabled')) {
                    $table->boolean('creator_reward_enabled')->default(false);
                }
                if (! Schema::hasColumn('products', 'creator_reward_settings')) {
                    $table->json('creator_reward_settings')->nullable();
                }
            });
        }

        if (! Schema::hasTable('product_creator_reward_events')) {
            Schema::create('product_creator_reward_events', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('consumer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('generation_id')->nullable();
                $table->string('event_key', 191)->unique();
                $table->string('media_type', 20)->default('photo');
                $table->string('credit_source', 20)->default('free');
                $table->unsignedInteger('reward_credits')->default(0);
                $table->string('status', 20)->default('pending');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['owner_user_id', 'created_at']);
                $table->index(['product_id', 'media_type']);
                $table->index(['consumer_user_id', 'created_at']);
                $table->index(['generation_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_creator_reward_events');

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                if (Schema::hasColumn('products', 'creator_reward_owner_id')) {
                    $table->dropForeign(['creator_reward_owner_id']);
                    $table->dropColumn('creator_reward_owner_id');
                }
                if (Schema::hasColumn('products', 'creator_reward_enabled')) {
                    $table->dropColumn('creator_reward_enabled');
                }
                if (Schema::hasColumn('products', 'creator_reward_settings')) {
                    $table->dropColumn('creator_reward_settings');
                }
            });
        }
    }
};
