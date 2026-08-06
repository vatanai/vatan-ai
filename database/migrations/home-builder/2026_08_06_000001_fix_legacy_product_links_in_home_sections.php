<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** تبدیل لینک قدیمی /products به مسیر واقعی کاتالوگ در سکشن‌های صفحه هوم. */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->orderBy('id')
            ->get(['id', 'settings'])
            ->each(function (object $section): void {
                $settings = json_decode((string) $section->settings, true);
                if (! is_array($settings)) {
                    return;
                }

                $changed = false;
                foreach (['view_all_link', 'intro_cta_link', 'cta_link', 'link'] as $key) {
                    if (! isset($settings[$key]) || ! is_string($settings[$key])) {
                        continue;
                    }

                    $fixed = preg_replace('#^/products(?=\?|$)#', '/app/products', $settings[$key]);
                    if ($fixed !== $settings[$key]) {
                        $settings[$key] = $fixed;
                        $changed = true;
                    }
                }

                if ($changed) {
                    DB::table('home_sections')->where('id', $section->id)->update([
                        'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // بازگرداندن لینک خراب قدیمی عمداً انجام نمی‌شود.
    }
};
