<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $galleries = [
            'hero-gallery' => [
                ['type' => 'asset', 'path' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/promptbank176.webp', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/lookasjide.fbsbx.webp', 'title' => 'نمونه وطن'],
                ['type' => 'asset', 'path' => 'assets/img/A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg', 'title' => 'نمونه وطن'],
            ],
            'ideas-gallery' => [
                ['type' => 'asset', 'path' => 'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif', 'title' => 'ایده آماده'],
                ['type' => 'asset', 'path' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'title' => 'ایده آماده'],
                ['type' => 'asset', 'path' => 'assets/img/best-friends-ai-prompt-2.webp', 'title' => 'ایده آماده'],
                ['type' => 'asset', 'path' => 'assets/img/9cb93b50-d93f-462f-b6d4-113f63ffc603.avif', 'title' => 'ایده آماده'],
                ['type' => 'asset', 'path' => 'assets/img/ai-photo-editor-prompt.webp', 'title' => 'ایده آماده'],
                ['type' => 'asset', 'path' => 'assets/img/prompt-for-gemini-ai-girl.webp', 'title' => 'ایده آماده'],
            ],
            'inspiration-gallery' => [
                ['type' => 'asset', 'path' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'tag' => 'سینمایی', 'title' => 'پرتره‌ای با نور عمیق'],
                ['type' => 'asset', 'path' => 'assets/img/gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp', 'tag' => 'پروفایل', 'title' => 'برای حضور حرفه‌ای‌تر'],
                ['type' => 'video', 'path' => 'assets/videos/60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4', 'tag' => 'خلاقانه', 'title' => 'ایده‌ای خارج از قاب'],
                ['type' => 'asset', 'path' => 'assets/img/Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg', 'tag' => 'ترند', 'title' => 'حال‌وهوای امروزی'],
                ['type' => 'video', 'path' => 'assets/videos/a1be8a17-0f52-44e3-8693-6f2d7a3056b2.mp4', 'tag' => 'فشن', 'title' => 'قاب ادیتوریال'],
            ],
        ];

        foreach ($galleries as $key => $items) {
            DB::table('home_page_galleries')
                ->where('key', $key)
                ->whereJsonLength('items', 0)
                ->update([
                    'items' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        foreach (['hero-gallery', 'ideas-gallery', 'inspiration-gallery'] as $key) {
            DB::table('home_page_galleries')
                ->where('key', $key)
                ->update(['items' => json_encode([]), 'updated_at' => now()]);
        }
    }
};
