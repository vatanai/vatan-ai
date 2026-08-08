<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return;
        }

        $setting = DB::table('ai_provider_settings')->where('provider', 'replicate')->first();

        if (! $setting) {
            return;
        }

        $settings = is_string($setting->settings)
            ? json_decode($setting->settings, true)
            : (array) $setting->settings;
        $settings = is_array($settings) ? $settings : [];
        $limits = (array) ($settings['usage_limits'] ?? []);

        // فقط پیکربندی بسیار محدودِ قدیمی را اصلاح می‌کنیم؛ سقف‌هایی که مدیر آگاهانه
        // بزرگ‌تر تنظیم کرده است، بدون تغییر باقی می‌ماند.
        $isLegacyRestrictiveLimit = (int) ($limits['max_requests'] ?? 0) <= 1
            && (float) ($limits['max_cost_usd'] ?? 0) <= 0.1
            && (int) ($limits['max_concurrent'] ?? 0) <= 1;

        if (! $isLegacyRestrictiveLimit) {
            return;
        }

        $settings['usage_limits'] = array_replace($limits, [
            'enabled' => true,
            'window_minutes' => max(1, (int) ($limits['window_minutes'] ?? 60)),
            'max_requests' => 50,
            'max_cost_usd' => 5.0,
            'max_concurrent' => 4,
            'max_outputs' => max(1, (int) ($limits['max_outputs'] ?? 1)),
        ]);

        DB::table('ai_provider_settings')->where('id', $setting->id)->update([
            'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // بازگرداندن سقف محدود قدیمی می‌تواند دوباره مسیر ساخت تصویر را متوقف کند.
    }
};
