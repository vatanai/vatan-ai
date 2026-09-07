<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * قیمت خط‌خورده، ارزش مرجع قابل بازبینی است؛ قیمت فعلی صرفاً آزمایش ورود MVP است.
     * مقدار اعتبارها دست‌نخورده می‌ماند تا با یک تغییر بازاریابی، موجودی خریدهای قبلی عوض نشود.
     */
    public function up(): void
    {
        foreach ([
            'start' => ['name' => 'اقتصادی', 'price' => 10000, 'compare' => 75000, 'tier' => 'economy'],
            'pro' => ['name' => 'حرفه‌ای', 'price' => 15000, 'compare' => 185000, 'tier' => 'pro'],
            'premium' => ['name' => 'پیشرفته', 'price' => 20000, 'compare' => 320000, 'tier' => 'business'],
            'business' => ['name' => 'بیزینس', 'price' => 25000, 'compare' => 550000, 'tier' => 'business'],
        ] as $slug => $data) {
            DB::table('plans')->where('slug', $slug)->update([
                'name' => $data['name'],
                'price' => $data['price'],
                'compare_at_price' => $data['compare'],
                'model_tier_key' => $data['tier'],
                'show_model_tier' => true,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plans')->whereIn('slug', ['start', 'pro', 'premium', 'business'])->update([
            'compare_at_price' => null,
            'updated_at' => now(),
        ]);
    }
};
