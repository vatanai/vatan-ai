<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $priorities = [
            'google/nano-banana-2-lite' => 10,
            'google/nano-banana' => 20,
            'bytedance/seedream-5-pro' => 30,
            'bytedance/seedream-4.5' => 40,
            'black-forest-labs/flux-kontext-pro' => 50,
            'google/nano-banana-2' => 60,
            'google/nano-banana-pro' => 70,
        ];

        foreach ($priorities as $modelId => $priority) {
            DB::table('ai_models')
                ->where('provider', 'replicate')
                ->where('openrouter_model_id', $modelId)
                ->update(['lab_priority' => $priority, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // ترتیب MVP بخشی از کاتالوگ فعال است و rollback خودکار ندارد.
    }
};
