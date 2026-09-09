<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_purchases') || Schema::hasTable('plan_purchase_follow_ups')) {
            return;
        }

        Schema::create('plan_purchase_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_purchase_id')->constrained('plan_purchases')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('task_key', 50);
            $table->string('task_label', 180);
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['plan_purchase_id', 'task_key']);
            $table->index(['completed_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_purchase_follow_ups');
    }
};
