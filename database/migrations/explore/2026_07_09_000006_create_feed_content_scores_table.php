<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: امتیاز دستی ادمین روی هر آیتم (Boost) — پایه‌ی رتبه‌بندی هوشمند فاز بعد ──
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_content_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_content_item_id')->constrained('feed_content_items')->cascadeOnDelete();
            $table->foreignId('feed_surface_id')->constrained('feed_surfaces')->cascadeOnDelete();
            $table->unsignedTinyInteger('manual_boost')->default(0); // 0..100، دستی از ادمین
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['feed_content_item_id', 'feed_surface_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_content_scores');
    }
};
