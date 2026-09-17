<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class CharacterSheetLandingController extends Controller
{
    public function index(): View
    {
        $definitions = [
            [
                'slug' => '213883-body-character-sheet',
                'external_slug' => '213883-body-character-sheet',
                'title' => 'کارکتر شیت بدن',
                'label' => 'مرجع فرم و تناسبات بدن',
                'description' => 'برای ساخت یک مرجع تصویری منسجم از فرم بدن، قد، تناسبات و ظاهر کلی شخصیت؛ مناسب پروژه‌های داستانی، تبلیغاتی و تصویری.',
                'fallback' => 'assets/img/moody-portrait-of-a-young-man-with-a-black-horse-on-a-ranch-ai-photo-editing-prompt.avif',
                'icon' => 'fa-person',
            ],
            [
                'slug' => '406460-facial-character-sheet',
                'external_slug' => '406460-facial-character-sheet',
                'title' => 'کارکتر شیت چهره',
                'label' => 'مرجع هویت و جزئیات چهره',
                'description' => 'برای ثبت ویژگی‌های ثابت چهره و ساخت تصاویر هماهنگ از نماهای مختلف؛ انتخابی کاربردی برای پرتره، آواتار و روایت تصویری.',
                'fallback' => 'assets/img/best-ai-prompts-for-cinematic-photos-and-portraits.jpeg',
                'icon' => 'fa-face-smile',
            ],
            [
                'slug' => '378924-character-sheet-3',
                'external_slug' => '378924-character-sheet-3',
                'title' => 'کارکتر شیت ۳',
                'label' => 'برای پروژه‌های تبلیغاتی و تیزر',
                'description' => 'برای زمانی که می‌خواهی ظاهر یک شخصیت در چند صحنه، قاب و ایده‌ی تبلیغاتی ثابت و قابل تشخیص باقی بماند.',
                'fallback' => 'assets/img/elegant-woman-cafe-portrait-by-promptplum.avif',
                'icon' => 'fa-clapperboard',
            ],
        ];

        $products = Product::query()
            ->where('status', 'active')
            ->whereIn('slug', collect($definitions)->pluck('slug'))
            ->get()
            ->keyBy('slug');

        $cards = collect($definitions)->map(function (array $definition) use ($products): array {
            $product = $products->get($definition['slug']);

            return [
                ...$definition,
                'product' => $product,
                'url' => $product
                    ? route('app.product', ['product' => $product->route_slug])
                    : 'https://aivatan.com/app/product/' . $definition['external_slug'],
                'image' => $product?->displayImageUrl() ?: asset($definition['fallback']),
            ];
        })->values();

        return view('site.character-sheet-landing', [
            'cards' => $cards,
            'canonicalUrl' => route('landing.character-sheet'),
            'heroImages' => $cards->pluck('image')->take(3)->values(),
        ]);
    }
}
