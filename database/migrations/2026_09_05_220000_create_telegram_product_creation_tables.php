<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_product_managers')) {
            Schema::create('telegram_product_managers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('telegram_id')->nullable()->unique();
                $table->string('name', 160);
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->boolean('is_active')->default(true)->index();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('telegram_product_drafts')) {
            Schema::create('telegram_product_drafts', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignId('telegram_product_manager_id')->constrained('telegram_product_managers')->cascadeOnDelete();
                $table->unsignedBigInteger('telegram_id')->index();
                $table->string('chat_id', 100);
                $table->string('state', 40)->index();
                $table->string('pending_edit_field', 60)->nullable();
                $table->text('description')->nullable();
                $table->json('image_paths')->nullable();
                $table->json('image_file_ids')->nullable();
                $table->json('ai_result')->nullable();
                $table->json('input_payload')->nullable();
                $table->json('message_ids')->nullable();
                $table->string('last_message_id', 100)->nullable();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->text('error_message')->nullable();
                $table->timestamp('processing_started_at')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();

                $table->index(['telegram_product_manager_id', 'state']);
                $table->index(['telegram_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('telegram_product_events')) {
            Schema::create('telegram_product_events', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('update_id')->nullable()->unique();
                $table->foreignId('telegram_product_manager_id')->nullable()->constrained('telegram_product_managers')->nullOnDelete();
                $table->uuid('draft_id')->nullable();
                $table->string('event_type', 60);
                $table->json('payload')->nullable();
                $table->json('response')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['telegram_product_manager_id', 'created_at'], 'telegram_product_events_manager_created_idx');
            });
        }

        if (Schema::hasTable('telegram_product_managers') && ! DB::table('telegram_product_managers')->where('name', 'محسن آقاجانی')->exists()) {
            DB::table('telegram_product_managers')->insert([
                'name' => 'محسن آقاجانی',
                'admin_id' => null,
                'is_active' => true,
                'metadata' => json_encode(['note' => 'شناسه تلگرام پس از تأیید مدیر متصل می‌شود'], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_product_events');
        Schema::dropIfExists('telegram_product_drafts');
        Schema::dropIfExists('telegram_product_managers');
    }
};
