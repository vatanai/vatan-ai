<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('ai_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');          // نام نمایشی (مثلا: GPT-4o Omni)
        $table->string('model_id');      // شناسه دقیق OpenRouter (مثلا: openai/gpt-4o)
        $table->string('provider');      // ارائه‌دهنده (مثلا: OpenAI, Anthropic)
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
