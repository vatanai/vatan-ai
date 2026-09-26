<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_partner_activities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sales_partner_lead_id')->constrained('sales_partner_leads')->cascadeOnDelete();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('contact_type', 30);
            $table->string('result', 30);
            $table->text('note')->nullable();
            $table->dateTime('contacted_at');
            $table->dateTime('next_follow_up_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'contacted_at'], 'spa_admin_contacted_idx');
            $table->index(['sales_partner_lead_id', 'contacted_at'], 'spa_lead_contacted_idx');
            $table->index('result', 'spa_result_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_partner_activities');
    }
};
