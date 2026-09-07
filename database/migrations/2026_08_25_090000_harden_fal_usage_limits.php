<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_provider_settings')) {
            return;
        }

        $row = DB::table('ai_provider_settings')->where('provider', 'fal')->first();
        $settings = json_decode((string) ($row->settings ?? '{}'), true);
        $settings = is_array($settings) ? $settings : [];
        $current = is_array($settings['usage_limits'] ?? null) ? $settings['usage_limits'] : [];

        $positiveOr = static function (mixed $value, int|float $fallback): int|float {
            return is_numeric($value) && (float) $value > 0 ? $value : $fallback;
        };

        $settings['usage_limits'] = array_replace($current, [
            'enabled' => true,
            'window_minutes' => max(1, (int) ($current['window_minutes'] ?? 60)),
            // تنظیم قبلی اگر سخت‌گیرانه‌تر باشد، هرگز شل‌تر نمی‌شود.
            'max_requests' => min(30, (int) $positiveOr($current['max_requests'] ?? null, 30)),
            'max_cost_usd' => min(2.0, (float) $positiveOr($current['max_cost_usd'] ?? null, 2.0)),
            'max_concurrent' => min(2, (int) $positiveOr($current['max_concurrent'] ?? null, 2)),
            'max_outputs' => 1,
            'max_failed_requests' => min(3, (int) $positiveOr($current['max_failed_requests'] ?? null, 3)),
        ]);

        DB::table('ai_provider_settings')->updateOrInsert(
            ['provider' => 'fal'],
            [
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
                'created_at' => $row->created_at ?? now(),
            ]
        );
    }

    public function down(): void
    {
        // محدودیت محافظتی به‌صورت خودکار برداشته نمی‌شود تا rollback کد
        // باعث بازشدن ناخواسته سقف هزینه سرویس پولی نشود.
    }
};
