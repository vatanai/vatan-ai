<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** بخش فعلی قبل/بعد را به نمونه‌های تصویری همراه کد وصل می‌کند؛ نیازی به انتقال دادهٔ محصول ندارد. */
    public function up(): void
    {
        $sections = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('type', 'vt_before_after')
            ->where('status', 'published')
            ->where('title_fa', 'از عکس ساده تا عکس فروش')
            ->get(['id', 'settings']);

        foreach ($sections as $section) {
            $settings = is_array($section->settings)
                ? $section->settings
                : (json_decode((string) $section->settings, true) ?: []);

            // تنظیم صریح مدیر را نگه می‌داریم؛ فقط منبع خودکار قبلی به ویترین آماده تبدیل می‌شود.
            if (($settings['source'] ?? 'with_before') !== 'with_before') {
                continue;
            }

            $settings['source'] = 'showcase';
            $settings['limit'] = 3;
            $settings['show_meta'] = false;
            DB::table('home_sections')->where('id', $section->id)->update([
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $sections = DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('type', 'vt_before_after')
            ->where('status', 'published')
            ->where('title_fa', 'از عکس ساده تا عکس فروش')
            ->get(['id', 'settings']);

        foreach ($sections as $section) {
            $settings = is_array($section->settings)
                ? $section->settings
                : (json_decode((string) $section->settings, true) ?: []);

            if (($settings['source'] ?? null) !== 'showcase' || (int) ($settings['limit'] ?? 0) !== 3) {
                continue;
            }

            $settings['source'] = 'with_before';
            $settings['limit'] = 3;
            DB::table('home_sections')->where('id', $section->id)->update([
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
        }
    }
};
