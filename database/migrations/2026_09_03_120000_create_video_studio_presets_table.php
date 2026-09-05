<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_presets')) {
            return;
        }

        Schema::create('video_studio_presets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('name', 120);
            $table->json('settings');
            $table->timestamps();
            $table->unique(['admin_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_presets');
    }
};
