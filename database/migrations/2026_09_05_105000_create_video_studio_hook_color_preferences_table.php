<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_hook_color_preferences')) {
            return;
        }

        Schema::create('video_studio_hook_color_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('target', 20);
            $table->string('color_key', 60);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->unique(['admin_id', 'target', 'color_key'], 'video_studio_hook_color_pref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_hook_color_preferences');
    }
};
