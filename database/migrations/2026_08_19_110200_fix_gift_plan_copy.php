<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plan = DB::table('plans')->where('slug', 'vatan-gift')->first();
        if (! $plan) return;

        $configuration = json_decode((string) $plan->home_pricing_config, true) ?: [];
        $configuration['credit'] = '۴۰ اعتبار هدیه برای شروع';
        DB::table('plans')->where('id', $plan->id)->update([
            'home_pricing_config' => json_encode($configuration, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // بازگرداندن متن تبلیغاتی قبلی لازم نیست.
    }
};
