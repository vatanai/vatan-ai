<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;

/** شمارنده‌های کوچک منوی سئو؛ با عمر کوتاه تا نشانگرها تازه بمانند و کوئری تکراری نشود. */
class NavStats
{
    public function for(Site $site): array
    {
        return Cache::remember('seo-engine:nav-stats:'.$site->id, now()->addSeconds(30), static fn (): array => [
            'review' => (int) ContentItem::where('site_id', $site->id)->where('status', 'review')->count(),
            'action' => (int) Task::where('site_id', $site->id)->where('status', 'needs_action')->where('automation', '!=', 'auto')->count(),
        ]);
    }
}
