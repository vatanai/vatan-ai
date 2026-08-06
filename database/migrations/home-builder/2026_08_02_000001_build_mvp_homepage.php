<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** چیدمان نهایی و فروش‌محور صفحه Home برای نسخه MVP. */
return new class extends Migration
{
    private const MARKER = 'hb_mvp_home_v1';

    public function up(): void
    {
        if (DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::MARKER . '%')->exists()) {
            return;
        }

        DB::table('home_sections')->where('page_key', 'app_home')->update(['status' => 'hidden']);

        $sections = [
            $this->products('product_slider', 'peek', 'ترندهای امروز', 'پراستفاده‌ترین سبک‌های پرتره برای شروع سریع', [1, 36, 37, 6, 9], 1, 'portrait', ['source' => 'trending', 'hover_effect' => 'neon_glow']),
            $this->products('product_slider', 'motion_shimmer', 'استوری‌هایی که دیده می‌شوند', 'قالب‌های آماده برای ساخت استوری حرفه‌ای و چشم‌گیر', [10, 2, 7, 1], 34, 'social/instagram/story', ['hover_effect' => 'shine']),
            $this->hero('default', 'امروز چه چیزی ترند شده؟', 'محبوب‌ترین ایده‌ها و سبک‌های روز را ببین و با چند کلیک نسخه خودت را بساز.', '/assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'دیدن ترندها', '/trends'),
            $this->products('product_grid', 'bento', 'عکس محصول، آماده فروش', 'محصولت را با عکس‌های حرفه‌ای و تبلیغاتی بهتر معرفی کن', [8, 2, 10, 1, 36, 37], 77, 'business/product-photo', ['hover_effect' => 'zoom_soft']),
            $this->text('هر ایده، یک مسیر آماده برای اجرا', 'از پرتره و محتوای شبکه‌های اجتماعی تا تبلیغات و ویدیو؛ محصول مناسب را انتخاب کن، اطلاعاتت را بده و خروجی آماده تحویل بگیر.'),
            $this->products('product_slider', 'scroll_marquee', 'برای اینستاگرام آماده شو', 'ایده‌های سریع برای پست، استوری و ریلز برند یا صفحه شخصی', [10, 2, 1, 8, 7], 32, 'social/instagram', ['hover_effect' => 'saturate']),
            $this->products('product_grid', 'four_col', 'محبوب‌ترین ابزارهای ساخت', 'انتخاب‌های کاربردی کاربران برای ساخت سریع‌تر محتوا', [1, 2, 8, 10, 3, 7, 9, 6], 46, 'business', ['hover_effect' => 'lift_shadow']),
            $this->banner('/assets/img/dayno-cinematic-ai-photo-prompts-eH9Z8z.jpg', '/app/products', 'از ایده تا خروجی حرفه‌ای با ابزارهای آماده وطن'),
            $this->products('product_slider', 'video_loop', 'ویدیو برای لحظه‌های ماندگار', 'ویدیوهای مناسبتی؛ خودکار، بی‌صدا و آماده تماشا', [3, 7, 10], 85, 'video/occasion-video', ['hover_effect' => 'neon_glow']),
            $this->products('product_slider', 'scroll_wheel', 'استایل مردانه، متفاوت و حرفه‌ای', 'ظاهرهای آماده برای پرتره، پروفایل و محتوای شخصی', [1, 36, 37, 6, 9], 111, 'fashion/mens-style', ['hover_effect' => 'tilt']),
            $this->categoryTabs(),
            $this->text('دنبال نتیجه سریع هستی؟', 'دسته‌بندی موردنظرت را انتخاب کن؛ هزینه هر محصول شفاف است و قبل از شروع دقیقاً می‌دانی چه خروجی‌ای دریافت می‌کنی.'),
            $this->products('product_slider', 'motion_token', 'ویژه باشگاه‌ها و برندهای ورزشی', 'محتوای تبلیغاتی پرانرژی برای جذب و حفظ مشتری', [2, 8, 10, 1], 68, 'business/gym', ['hover_effect' => 'token_bounce']),
            $this->products('product_grid', 'four_col', 'برای شروع یک زندگی مشترک', 'دعوت‌نامه، ویدیو و تصویرهای خاص برای جشن ازدواج', [3, 1, 10, 2], 93, 'occasions/wedding', ['hover_effect' => 'border_draw']),
            $this->hero('centered', 'ایده‌های بیشتری می‌خواهی؟', 'در اکسپلور میان سبک‌ها، خروجی‌ها و پیشنهادهای تازه بچرخ و انتخاب خودت را پیدا کن.', '/assets/img/gemini-vintage-boys-man-with-flowers-ai-photo-editing-prompt-ud1t53g9cf.webp', 'رفتن به اکسپلور', '/explore'),
            $this->products('product_slider', 'scroll_stack', 'یک پروفایل که به‌یاد می‌ماند', 'از آواتار تا پرتره حرفه‌ای؛ تصویر مناسب خودت را پیدا کن', [9, 1, 6, 36, 37], 41, 'social/youtube/profile-picture', ['hover_effect' => 'rotate_soft']),
        ];

        $now = now();
        foreach ($sections as $index => $section) {
            DB::table('home_sections')->insert(array_merge($section, [
                'page_key' => 'app_home',
                'status' => 'published',
                'position' => $index + 1,
                'responsive' => json_encode(['desktop' => true, 'tablet' => true, 'mobile' => true, 'mobile_layout' => null]),
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    private function products(string $type, string $layout, string $title, string $subtitle, array $ids, int $categoryId, string $categoryPath, array $extra = []): array
    {
        $picked = DB::table('products')->whereIn('id', $ids)->get(['id', 'name_fa'])->keyBy('id');
        $productIds = collect($ids)->map(fn (int $id) => ['id' => $id, 'name' => $picked[$id]->name_fa ?? ('محصول ' . $id)])->all();
        $settings = array_merge([
            '_sample' => self::MARKER . ':' . $layout . ':' . $categoryId,
            'source' => 'manual', 'product_ids' => $productIds, 'limit' => count($ids), 'sort' => 'latest',
            'category_id' => $categoryId, 'show_credit' => true, 'show_title' => true, 'show_category' => true,
            'show_view_all' => true, 'view_all_link_mode' => 'manual', 'view_all_link' => '/category/' . $categoryPath,
            'hover_effect' => 'neon_glow',
        ], $extra);

        return ['type' => $type, 'layout' => $layout, 'title_fa' => $title, 'subtitle_fa' => $subtitle, 'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE)];
    }

    private function hero(string $layout, string $heading, string $subheading, string $image, string $cta, string $link): array
    {
        return ['type' => 'hero', 'layout' => $layout, 'title_fa' => null, 'subtitle_fa' => null, 'settings' => json_encode([
            '_sample' => self::MARKER . ':hero:' . $layout . ':' . md5($heading),
            'heading' => $heading, 'subheading' => $subheading, 'image' => $image, 'cta_label' => $cta, 'cta_link' => $link,
        ], JSON_UNESCAPED_UNICODE)];
    }

    private function banner(string $image, string $link, string $alt): array
    {
        return ['type' => 'banner', 'layout' => 'rounded', 'title_fa' => null, 'subtitle_fa' => null, 'settings' => json_encode([
            '_sample' => self::MARKER . ':banner', 'image' => $image, 'link' => $link, 'alt_text' => $alt, 'height' => 'medium',
        ], JSON_UNESCAPED_UNICODE)];
    }

    private function text(string $heading, string $body): array
    {
        return ['type' => 'text', 'layout' => 'center', 'title_fa' => null, 'subtitle_fa' => null, 'settings' => json_encode([
            '_sample' => self::MARKER . ':text:' . md5($heading), 'heading' => $heading, 'body' => $body, 'align' => 'center',
        ], JSON_UNESCAPED_UNICODE)];
    }

    private function categoryTabs(): array
    {
        return ['type' => 'category_slider', 'layout' => 'tabs', 'title_fa' => 'هر چیزی که لازم داری، یک‌جا', 'subtitle_fa' => 'با انتخاب هر دسته، محصولات مرتبط همان بخش را ببین', 'settings' => json_encode([
            '_sample' => self::MARKER . ':tabs', 'limit' => 8, 'products_per_tab' => 8,
            'show_view_all' => false, 'hover_effect' => 'neon_glow',
        ], JSON_UNESCAPED_UNICODE)];
    }

    public function down(): void
    {
        DB::table('home_sections')->where('page_key', 'app_home')->where('settings', 'like', '%' . self::MARKER . '%')->delete();
        DB::table('home_sections')->where('page_key', 'app_home')->where('status', 'hidden')->update(['status' => 'published']);
    }
};
