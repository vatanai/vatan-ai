<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_journey_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_journey_id')->constrained('customer_journeys')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event', 80);
            $table->unsignedTinyInteger('point_before')->nullable();
            $table->unsignedTinyInteger('point_after')->nullable();
            $table->string('substage_before', 60)->nullable();
            $table->string('substage_after', 60)->nullable();
            $table->integer('credit_before')->nullable();
            $table->integer('credit_after')->nullable();
            $table->string('actor_type', 30)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['event', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_journey_events');
    }
};
