<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * برای هر Layout ثبت‌شده یک نمونه منتشرشده و قابل‌مدیریت روی Home می‌سازد.
 * اگر همان type/layout از قبل وجود داشته باشد، نمونه تکراری ایجاد نمی‌شود.
 */
return new class extends Migration
{
    private const MARKER = 'hb_sample_v2';

    public function up(): void
    {
        $layouts = [
            'hero' => ['default', 'centered'],
            'product_slider' => ['default', 'compact', 'peek', 'intro', 'large', 'glass', 'neon', 'cinema', 'minimal'],
            'product_grid' => ['default', 'two_col', 'four_col', 'bento', 'editorial'],
            'category_slider' => ['default', 'tabs'],
            'banner' => ['default', 'rounded'],
            'collection' => ['default'],
            'text' => ['default', 'center'],
            'spacer' => ['default'],
        ];

        $labels = [
            'default' => 'پیش‌فرض', 'centered' => 'وسط‌چین', 'compact' => 'فشرده',
            'peek' => 'لبه‌نما', 'intro' => 'کارت معرفی', 'large' => 'کارت بزرگ',
            'glass' => 'شیشه‌ای', 'neon' => 'نئونی', 'cinema' => 'سینمایی',
            'minimal' => 'مینیمال', 'two_col' => 'دو ستونه', 'four_col' => 'چهار ستونه',
            'bento' => 'بنتو', 'editorial' => 'ادیتوریال', 'tabs' => 'تب دسته‌بندی',
            'rounded' => 'بنر گرد', 'center' => 'متن وسط‌چین',
        ];

        $now = now();
        $position = (int) DB::table('home_sections')->where('page_key', 'app_home')->max('position');
        $responsive = json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]);

        foreach ($layouts as $type => $typeLayouts) {
            foreach ($typeLayouts as $layout) {
                $exists = DB::table('home_sections')
                    ->where('page_key', 'app_home')
                    ->where('type', $type)
                    ->where('layout', $layout)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $position++;
                DB::table('home_sections')->insert([
                    'page_key' => 'app_home',
                    'type' => $type,
                    'layout' => $layout,
                    'title_fa' => 'نمونه ' . ($labels[$layout] ?? $layout),
                    'subtitle_fa' => 'مدل نمایشی قابل ویرایش در مدیریت صفحه هوم',
                    'settings' => json_encode($this->settings($type, $layout), JSON_UNESCAPED_UNICODE),
                    'responsive' => $responsive,
                    'status' => 'published',
                    'position' => $position,
                    'published_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    private function settings(string $type, string $layout): array
    {
        $base = ['_sample' => self::MARKER . ':' . $type . ':' . $layout];

        return $base + match ($type) {
            'hero' => [
                'heading' => 'هر ایده‌ای می‌تواند واقعی شود',
                'subheading' => 'مدل نمایشی هدر وطن با تصویر واقعی و دعوت به اقدام.',
                'image' => '/assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
                'cta_label' => 'شروع ساخت', 'cta_link' => '/create',
            ],
            'product_slider', 'product_grid', 'collection' => [
                'source' => 'latest', 'limit' => 8, 'sort' => 'latest',
                'show_credit' => true, 'show_view_all' => true, 'view_all_link_mode' => 'auto',
                'intro_badge' => 'استودیو وطن', 'intro_heading' => 'از ایده تا خروجی با',
                'intro_heading_accent' => 'هوش مصنوعی',
                'intro_desc' => 'محصول مناسب را انتخاب کن و خروجی حرفه‌ای خودت را بساز.',
                'intro_steps' => "انتخاب مدل\nواردکردن اطلاعات\nدریافت خروجی",
                'intro_note' => 'ساده، سریع و قابل تنظیم',
                'intro_cta_label' => 'مشاهده محصولات', 'intro_cta_link' => '/app/products',
            ],
            'category_slider' => [
                'limit' => 8, 'products_per_tab' => 8,
                'show_view_all' => true, 'view_all_link_mode' => 'auto',
            ],
            'banner' => [
                'image' => '/assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp',
                'alt_text' => 'بنر نمایشی وطن', 'link' => '/app/products', 'height' => 'medium',
            ],
            'text' => [
                'heading' => 'خلاقیت بدون مرز',
                'body' => 'این بلوک متنی یک نمونه قابل ویرایش از مدل‌های صفحه هوم است.',
                'align' => $layout === 'center' ? 'center' : 'right',
            ],
            'spacer' => ['height' => 'medium'],
            default => [],
        };
    }

    public function down(): void
    {
        DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('settings', 'like', '%' . self::MARKER . '%')
            ->delete();
    }
};
