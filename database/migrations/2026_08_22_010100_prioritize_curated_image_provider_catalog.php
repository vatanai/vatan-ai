<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** مدل‌های اصلی هر provider قبل از مدل‌های خام catalog نمایش داده شوند. */
    public function up(): void
    {
        if (! Schema::hasTable('ai_models') || ! Schema::hasColumn('ai_models', 'lab_priority')) {
            return;
        }

        $priorities = [
            'replicate|google/nano-banana-2-lite' => 10,
            'replicate|google/nano-banana' => 20,
            'replicate|bytedance/seedream-5-pro' => 30,
            'replicate|bytedance/seedream-4.5' => 40,
            'replicate|black-forest-labs/flux-kontext-pro' => 50,
            'replicate|google/nano-banana-2' => 60,
            'replicate|google/nano-banana-pro' => 70,
            'openrouter|openai/gpt-image-1-mini' => 110,
            'openrouter|openai/gpt-image-1' => 120,
            'openrouter|openai/gpt-image-2' => 130,
            'openrouter|openai/gpt-5.4-image-2' => 140,
            'fal|fal-ai/flux/schnell' => 210,
            'fal|fal-ai/flux/dev' => 220,
            'fal|fal-ai/flux-2-max' => 230,
        ];

        foreach ($priorities as $key => $priority) {
            [$provider, $modelId] = explode('|', $key, 2);
            DB::table('ai_models')
                ->where('provider', $provider)
                ->where('openrouter_model_id', $modelId)
                ->update(['lab_priority' => $priority, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // اولویت‌ها بخشی از ترتیب انتخابی catalog هستند و rollback آن‌ها
        // نباید مدل‌های ذخیره‌شده در محصولات را جابه‌جا یا حذف کند.
    }
};
