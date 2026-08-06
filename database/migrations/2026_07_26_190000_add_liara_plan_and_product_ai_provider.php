<?php

use App\Support\ProviderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ai_models') && !Schema::hasColumn('ai_models', 'liara_plan')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->string('liara_plan', 30)->nullable()->after('provider')->index();
            });
        }

        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'ai_provider')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('ai_provider', 30)->default('openrouter')->after('primary_model')->index();
            });
        }

        if (Schema::hasTable('ai_models') && Schema::hasColumn('ai_models', 'liara_plan')) {
            DB::table('ai_models')
                ->where('provider', 'liara')
                ->where('openrouter_model_id', 'openai/gpt-image-1-mini')
                ->update(['liara_plan' => 'mirzakhani']);

            DB::table('ai_models')
                ->where('provider', 'liara')
                ->whereIn('openrouter_model_id', [
                    'openai/gpt-image-1',
                    'openai/gpt-image-1.5',
                    'openai/gpt-image-2',
                    'google/gemini-2.5-flash-image',
                    'google/gemini-3-pro-image-preview',
                ])
                ->update(['liara_plan' => 'turing']);
        }

        // محصولات موجود در دوره‌ای ساخته شده‌اند که OpenRouter خاموش و Liara
        // سرویس اصلی بوده است؛ مدل‌های شناخته‌شده Liara را دقیق بک‌فیل می‌کنیم.
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'ai_provider')) {
            DB::table('products')
                ->whereIn('primary_model', [
                    'openai/gpt-image-1-mini',
                    'openai/gpt-image-1',
                    'openai/gpt-image-1.5',
                    'openai/gpt-image-2',
                    'google/gemini-2.5-flash-image',
                    'google/gemini-3-pro-image-preview',
                ])
                ->update(['ai_provider' => 'liara']);
        }

        // OpenRouter باید در گام دوم ثبت محصول در دسترس باشد؛ مقدار پایدار قبلی
        // Cache را هم بازنویسی می‌کنیم تا فقط تغییر default کافی نباشد.
        ProviderStatus::setEnabled('openrouter', true);
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'ai_provider')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex(['ai_provider']);
                $table->dropColumn('ai_provider');
            });
        }

        if (Schema::hasTable('ai_models') && Schema::hasColumn('ai_models', 'liara_plan')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->dropIndex(['liara_plan']);
                $table->dropColumn('liara_plan');
            });
        }
    }
};
