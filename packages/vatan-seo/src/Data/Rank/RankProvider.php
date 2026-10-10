<?php

namespace Vatan\Seo\Data\Rank;

use Vatan\Seo\Models\Site;

interface RankProvider
{
    public function key(): string;

    /**
     * @param \Illuminate\Support\Collection<int, \Vatan\Seo\Models\Keyword> $keywords
     * @return array<int, array{position:?float, url:?string, clicks:int, impressions:int, date:string}>
     */
    public function fetch(Site $site, $keywords): array;
}
