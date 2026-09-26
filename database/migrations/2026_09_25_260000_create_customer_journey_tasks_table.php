<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_journey_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_journey_id')->constrained('customer_journeys')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('point');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->string('task_type', 30)->default('manual');
            $table->string('status', 30)->default('pending');
            $table->string('title', 180);
            $table->text('body')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index(['point', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_journey_tasks');
    }
};
