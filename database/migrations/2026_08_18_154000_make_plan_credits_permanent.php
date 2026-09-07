<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->orderBy('id')->get()->each(function ($plan): void {
            $features = json_decode($plan->features ?: '[]', true) ?: [];
            $features = collect($features)
                ->reject(fn ($feature) => str_contains((string) ($feature['title'] ?? ''), 'انتقال اعتبار'))
                ->map(function ($feature) use ($plan) {
                    $title = (string) ($feature['title'] ?? '');
                    if (str_contains($title, 'اعتبار ماهانه') || str_contains($title, 'اعتبار هدیه')) {
                        $feature['title'] = number_format((int) $plan->tokens) . ' اعتبار دائمی';
                    }
                    if (str_contains((string) ($feature['value'] ?? ''), '۳۰ روز')) {
                        $feature['value'] = '';
                    }
                    return $feature;
                })->values()->all();

            $payload = ['features' => json_encode($features, JSON_UNESCAPED_UNICODE), 'updated_at' => now()];
            if (in_array($plan->slug, ['start', 'pro', 'premium', 'business'], true)) {
                $payload['billing_type'] = 'one_time';
            }
            DB::table('plans')->where('id', $plan->id)->update($payload);
        });
    }

    public function down(): void
    {
        // اطلاعات قبلی پلن‌ها قابل بازسازی قطعی نیست؛ بازگشت فقط نوع فروش را برمی‌گرداند.
        DB::table('plans')->whereIn('slug', ['start', 'pro', 'premium', 'business'])->update([
            'billing_type' => 'monthly',
            'updated_at' => now(),
        ]);
    }
};
