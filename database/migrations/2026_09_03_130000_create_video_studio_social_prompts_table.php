<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('video_studio_social_prompts')) {
            return;
        }

        Schema::create('video_studio_social_prompts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('platform', 40);
            $table->longText('prompt');
            $table->timestamps();
            $table->unique(['admin_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_studio_social_prompts');
    }
};
