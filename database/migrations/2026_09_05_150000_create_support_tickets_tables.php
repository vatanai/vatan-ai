<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table): void {
                $table->id();
                $table->string('ticket_number', 32)->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('channel', 32)->default('site')->index();
                $table->string('external_thread_id', 190)->nullable()->index();
                $table->string('customer_handle', 190)->nullable();
                $table->json('metadata')->nullable();
                $table->string('category', 32)->default('other')->index();
                $table->string('priority', 20)->default('normal')->index();
                $table->string('status', 20)->default('open')->index();
                $table->string('subject', 180);
                $table->text('last_message_preview')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('support_ticket_messages')) {
            Schema::create('support_ticket_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
                $table->string('sender_type', 20)->index();
                $table->unsignedBigInteger('sender_id')->nullable()->index();
                $table->string('external_message_id', 190)->nullable()->index();
                $table->text('body');
                $table->json('attachments')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['support_ticket_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
