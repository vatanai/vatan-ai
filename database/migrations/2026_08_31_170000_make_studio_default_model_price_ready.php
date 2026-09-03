<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('ai_models')) {
            return;
        }

        // مدل پیش‌فرض قدیمی قیمت قابل اتکای پیش‌ساخت نداشت. فقط همان مقدار
        // seeded را جابه‌جا می‌کنیم تا صفحه‌ی عمومی از ابتدا قیمت معتبر نشان دهد؛
        // انتخاب دستی مدیر یا مدل‌های دیگر دست‌نخورده می‌ماند.
        $product = DB::table('products')
            ->where('slug', 'ai-fashion-portrait')
            ->where('primary_model', 'google/gemini-3.1-flash-image')
            ->where('ai_provider', 'openrouter')
            ->first();
        $primary = DB::table('ai_models')
            ->where('provider', 'openrouter')
            ->where('openrouter_model_id', 'black-forest-labs/flux.2-klein-4b')
            ->where('is_active', true)
            ->first();
        $fallback = DB::table('ai_models')
            ->where('provider', 'openrouter')
            ->where('openrouter_model_id', 'sourceful/riverflow-v2.5-fast')
            ->where('is_active', true)
            ->first();

        if (! $product || ! $primary) {
            return;
        }

        DB::table('products')
            ->where('id', $product->id)
            ->update([
                'primary_model' => 'black-forest-labs/flux.2-klein-4b',
                'ai_provider' => 'openrouter',
                'fallback_models' => json_encode($fallback ? ['sourceful/riverflow-v2.5-fast'] : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'fallback_model_providers' => json_encode($fallback ? ['openrouter'] : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // مدل قبلی قیمت پیش‌ساخت قابل اتکا نداشت و عمداً بازگردانده نمی‌شود.
    }
};
