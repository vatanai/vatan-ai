<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partner_leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('handle', 160)->nullable();
            $table->string('channel', 30)->default('instagram');
            $table->string('profile_url', 2048)->nullable();
            $table->unsignedTinyInteger('stage')->default(0);
            $table->dateTime('stage_changed_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->string('priority', 20)->default('normal');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->dateTime('last_contact_at')->nullable();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->string('last_contact_type', 30)->nullable();
            $table->text('last_contact_note')->nullable();
            $table->unsignedInteger('contact_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->unsignedInteger('positive_reply_count')->default(0);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'stage']);
            $table->index(['status', 'next_follow_up_at']);
            $table->index('channel');
            $table->index('handle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_partner_leads');
    }
};
