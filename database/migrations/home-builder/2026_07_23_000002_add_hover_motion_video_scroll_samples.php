<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** نمونه‌های کتابخانه هاور، حرکت، ویدیو و اسکرول را پیش از نمونه‌های تأییدشده به Home اضافه می‌کند. */
return new class extends Migration
{
    private const MARKER = 'hb_sample_v6';

    public function up(): void
    {
        $samples = [
            ['product_grid', 'hover_library', 'مدل‌های هاور', '۱۵ افکت قابل انتخاب برای کارت محصول', 15],
            ['product_slider', 'motion_token', 'متحرک: توکن جهنده', 'حرکت آیکون کردیت و توکن', 8],
            ['product_slider', 'motion_float', 'متحرک: کارت‌های شناور', 'حرکت آرام کارت‌ها در ارتفاع', 8],
            ['product_slider', 'motion_shimmer', 'متحرک: موج نور', 'عبور پیوسته نور روی کارت‌ها', 8],
            ['product_slider', 'motion_orbit', 'متحرک: مدار آیکون', 'حرکت مداری آیکون روی تصویر', 8],
            ['product_slider', 'motion_wave', 'متحرک: موج کارت‌ها', 'حرکت موجی با زمان‌بندی متفاوت', 8],
            ['product_slider', 'video_loop', 'ویدیوهای حلقه‌ای', 'پخش خودکار، بی‌صدا و پیوسته', 8],
            ['product_slider', 'scroll_vertical', 'اسکرول عمودی', 'فهرست محصول با حرکت بالا و پایین', 10],
            ['product_slider', 'scroll_marquee', 'اسکرول پیوسته', 'حرکت خودکار و بی‌انتها', 8],
            ['product_slider', 'scroll_stack', 'اسکرول پشته‌ای', 'کارت‌های هم‌پوشان و قابل پیمایش', 8],
            ['product_slider', 'scroll_wheel', 'اسکرول چرخ‌وفلکی', 'کارت‌های زاویه‌دار با حرکت عمقی', 8],
        ];

        $samples = collect($samples)->reject(fn (array $sample) => DB::table('home_sections')
            ->where('page_key', 'app_home')->where('type', $sample[0])->where('layout', $sample[1])->exists())->values();
        if ($samples->isEmpty()) return;

        $reviewedLayouts = ['intro', 'intro_dual', 'large', 'tabs', 'hover_showcase', 'minimal', 'neon'];
        $anchor = (int) (DB::table('home_sections')->where('page_key', 'app_home')
            ->whereIn('layout', $reviewedLayouts)->min('position')
            ?: ((int) DB::table('home_sections')->where('page_key', 'app_home')->max('position') + 1));

        DB::table('home_sections')->where('page_key', 'app_home')->where('position', '>=', $anchor)->increment('position', $samples->count());

        $now = now();
        foreach ($samples as $index => [$type, $layout, $title, $subtitle, $limit]) {
            DB::table('home_sections')->insert([
                'page_key' => 'app_home', 'type' => $type, 'layout' => $layout,
                'title_fa' => 'نمونه: ' . $title, 'subtitle_fa' => $subtitle,
                'settings' => json_encode([
                    '_sample' => self::MARKER . ':' . $layout,
                    'source' => 'latest', 'limit' => $limit, 'sort' => 'latest', 'show_credit' => true,
                    'show_view_all' => true, 'view_all_link_mode' => 'auto', 'hover_effect' => 'neon_glow',
                    'hover_grid_cols' => '4', 'hover_grid_rows' => 4,
                ], JSON_UNESCAPED_UNICODE),
                'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
                'status' => 'published', 'position' => $anchor + $index, 'published_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $showcase = DB::table('home_sections')->where('page_key', 'app_home')->where('type', 'product_grid')->where('layout', 'hover_showcase')->first();
        if ($showcase) {
            $settings = json_decode($showcase->settings ?: '{}', true) ?: [];
            $settings['limit'] = max(16, (int) ($settings['limit'] ?? 0));
            $settings['hover_grid_cols'] = $settings['hover_grid_cols'] ?? '4';
            $settings['hover_grid_rows'] = $settings['hover_grid_rows'] ?? 4;
            DB::table('home_sections')->where('id', $showcase->id)->update(['settings' => json_encode($settings, JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::MARKER . '%')->delete();
    }
};
