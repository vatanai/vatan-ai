<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** مدل گرید ۱ باید برای برآورد سود، قیمت ثبت‌شده داشته باشد. */
    public function up(): void
    {
        DB::table('model_tier_defaults')->where('tier_key', 'business')->update([
            'primary_model_id' => 'openai/gpt-image-2',
            'primary_provider' => 'replicate',
            'fallback_model_id' => 'fal-ai/nano-banana-pro/edit',
            'fallback_provider' => 'fal',
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('model_tier_defaults')->where('tier_key', 'business')->update([
            'primary_model_id' => 'openai/gpt-image-2',
            'primary_provider' => 'openrouter',
            'fallback_model_id' => 'google/nano-banana-pro',
            'fallback_provider' => 'replicate',
            'updated_at' => now(),
        ]);
    }
};
