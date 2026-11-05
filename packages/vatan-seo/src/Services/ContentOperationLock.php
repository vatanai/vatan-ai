<?php

namespace Vatan\Seo\Services;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Vatan\Seo\Models\Site;

/** Prevent duplicate article generation and redrafts for the same keyword. */
class ContentOperationLock
{
    public static function forKeyword(Site $site, int|string $keywordId): Lock
    {
        return Cache::lock("seo-content:{$site->id}:keyword:{$keywordId}", 900);
    }
}
