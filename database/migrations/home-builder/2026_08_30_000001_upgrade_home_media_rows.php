<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** به‌روزرسانی سکشن‌های فعلی هوم برای نمایش دو ردیفه و پخش ویدیو. */
return new class extends Migration
{
    public function up(): void
    {
        $this->updateSettings('ترندهای امروز', [
            'source' => 'latest_non_trending',
            'limit' => 24,
            'display_rows' => '2',
        ]);

        $this->updateSettings('استوری‌هایی که دیده می‌شوند', [
            'display_rows' => '2',
        ]);

        $this->updateSettings('ویدیو برای لحظه‌های ماندگار', [
            'display_rows' => '2',
        ]);

        $this->updateSettings('قصه‌ها را به حرکت درآور', [
            'source' => 'video',
            'limit' => 5,
            'display_rows' => '2',
        ]);

        $this->updateSettings('هر چیزی که لازم داری، یک‌جا', [
            'display_rows' => '2',
        ]);
    }

    private function updateSettings(string $title, array $changes): void
    {
        DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('title_fa', $title)
            ->orderBy('id')
            ->get(['id', 'settings'])
            ->each(function (object $section) use ($changes): void {
                $settings = json_decode((string) $section->settings, true);
                $settings = is_array($settings) ? $settings : [];
                $settings = array_merge($settings, $changes);

                DB::table('home_sections')->where('id', $section->id)->update([
                    'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        // تنظیمات سکشن‌ها عمداً به مقدار قبلی برگردانده نمی‌شوند تا انتخاب‌های ادمین از بین نرود.
    }
};
