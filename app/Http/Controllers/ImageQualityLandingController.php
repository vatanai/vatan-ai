<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ImageQualityLandingController extends Controller
{
    public function index(): View
    {
        $definitions = [
            [
                'slug' => '037319-increase-in-quality-5',
                'title' => 'افزایش کیفیت حرفه‌ای عکس',
                'label' => 'بالاترین خروجی',
                'description' => 'برای عکس‌هایی که جزئیات بیشتر، وضوح بالاتر و خروجی جدی‌تری برای چاپ یا انتشار می‌خواهند.',
                'fallback' => 'assets/img/ai-photo-editor-prompt.webp',
            ],
            [
                'slug' => '609387-increase-in-quality-3',
                'title' => 'افزایش کیفیت سریع عکس',
                'label' => 'انتخاب محبوب',
                'description' => 'یک مسیر سریع برای واضح‌تر کردن عکس‌های روزمره، شبکه‌های اجتماعی و تصاویر کم‌کیفیت.',
                'fallback' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
            ],
            [
                'slug' => '652381-increase-in-quality-2',
                'title' => 'بهبود وضوح و جزئیات',
                'label' => 'برای جزئیات بیشتر',
                'description' => 'برای زمانی که می‌خواهی بافت، نور و جزئیات سوژه در عکس تمیزتر و خواناتر دیده شود.',
                'fallback' => 'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
            ],
            [
                'slug' => '520738-quality-improvement',
                'title' => 'بهبود کیفیت تصویر',
                'label' => 'شروع ساده',
                'description' => 'راهی ساده برای جان‌دادن دوباره به عکس‌های قدیمی، تار یا کم‌نور و آماده‌کردن آن‌ها برای استفاده.',
                'fallback' => 'assets/img/Screenshot-2025-12-09-at-12.33.35-PM.avif',
            ],
        ];

        $slugs = collect($definitions)->pluck('slug')->map(
            fn (string $slug) => preg_replace('/^\d{6}-/', '', $slug)
        );
        $products = Product::query()
            ->where('status', 'active')
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        $cards = collect($definitions)->map(function (array $definition) use ($products): array {
            $productSlug = preg_replace('/^\d{6}-/', '', $definition['slug']);
            $product = $products->get($productSlug);

            return [
                ...$definition,
                'product' => $product,
                // در دیتابیس لوکال ممکن است این محصولات همگام نشده باشند؛
                // در این حالت همان لینک رسمی محصول حفظ می‌شود تا کارت به ۴۰۴ نرسد.
                'url' => $product
                    ? route('app.product', ['product' => $product->route_slug])
                    : 'https://aivatan.com/app/product/' . $definition['slug'],
                'image' => $product?->displayImageUrl() ?: asset($definition['fallback']),
            ];
        })->values();

        return view('site.image-quality-landing', [
            'cards' => $cards,
            'canonicalUrl' => route('landing.image-quality'),
            'heroImages' => $cards->pluck('image')->take(3)->values(),
        ]);
    }
}
