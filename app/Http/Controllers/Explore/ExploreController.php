<?php

namespace App\Http\Controllers\Explore;

use App\Http\Controllers\Controller;
use App\Services\Explore\ExploreFeedService;
use App\Services\Explore\TrendsService;
use App\Services\SitePageService;
use Illuminate\Http\Request;

/**
 * صفحه‌ی عمومی اکسپلور — فقط رندر. تمام منطق «هوشمند» در ExploreFeedService است.
 */
class ExploreController extends Controller
{
    public function index(Request $request, ExploreFeedService $feed, SitePageService $pages)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $page = $pages->byKey('explore');
        $tiles = $feed->buildFeed('explore', (int) ($page?->content('items_per_page', 48) ?? 48), [
            'query' => $query,
            'new_product_ratio' => 45,
        ]);
        $termRows = $feed->discoverableTerms();
        $layoutStyle = $feed->activeLayoutStyle('explore');
        $layoutPatterns = \App\Models\FeedSetting::DISPLAY_PATTERNS;

        return view('app.ideas', compact('tiles', 'termRows', 'query', 'layoutStyle', 'layoutPatterns'));
    }

    /**
     * صفحه‌ی «ترندز» — قبلاً کاملاً استاتیک/هاردکد بود (۱۰ کارت ثابت با لینک fake به
     * /app/product/demo، بدون هیچ ارتباطی با دیتابیس). حالا از همان موتور فید هوشمند
     * Explore استفاده می‌کند، فقط با یک surface جدا ('trending') تا تنظیمات/سنجاق/بوست
     * این صفحه کاملاً مستقل از صفحه‌ی اکسپلور عمومی از پنل ادمین قابل کنترل باشد.
     */
    public function trending(TrendsService $trends, SitePageService $pages)
    {
        $page = $pages->byKey('trends');
        $data = $trends->buildPage((int) ($page?->content('items_per_page', 24) ?? 24));

        return view('app.trends.index', $data);
    }
}
