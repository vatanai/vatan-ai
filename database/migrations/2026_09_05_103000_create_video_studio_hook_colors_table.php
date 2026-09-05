<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_hook_colors')) {
            return;
        }

        Schema::create('video_studio_hook_colors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('target', 20);
            $table->string('name', 80);
            $table->string('color_value', 16);
            $table->timestamps();
            $table->unique(['admin_id', 'target', 'color_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_hook_colors');
    }
};
