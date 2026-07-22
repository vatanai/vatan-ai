<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * افزودن Sectionهای «نمونه» با همه‌ی Layoutهای جدید (لبه‌نما، بنتو، کارت معرفی، کارت بزرگ،
 * تب دسته‌بندی، کارت شیشه‌ای، قاب نئونی) به صفحه Home — منتشرشده، با منبع «جدیدترین محصولات»
 * تا بلافاصله بعد از دیپلوی همه‌ی حالت‌های نمایشی روی صفحه دیده شوند و مدیر بتواند
 * هرکدام را از پنل Home Builder ویرایش/مخفی/حذف کند.
 * Idempotent است: هر ردیف فقط اگر با همان مارکر نمونه قبلاً ثبت نشده باشد اضافه می‌شود.
 */
return new class extends Migration
{
    private const SAMPLE_MARKER = 'hb_sample_v1';

    public function up(): void
    {
        $now = now();
        $responsive = json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]);

        $basePosition = (int) DB::table('home_sections')->where('page_key', 'app_home')->max('position');

        $rows = [
            [
                'type' => 'product_slider',
                'layout' => 'peek',
                'title_fa' => 'نمونه: اسلایدر لبه‌نما',
                'subtitle_fa' => 'هاور لیمویی + کردیت زیر کارت',
                'settings' => ['source' => 'latest', 'limit' => 10, 'sort' => 'latest', 'show_view_all' => true, 'show_credit' => true],
            ],
            [
                'type' => 'product_grid',
                'layout' => 'bento',
                'title_fa' => 'نمونه: گرید بنتو',
                'subtitle_fa' => 'چیدمان نامتقارن با برچسب دسته',
                'settings' => ['source' => 'latest', 'limit' => 8, 'sort' => 'latest', 'show_credit' => false],
            ],
            [
                'type' => 'product_slider',
                'layout' => 'intro',
                'title_fa' => 'نمونه: اسلایدر با کارت معرفی',
                'subtitle_fa' => 'کارت معرفی قابل‌تنظیم + کارت‌های واید',
                'settings' => [
                    'source' => 'latest', 'limit' => 8, 'sort' => 'latest', 'show_credit' => true,
                    'intro_badge' => 'استودیو هوش مصنوعی',
                    'intro_heading' => 'تولید محتوای حرفه‌ای با',
                    'intro_heading_accent' => 'وطن AI',
                    'intro_desc' => 'محصول را انتخاب کن، تصویر یا اطلاعاتت را آپلود کن و خروجی نهایی را در چند ثانیه بگیر.',
                    'intro_steps' => "انتخاب محصول یا سبک\nآپلود تصویر یا اطلاعات\nدریافت خروجی نهایی",
                    'intro_note' => 'بدون نیاز به دانش طراحی',
                    'intro_cta_label' => 'شروع کنید',
                    'intro_cta_link' => '/explore',
                ],
            ],
            [
                'type' => 'product_slider',
                'layout' => 'large',
                'title_fa' => 'نمونه: کارت بزرگ',
                'subtitle_fa' => 'نمایش بزرگ‌تر با برچسب وضعیت',
                'settings' => ['source' => 'latest', 'limit' => 6, 'sort' => 'latest', 'show_view_all' => true, 'show_credit' => true],
            ],
            [
                'type' => 'category_slider',
                'layout' => 'tabs',
                'title_fa' => 'نمونه: تب دسته‌بندی + اسلایدر',
                'subtitle_fa' => 'کلیک روی هر تب، محصولات همان دسته',
                'settings' => ['limit' => 6, 'products_per_tab' => 8, 'show_credit' => true],
            ],
            [
                'type' => 'product_slider',
                'layout' => 'glass',
                'title_fa' => 'نمونه: کارت شیشه‌ای',
                'subtitle_fa' => 'سطح نیمه‌شفاف با بلور پس‌زمینه',
                'settings' => ['source' => 'latest', 'limit' => 10, 'sort' => 'latest', 'show_view_all' => true, 'show_credit' => true],
            ],
            [
                'type' => 'product_slider',
                'layout' => 'neon',
                'title_fa' => 'نمونه: قاب نئونی',
                'subtitle_fa' => 'قاب لیمویی درخشان + نشان کردیت',
                'settings' => ['source' => 'latest', 'limit' => 10, 'sort' => 'latest', 'show_view_all' => true, 'show_credit' => true],
            ],
        ];

        foreach ($rows as $i => $row) {
            $settings = $row['settings'] + ['_sample' => self::SAMPLE_MARKER . ':' . $row['layout']];

            $exists = DB::table('home_sections')
                ->where('page_key', 'app_home')
                ->where('settings', 'like', '%' . self::SAMPLE_MARKER . ':' . $row['layout'] . '%')
                ->exists();
            if ($exists) {
                continue;
            }

            DB::table('home_sections')->insert([
                'page_key' => 'app_home',
                'type' => $row['type'],
                'layout' => $row['layout'],
                'title_fa' => $row['title_fa'],
                'subtitle_fa' => $row['subtitle_fa'],
                'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                'responsive' => $responsive,
                'status' => 'published',
                'position' => $basePosition + $i + 1,
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('home_sections')
            ->where('page_key', 'app_home')
            ->where('settings', 'like', '%' . self::SAMPLE_MARKER . '%')
            ->delete();
    }
};
