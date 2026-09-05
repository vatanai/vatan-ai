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

        $now = now();
        $presets = [
            'ادیت سریع محصول' => [
                'font_family' => 'B_Yekan',
                'aspect_ratio' => '9:16',
                'transition' => 'cut',
                'transition_duration' => '0.3',
                'hook_background' => 'primary',
                'hook_text_color' => 'light',
                'hook_font_size' => '42',
                'hook_scale' => '1',
                'hook_vertical_offset' => '-8',
                'cta_enabled' => true,
                'cta_background' => 'primary',
                'instagram_enabled' => true,
                'telegram_enabled' => true,
                'youtube_enabled' => true,
                'aparat_enabled' => true,
                'linkedin_enabled' => true,
                'instagram_send_video' => true,
                'telegram_send_video' => true,
                'youtube_send_video' => true,
                'aparat_send_video' => true,
                'linkedin_send_video' => true,
            ],
            'روایت سینمایی محصول' => [
                'font_family' => 'Abar',
                'aspect_ratio' => '9:16',
                'transition' => 'fade',
                'transition_duration' => '0.8',
                'hook_background' => 'dark',
                'hook_text_color' => 'light',
                'hook_font_size' => '36',
                'hook_scale' => '0.95',
                'hook_vertical_offset' => '10',
                'cta_enabled' => true,
                'cta_background' => 'dark',
                'instagram_enabled' => true,
                'telegram_enabled' => true,
                'youtube_enabled' => true,
                'aparat_enabled' => true,
                'linkedin_enabled' => true,
                'instagram_send_video' => true,
                'telegram_send_video' => true,
                'youtube_send_video' => true,
                'aparat_send_video' => true,
                'linkedin_send_video' => true,
            ],
        ];

        foreach ($presets as $name => $settings) {
            DB::table('video_studio_presets')->updateOrInsert(
                ['admin_id' => null, 'name' => $name],
                ['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE), 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('video_studio_presets')) {
            DB::table('video_studio_presets')->whereNull('admin_id')->whereIn('name', ['ادیت سریع محصول', 'روایت سینمایی محصول'])->delete();
        }
    }
};
