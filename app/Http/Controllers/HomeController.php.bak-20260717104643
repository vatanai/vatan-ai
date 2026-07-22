<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    /**
     * نمایش صفحه اصلی اپ با محصولات واقعی دیتابیس
     * هر بخش (ترندها، کسب‌وکار، پرتره، فشن، ویدیو) جداگانه واکشی می‌شود
     * تا دقیقاً همان ردیف‌های فرانت قبلی پر شوند، بدون تغییر در ساختار ویو.
     */
    public function index()
    {
        $baseQuery = Product::query()->where('status', 'active');

        // ردیف ۱: ترندهای امروز
        $trending = (clone $baseQuery)
            ->where('is_trending', true)
            ->latest()
            ->limit(8)
            ->get();

        // ردیف ۲: کسب‌وکار
        $business = (clone $baseQuery)
            ->where('category', 'BUSINESS')
            ->latest()
            ->limit(8)
            ->get();

        // ردیف ۳: پرتره سینمایی (دسته PEOPLE)
        $portrait = (clone $baseQuery)
            ->where('category', 'PEOPLE')
            ->latest()
            ->limit(8)
            ->get();

        // ردیف ۴: عکاسی فشن (زیردسته Fashion)
        $fashion = (clone $baseQuery)
            ->where('subcategory', 'Fashion')
            ->latest()
            ->limit(8)
            ->get();

        // ردیف ۵: ریلز و ویدیو (محصولاتی که نوع رسانه‌شان ویدیو یا هر دو است)
        $videos = (clone $baseQuery)
            ->whereIn('media_type', ['video', 'both'])
            ->latest()
            ->limit(8)
            ->get();

        return view('app.home', compact('trending', 'business', 'portrait', 'fashion', 'videos'));
    }
}