<?php

namespace Vatan\Seo\Data\Rank;

use Vatan\Seo\Models\QueryMetric;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

/**
 * رتبه از داده‌ی واقعی سرچ کنسول (رایگان).
 * برای هر کلمه، آخرین روزی که ایمپرشن داشته را می‌گیرد؛ اگر در ۷ روز اخیر دیده نشده،
 * میانگین وزنی ۲۸ روزه گزارش می‌شود (با برچسب «تخمینی»). داده‌ی گوگل ۲ تا ۳ روز تأخیر دارد.
 */
class GscRankProvider implements RankProvider
{
    public function key(): string
    {
        return 'gsc';
    }

    public function fetch(Site $site, $keywords): array
    {
        $out = [];
        foreach ($keywords as $kw) {
            $hash = sha1(Fa::normalizeKeyword($kw->keyword));
            $rows = QueryMetric::query()
                ->where('site_id', $site->id)
                ->where('query_hash', $hash)
                ->where('date', '>=', now()->subDays(28)->toDateString())
                ->orderByDesc('date')
                ->get(['date', 'page', 'clicks', 'impressions', 'position']);
            if ($rows->isEmpty()) {
                continue;
            }
            $latestDate = $rows->first()->date;
            $latest = $rows->where('date', $latestDate);
            $recent = $latestDate->gte(now()->subDays(7));
            $set = $recent ? $latest : $rows;
            $imp = max(1, $set->sum('impressions'));
            $position = $set->sum(fn ($r) => $r->position * max(1, $r->impressions)) / max(1, $set->sum(fn ($r) => max(1, $r->impressions)));
            $best = $set->sortByDesc('clicks')->sortBy('position')->first();
            $out[$kw->id] = [
                'position' => round($position, 1),
                'url' => $best?->page,
                'clicks' => (int) $rows->sum('clicks'),
                'impressions' => (int) $rows->sum('impressions'),
                'date' => $latestDate->toDateString(),
                'estimated' => ! $recent,
            ];
        }
        return $out;
    }
}
