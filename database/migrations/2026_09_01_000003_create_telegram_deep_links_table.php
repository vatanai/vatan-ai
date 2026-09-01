<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_deep_links')) return;

        Schema::create('telegram_deep_links', function (Blueprint $table): void {
            $table->id();
            $table->string('token', 24)->unique();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('source', 120)->default('channel');
            $table->string('source_channel', 120)->nullable();
            $table->string('source_campaign', 160)->nullable();
            $table->string('message_id', 100)->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'source']);
            $table->index(['is_active', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_deep_links');
    }
};
