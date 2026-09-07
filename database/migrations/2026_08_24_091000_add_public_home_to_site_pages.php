<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_pages')) {
            return;
        }

        $now = now();
        DB::table('site_pages')->updateOrInsert(
            ['key' => 'landing'],
            [
                'name_fa' => 'صفحه نخست سایت',
                'name_en' => 'Public Home',
                'status' => 'published',
                'title' => 'بدون محدودیت بساز؛ با وطن خلق کن',
                'subtitle' => 'عکس، ویدیو و محتوای خلاقانه را با وطن خلق کن. برای شبکه‌های اجتماعی، تصویر پروفایل، استوری، پرتره، بنر و محتوای تبلیغاتی، با چند کلیک خروجی حرفه‌ای بگیر.',
                'meta_title' => 'وطن — ساخت بدون محدودیت',
                'meta_description' => 'وطن؛ پلتفرم فارسی هوش مصنوعی برای ساخت عکس، ویدیو و محتوای خلاقانه.',
                'meta_keywords' => json_encode([], JSON_UNESCAPED_UNICODE),
                'is_indexable' => true,
                'requires_auth' => false,
                'maintenance_mode' => false,
                'display_settings' => json_encode(['show_footer' => true, 'layout_width' => 'default', 'theme' => 'system'], JSON_UNESCAPED_UNICODE),
                'content_settings' => json_encode(['show_page_title' => true, 'show_search' => false, 'items_per_page' => 24, 'cache_ttl' => 300], JSON_UNESCAPED_UNICODE),
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('site_pages')) {
            DB::table('site_pages')->where('key', 'landing')->delete();
        }
    }
};
