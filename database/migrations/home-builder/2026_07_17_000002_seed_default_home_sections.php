<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * انتقال ۵ ردیف ثابتی که قبلاً مستقیم در HomeController/app/home.blade.php کدنویسی‌شده بود
 * به Sectionهای واقعی دیتابیس (منتشرشده)، تا بعد از فعال‌سازی Home Builder صفحه Home خالی نماند
 * و مدیر بتواند همان محتوای فعلی را از پنل ویرایش/جابه‌جا/حذف کند.
 * Idempotent است: فقط اگر تا الان هیچ Section ای برای صفحه app_home ثبت نشده، این ۵ ردیف را اضافه می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('home_sections')->where('page_key', 'app_home')->exists();
        if ($exists) {
            return;
        }

        $now = now();
        $responsive = json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]);

        $rows = [
            [
                'title_fa' => 'ترندهای امروز',
                'subtitle_fa' => 'پراستفاده ترین سبک ها',
                'settings' => ['source' => 'trending', 'limit' => 8, 'sort' => 'latest', 'show_view_all' => true],
                'position' => 1,
            ],
            [
                'title_fa' => 'کسب و کار',
                'subtitle_fa' => 'محتوا برای برندها',
                'settings' => ['source' => 'category', 'category_value' => 'BUSINESS', 'limit' => 8, 'sort' => 'latest', 'show_view_all' => true],
                'position' => 2,
            ],
            [
                'title_fa' => 'پرتره سینمایی',
                'subtitle_fa' => 'نورپردازی درام و اتمسفر',
                'settings' => ['source' => 'category', 'category_value' => 'PEOPLE', 'limit' => 8, 'sort' => 'latest', 'show_view_all' => true],
                'position' => 3,
            ],
            [
                'title_fa' => 'عکاسی فشن',
                'subtitle_fa' => 'استایل و مد روز',
                'settings' => ['source' => 'category', 'subcategory_value' => 'Fashion', 'limit' => 8, 'sort' => 'latest', 'show_view_all' => true],
                'position' => 4,
            ],
            [
                'title_fa' => 'ریلز و ویدیو',
                'subtitle_fa' => 'محتوای ویدیویی هوشمند',
                'settings' => ['source' => 'video', 'limit' => 8, 'sort' => 'latest', 'show_view_all' => true],
                'position' => 5,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('home_sections')->insert([
                'page_key' => 'app_home',
                'type' => 'product_slider',
                'layout' => 'default',
                'title_fa' => $row['title_fa'],
                'subtitle_fa' => $row['subtitle_fa'],
                'settings' => json_encode($row['settings']),
                'responsive' => $responsive,
                'status' => 'published',
                'position' => $row['position'],
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->delete();
    }
};
