<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\DB;
use Vatan\Seo\Data\Google\SearchConsole;
use Vatan\Seo\Models\DailyMetric;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

/**
 * همگام‌سازی سرچ کنسول:
 *  - مجموع روزانه‌ی سایت (کلیک، ایمپرشن، CTR، میانگین رتبه)
 *  - جزئیات کوئری × صفحه × روز (پایه‌ی رتبه، فرصت‌ها و همنوع‌خواری)
 * بار اول ۹۰ روز گذشته، بعد از آن هر روز ۵ روز اخیر (چون داده‌ی گوگل تا ۳ روز اصلاح می‌شود).
 */
class GscSync
{
    public function __construct(private SearchConsole $gsc) {}

    public function sync(Site $site, ?int $days = null): array
    {
        $property = $site->gscProperty();
        $hasData = DailyMetric::where('site_id', $site->id)->exists();
        $days ??= $hasData ? 5 : 90;
        $end = now()->subDay()->toDateString();
        $start = now()->subDays($days)->toDateString();

        // ۱) مجموع روزانه
        $totals = $this->gsc->query($property, $start, $end, ['date'], 1000);
        foreach ($totals as $row) {
            DailyMetric::updateOrCreate(
                ['site_id' => $site->id, 'date' => $row['date']],
                ['clicks' => $row['clicks'], 'impressions' => $row['impressions'], 'ctr' => $row['ctr'], 'position' => round($row['position'], 2)]
            );
        }

        // ۲) کوئری × صفحه × روز
        $rows = $this->gsc->query($property, $start, $end, ['date', 'query', 'page'], 100000);
        $buffer = [];
        $perDay = [];
        foreach ($rows as $r) {
            $q = (string) $r['query'];
            $p = (string) $r['page'];
            $buffer[] = [
                'site_id' => $site->id,
                'date' => $r['date'],
                'query' => mb_substr($q, 0, 500),
                'query_hash' => sha1(Fa::normalizeKeyword($q)),
                'page' => mb_substr($p, 0, 700),
                'page_hash' => sha1($p),
                'clicks' => $r['clicks'],
                'impressions' => $r['impressions'],
                'ctr' => round($r['ctr'], 4),
                'position' => round($r['position'], 2),
            ];
            $d = $r['date'];
            $perDay[$d]['queries'][$q] = min($perDay[$d]['queries'][$q] ?? 999, $r['position']);
            $perDay[$d]['pages'][$p] = true;
            if (count($buffer) >= 1000) {
                $this->flush($buffer);
            }
        }
        $this->flush($buffer);

        // ۳) توزیع رتبه‌ی کوئری‌ها برای هر روز
        foreach ($perDay as $date => $info) {
            $positions = array_values($info['queries'] ?? []);
            DailyMetric::where('site_id', $site->id)->where('date', $date)->update([
                'queries_count' => count($positions),
                'pages_count' => count($info['pages'] ?? []),
                'position_buckets' => json_encode([
                    'top3' => count(array_filter($positions, fn ($p) => $p <= 3)),
                    'top10' => count(array_filter($positions, fn ($p) => $p > 3 && $p <= 10)),
                    'top20' => count(array_filter($positions, fn ($p) => $p > 10 && $p <= 20)),
                    'rest' => count(array_filter($positions, fn ($p) => $p > 20)),
                ]),
            ]);
        }

        // نگهداری ۱۶ ماه (مثل خود سرچ کنسول)
        DB::table('seo_query_metrics')->where('site_id', $site->id)->where('date', '<', now()->subMonths(16)->toDateString())->delete();

        return ['days' => $days, 'daily_rows' => count($totals), 'detail_rows' => count($rows)];
    }

    protected function flush(array &$buffer): void
    {
        if (! $buffer) {
            return;
        }
        DB::table('seo_query_metrics')->upsert($buffer, ['site_id', 'date', 'query_hash', 'page_hash'], ['query', 'page', 'clicks', 'impressions', 'ctr', 'position']);
        $buffer = [];
    }
}
