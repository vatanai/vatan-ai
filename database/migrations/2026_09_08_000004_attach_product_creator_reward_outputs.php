<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_creator_reward_events')) {
            return;
        }

        Schema::table('product_creator_reward_events', function (Blueprint $table): void {
            if (Schema::hasTable('generated_images') && ! Schema::hasColumn('product_creator_reward_events', 'generated_image_id')) {
                $table->foreignId('generated_image_id')->nullable()->after('generation_id')->constrained('generated_images')->nullOnDelete();
            }
            if (Schema::hasTable('generated_videos') && ! Schema::hasColumn('product_creator_reward_events', 'generated_video_id')) {
                $table->foreignId('generated_video_id')->nullable()->after('generated_image_id')->constrained('generated_videos')->nullOnDelete();
            }
            if (Schema::hasTable('orders') && ! Schema::hasColumn('product_creator_reward_events', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('generated_video_id')->constrained('orders')->nullOnDelete();
            }
        });

        Schema::table('product_creator_reward_events', function (Blueprint $table): void {
            if (Schema::hasColumn('product_creator_reward_events', 'generated_image_id')) {
                $table->index(['generated_image_id', 'status']);
            }
            if (Schema::hasColumn('product_creator_reward_events', 'generated_video_id')) {
                $table->index(['generated_video_id', 'status']);
            }
            if (Schema::hasColumn('product_creator_reward_events', 'order_id')) {
                $table->index(['order_id', 'created_at']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_creator_reward_events')) {
            return;
        }

        Schema::table('product_creator_reward_events', function (Blueprint $table): void {
            foreach (['generated_image_id', 'generated_video_id', 'order_id'] as $column) {
                if (Schema::hasColumn('product_creator_reward_events', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
        });
    }
};
