<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->unique();
            $table->text('api_key')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->string('base_url')->nullable();
            $table->unsignedInteger('timeout')->default(120);
            $table->unsignedTinyInteger('max_retries')->default(2);
            $table->boolean('webhook_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_settings');
    }
};
