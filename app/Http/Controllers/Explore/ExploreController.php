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
}
