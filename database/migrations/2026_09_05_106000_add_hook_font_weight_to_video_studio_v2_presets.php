<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('video_studio_presets')) {
            return;
        }

        DB::table('video_studio_presets')
            ->whereNull('admin_id')
            ->whereIn('name', ['ادیت سریع محصول', 'روایت سینمایی محصول'])
            ->orderBy('id')
            ->get(['id', 'name', 'settings'])
            ->each(function (object $preset): void {
                $settings = json_decode((string) $preset->settings, true);
                $settings = is_array($settings) ? $settings : [];
                $settings['hook_font_weight'] = $preset->name === 'روایت سینمایی محصول' ? 5 : 4;
                DB::table('video_studio_presets')->where('id', $preset->id)->update([
                    'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('video_studio_presets')) {
            return;
        }

        DB::table('video_studio_presets')
            ->whereNull('admin_id')
            ->whereIn('name', ['ادیت سریع محصول', 'روایت سینمایی محصول'])
            ->orderBy('id')
            ->get(['id', 'settings'])
            ->each(function (object $preset): void {
                $settings = json_decode((string) $preset->settings, true);
                $settings = is_array($settings) ? $settings : [];
                unset($settings['hook_font_weight']);
                DB::table('video_studio_presets')->where('id', $preset->id)->update([
                    'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            });
    }
};
