<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: آیتم‌های سنجاق‌شده در موقعیت ثابت — مستقل از رتبه‌بندی/رندوم ──
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_pinned_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_surface_id')->constrained('feed_surfaces')->cascadeOnDelete();
            $table->foreignId('feed_content_item_id')->constrained('feed_content_items')->cascadeOnDelete();
            $table->unsignedInteger('position'); // موقعیت ثابت در فید (۱-پایه)
            $table->timestamps();

            $table->unique(['feed_surface_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_pinned_items');
    }
};
