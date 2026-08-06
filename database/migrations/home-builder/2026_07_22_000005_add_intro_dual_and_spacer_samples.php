<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** نمونه دو ردیفه را بلافاصله بعد از intro و یک Spacer قابل تنظیم را بعد از آن قرار می‌دهد. */
return new class extends Migration
{
    private const DUAL_MARKER = 'hb_sample_v3:intro_dual';
    private const SPACER_MARKER = 'hb_sample_v3:spacer';

    public function up(): void
    {
        $intro = DB::table('home_sections')
            ->where('page_key', 'app_home')->where('type', 'product_slider')->where('layout', 'intro')
            ->orderBy('position')->first();
        $basePosition = $intro?->position ?? (int) DB::table('home_sections')->where('page_key', 'app_home')->max('position');

        $needsDual = ! DB::table('home_sections')->where('page_key', 'app_home')->where('type', 'product_slider')->where('layout', 'intro_dual')->exists();
        $needsSpacer = ! DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::SPACER_MARKER . '%')->exists();
        $insertCount = (int) $needsDual + (int) $needsSpacer;
        if ($insertCount === 0) return;

        DB::table('home_sections')->where('page_key', 'app_home')->where('position', '>', $basePosition)->increment('position', $insertCount);

        $now = now();
        $responsive = json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]);
        $nextPosition = $basePosition;

        if ($needsDual) {
            DB::table('home_sections')->insert([
                'page_key' => 'app_home', 'type' => 'product_slider', 'layout' => 'intro_dual',
                'title_fa' => 'نمونه: کارت معرفی دو ردیفه',
                'subtitle_fa' => 'دو ردیف کارت نئونی با اطلاعات روی تصویر',
                'settings' => json_encode([
                    '_sample' => self::DUAL_MARKER, 'source' => 'latest', 'limit' => 12, 'sort' => 'latest',
                    'show_credit' => true, 'show_title' => true, 'show_category' => true,
                    'show_view_all' => true, 'view_all_link_mode' => 'auto', 'intro_scroll_mode' => 'together',
                    'intro_badge' => 'مدل اختصاصی وطن', 'intro_heading' => 'محصولات بیشتر در',
                    'intro_heading_accent' => 'دو ردیف',
                    'intro_desc' => 'کارت‌های عمودی با ظاهر نئونی و اطلاعات مستقیم روی تصویر.',
                    'intro_steps' => "انتخاب محصول\nتنظیم اطلاعات نمایشی\nانتشار در هوم",
                    'intro_note' => 'قابل تنظیم برای موبایل و دسکتاپ',
                    'intro_cta_label' => 'مشاهده محصولات', 'intro_cta_link' => '/app/products',
                ], JSON_UNESCAPED_UNICODE),
                'responsive' => $responsive, 'status' => 'published', 'position' => ++$nextPosition,
                'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        if ($needsSpacer) {
            DB::table('home_sections')->insert([
                'page_key' => 'app_home', 'type' => 'spacer', 'layout' => 'default',
                'title_fa' => 'فاصله بعد از نمونه دو ردیفه', 'subtitle_fa' => null,
                'settings' => json_encode(['_sample' => self::SPACER_MARKER, 'spacing_mode' => 'auto', 'height' => 'medium'], JSON_UNESCAPED_UNICODE),
                'responsive' => $responsive, 'status' => 'published', 'position' => ++$nextPosition,
                'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where(function ($query) {
            $query->where('settings', 'like', '%' . self::DUAL_MARKER . '%')
                ->orWhere('settings', 'like', '%' . self::SPACER_MARKER . '%');
        })->delete();
    }
};
