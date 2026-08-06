<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** نمونه مستقل آزمایشگاه هاور را بعد از سکشن تب دسته‌بندی روی Home قرار می‌دهد. */
return new class extends Migration
{
    private const MARKER = 'hb_sample_v4:hover_showcase';

    public function up(): void
    {
        if (DB::table('home_sections')->where('page_key', 'app_home')->where('type', 'product_grid')->where('layout', 'hover_showcase')->exists()) {
            return;
        }

        $anchor = DB::table('home_sections')
            ->where('page_key', 'app_home')->where('type', 'category_slider')->where('layout', 'tabs')
            ->orderBy('position')->first();
        $position = $anchor?->position ?? (int) DB::table('home_sections')->where('page_key', 'app_home')->max('position');

        DB::table('home_sections')->where('page_key', 'app_home')->where('position', '>', $position)->increment('position');

        $now = now();
        DB::table('home_sections')->insert([
            'page_key' => 'app_home', 'type' => 'product_grid', 'layout' => 'hover_showcase',
            'title_fa' => 'نمونه: آزمایشگاه هاور کارت‌ها',
            'subtitle_fa' => 'زوم نرم، قاب لیمویی، نمایش اطلاعات، شناور و سایه',
            'settings' => json_encode([
                '_sample' => self::MARKER, 'source' => 'latest', 'limit' => 8, 'sort' => 'latest',
                'show_credit' => true, 'show_view_all' => true, 'view_all_link_mode' => 'auto',
            ], JSON_UNESCAPED_UNICODE),
            'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
            'status' => 'published', 'position' => $position + 1, 'published_at' => $now,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::MARKER . '%')->delete();
    }
};
