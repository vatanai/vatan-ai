<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_referral_messages')) {
            return;
        }

        Schema::create('telegram_referral_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('telegram_user_id')->constrained('telegram_users')->cascadeOnDelete();
            $table->foreignId('referral_link_id')->nullable()->constrained('referral_links')->nullOnDelete();
            $table->string('link_key', 64);
            $table->string('chat_id', 100);
            $table->unsignedBigInteger('message_id');
            $table->char('content_hash', 64);
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['telegram_user_id', 'link_key']);
            $table->index(['chat_id', 'is_active']);
            $table->index(['referral_link_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_referral_messages');
    }
};
