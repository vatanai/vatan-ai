<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\HomePageGalleryService;
use App\Services\PlanCatalogService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function __construct(
        private readonly PlanCatalogService $catalog,
        private readonly HomePageGalleryService $galleries,
    ) {
    }

    public function index(): View
    {
        $currentUser = auth()->user();

        try {
            $loadArticles = function () {
                return Schema::hasTable('articles')
                    ? Article::query()->published()->with(['category', 'author'])
                        ->orderByDesc('is_featured')->latest('published_at')->limit(4)->get()
                    : collect();
            };
            $homeArticles = $currentUser ? $loadArticles() : Cache::remember('public-home:articles:v1', now()->addMinutes(2), $loadArticles);
        } catch (\Throwable $exception) {
            report($exception);
            $homeArticles = collect();
        }

        $ideasFallback = collect([
            'elegant-woman-cafe-portrait-by-promptplum.avif',
            'best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
            'best-friends-ai-prompt-2.webp',
            '9cb93b50-d93f-462f-b6d4-113f63ffc603.avif',
            'ai-photo-editor-prompt.webp',
            'prompt-for-gemini-ai-girl.webp',
        ])->map(fn (string $image) => ['url' => asset('assets/img/' . $image), 'title' => 'ایده آماده', 'media_type' => 'image'])->all();

        $heroFallback = collect([
            'best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
            'gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp',
            'Screenshot-2025-12-09-at-12.33.35-PM.avif',
            'Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg',
            'promptbank176.webp',
            'lookasjide.fbsbx.webp',
            'A-man-in-a-white-t-shirt-and-jeans-sits-on-a-rooftop-at-dusk-gazing-contemplatively-at-a-bright-full-moon-above-him.-The-scene-conveys-serenity-and-wonder.jpg',
        ])->map(fn (string $image) => ['url' => asset('assets/img/' . $image), 'title' => 'نمونه وطن', 'media_type' => 'image'])->all();

        $inspirationFallback = collect([
            ['best-ai-prompts-for-cinematic-photos-and-portraits.jpeg', 'سینمایی', 'پرتره‌ای با نور عمیق'],
            ['gemini-boy-standing-on-road-outoor-editing-prompt-tve6lh5nkd.webp', 'پروفایل', 'برای حضور حرفه‌ای‌تر'],
            ['60ed34f8-ed85-4ae0-9b63-191dcbe11800.mp4', 'خلاقانه', 'ایده‌ای خارج از قاب', 'video'],
            ['Couple-bike-photo-edit-using-AI-Google-Gemini-with-stylish-effects-and-professional-finish-768x1365.jpg', 'ترند', 'حال‌وهوای امروزی'],
            ['a1be8a17-0f52-44e3-8693-6f2d7a3056b2.mp4', 'فشن', 'قاب ادیتوریال', 'video'],
        ])->map(fn (array $item) => [
            'url' => asset(($item[3] ?? 'image') === 'video' ? 'assets/videos/' . $item[0] : 'assets/img/' . $item[0]),
            'tag' => $item[1],
            'title' => $item[2],
            'media_type' => $item[3] ?? 'image',
        ])->all();

        return view('site.preview.home', array_merge($this->catalog->catalog($currentUser), [
            'heroGallery' => $this->galleries->visible('hero-gallery', $heroFallback),
            'ideasGallery' => $this->galleries->visible('ideas-gallery', $ideasFallback),
            'inspirationGallery' => $this->galleries->visible('inspiration-gallery', $inspirationFallback),
            'homeArticles' => $homeArticles,
            'homePricingPlans' => $this->catalog->homePricingPlans($currentUser)->take(4)->values(),
            'homePricing' => \App\Models\PlanSetting::homePricing(),
        ]));
    }
}
