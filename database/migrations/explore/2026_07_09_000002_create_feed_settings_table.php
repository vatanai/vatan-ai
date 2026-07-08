<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ── موتور فید: تنظیمات هر بستر (نسخه‌دار) — سبک چیدمان، نسبت محتوا، سطح تصادفی بودن ──
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_surface_id')->constrained('feed_surfaces')->cascadeOnDelete();

            $table->string('layout_style')->default('classic'); // classic / dense / magazine / custom
            $table->json('tile_weights'); // {"size-1x1":64,"size-wide":14,"size-tall":14,"size-big":8}
            $table->unsignedTinyInteger('randomness_level')->default(35); // 0..100
            $table->unsignedTinyInteger('campaign_ratio')->default(5); // درصد اسلات‌های اختصاص‌یافته به کمپین

            $table->boolean('is_active_version')->default(true);
            $table->timestamps();

            $table->index(['feed_surface_id', 'is_active_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_settings');
    }
};
