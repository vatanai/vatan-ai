<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_journeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('point')->default(1);
            $table->string('substage', 60)->default('new');
            $table->string('status', 30)->default('active');
            $table->string('source', 80)->nullable();
            $table->dateTime('entered_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('next_action_at')->nullable();
            $table->unsignedTinyInteger('cycle_step')->default(0);
            $table->dateTime('cycle_step_at')->nullable();
            $table->unsignedSmallInteger('readiness_score')->default(0);
            $table->text('last_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['point', 'status']);
            $table->index(['status', 'next_action_at']);
            $table->index('readiness_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_journeys');
    }
};
