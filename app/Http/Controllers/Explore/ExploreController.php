<?php

namespace App\Http\Controllers\Explore;

use App\Http\Controllers\Controller;
use App\Services\Explore\ExploreFeedService;

/**
 * صفحه‌ی عمومی اکسپلور — فقط رندر. تمام منطق «هوشمند» در ExploreFeedService است.
 */
class ExploreController extends Controller
{
    public function index(ExploreFeedService $feed)
    {
        $tiles = $feed->buildFeed('explore', 48);

        return view('app.ideas', compact('tiles'));
    }

    /**
     * صفحه‌ی «ترندز» — قبلاً کاملاً استاتیک/هاردکد بود (۱۰ کارت ثابت با لینک fake به
     * /app/product/demo، بدون هیچ ارتباطی با دیتابیس). حالا از همان موتور فید هوشمند
     * Explore استفاده می‌کند، فقط با یک surface جدا ('trending') تا تنظیمات/سنجاق/بوست
     * این صفحه کاملاً مستقل از صفحه‌ی اکسپلور عمومی از پنل ادمین قابل کنترل باشد.
     */
    public function trending(ExploreFeedService $feed)
    {
        $tiles = $feed->buildFeed('trending', 24);

        return view('app.explore', compact('tiles'));
    }
}
