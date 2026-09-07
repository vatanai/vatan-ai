<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * هر سه provider تصویر برای انتخاب و اجرای واقعی در MVP روشن باشند.
     * سلامت اتصال Fal.ai جداگانه در خود provider و گزارش خطا کنترل می‌شود.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_provider_settings')) {
            return;
        }

        foreach (['openrouter', 'fal', 'replicate'] as $provider) {
            $row = DB::table('ai_provider_settings')->where('provider', $provider)->first();
            $settings = json_decode((string) ($row->settings ?? '{}'), true);
            $settings = is_array($settings) ? $settings : [];
            $settings['admin_enabled'] = true;

            $values = [
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ];
            if (! $row) {
                $values += [
                    'timeout' => 180,
                    'max_retries' => 2,
                    'webhook_enabled' => true,
                    'created_at' => now(),
                ];
            }

            DB::table('ai_provider_settings')->updateOrInsert(['provider' => $provider], $values);
            Cache::forget('provider.' . $provider . '.enabled');
        }
    }

    public function down(): void
    {
        // خاموش‌کردن providerها در rollback می‌تواند اجرای محصولات موجود را
        // متوقف کند؛ وضعیت اجرایی از پنل مدیریت قابل کنترل است.
    }
};
