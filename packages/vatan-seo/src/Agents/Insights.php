<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Facades\DB;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Fa;

/**
 * تحلیل‌های داده‌محور بدون هزینه‌ی AI روی داده‌ی سرچ کنسول:
 *  - فرصت‌ها (Striking distance و CTR پایین)
 *  - همنوع‌خواری (Cannibalization)
 *  - افت محتوا (Content decay)
 */
class Insights
{
    /** CTR مورد انتظار بر اساس رتبه (میانگین صنعت، تقریبی) */
    private const EXPECTED_CTR = [1 => 0.28, 2 => 0.15, 3 => 0.10, 4 => 0.07, 5 => 0.05, 6 => 0.04, 7 => 0.03, 8 => 0.025, 9 => 0.02, 10 => 0.018];

    protected function queryStats(Site $site, int $days = 28, int $offset = 0)
    {
        return DB::table('seo_query_metrics')
            ->selectRaw('query_hash, MIN(query) as query, SUM(clicks) as clicks, SUM(impressions) as impressions, SUM(position*impressions)/NULLIF(SUM(impressions),0) as position')
            ->where('site_id', $site->id)
            ->whereBetween('date', [now()->subDays($days + $offset)->toDateString(), now()->subDays($offset + 1)->toDateString()])
            ->groupBy('query_hash');
    }

    public function opportunities(Site $site): array
    {
        $rows = $this->queryStats($site)->havingRaw('SUM(impressions) >= 20')->orderByDesc('impressions')->limit(500)->get();
        $striking = [];
        $lowCtr = [];
        foreach ($rows as $r) {
            $pos = (float) $r->position;
            $ctr = $r->impressions ? $r->clicks / $r->impressions : 0;
            if ($pos >= 4 && $pos <= 20) {
                $striking[] = ['query' => $r->query, 'position' => round($pos, 1), 'impressions' => (int) $r->impressions, 'clicks' => (int) $r->clicks, 'potential' => (int) round($r->impressions * (self::EXPECTED_CTR[3] - $ctr))];
            }
            $expected = self::EXPECTED_CTR[(int) max(1, min(10, round($pos)))] ?? null;
            if ($pos <= 10 && $expected && $ctr < $expected * 0.5 && $r->impressions >= 50) {
                $lowCtr[] = ['query' => $r->query, 'position' => round($pos, 1), 'impressions' => (int) $r->impressions, 'ctr' => round($ctr, 4), 'expected' => $expected];
            }
        }
        usort($striking, fn ($a, $b) => $b['potential'] <=> $a['potential']);
        usort($lowCtr, fn ($a, $b) => $b['impressions'] <=> $a['impressions']);
        $striking = array_slice($striking, 0, 25);
        $lowCtr = array_slice($lowCtr, 0, 15);

        // کلمات نزدیک صفحه‌ی اول ← پیشنهاد (اگر هنوز در فهرست نیست)
        foreach (array_slice($striking, 0, 15) as $s) {
            $kw = Keyword::firstOrNew(['site_id' => $site->id, 'normalized' => Fa::normalizeKeyword($s['query'])]);
            if (! $kw->exists) {
                $kw->fill(['keyword' => $s['query'], 'status' => 'candidate', 'source' => 'gsc', 'ai_score' => min(95, 60 + (int) round(log10($s['impressions'] + 1) * 10)), 'current_position' => $s['position'], 'impressions_28d' => $s['impressions'], 'clicks_28d' => $s['clicks'], 'meta' => ['reason' => 'نزدیک صفحه‌ی اول؛ با بهینه‌سازی کوچک می‌تواند کلیک بگیرد.']]);
                $kw->save();
            }
        }

        // تسک‌های فرصت هفته (یک تسک جمعی برای هر نوع)
        $week = now()->format('o-\WW');
        if ($lowCtr) {
            app(\Vatan\Seo\Services\Installer::class)->task($site, [
                'key' => 'opp.low_ctr', 'title' => 'بهبود عنوان و توضیحات برای '.Fa::n(count($lowCtr)).' کوئری با CTR پایین', 'pillar' => 'goals', 'kind' => 'opportunity',
                'category' => 'onpage', 'impact' => 4, 'effort' => 1, 'automation' => 'assisted', 'why' => 'این کوئری‌ها رتبه‌ی خوبی دارند ولی کمتر از نصف کلیک مورد انتظار را می‌گیرند؛ عنوان و توضیحات جذاب‌تر مستقیم کلیک را زیاد می‌کند.',
            ], now()->addDays(5), null, $week);
            Task::where('site_id', $site->id)->where('dedupe_key', 'opp.low_ctr||'.$week)->update(['result' => json_encode(['items' => $lowCtr], JSON_UNESCAPED_UNICODE)]);
        }
        return ['striking' => $striking, 'low_ctr' => $lowCtr];
    }

    public function cannibalization(Site $site): array
    {
        $rows = DB::table('seo_query_metrics')
            ->selectRaw('query_hash, MIN(query) as query, page, SUM(impressions) as imp, SUM(clicks) as clk, SUM(position*impressions)/NULLIF(SUM(impressions),0) as pos')
            ->where('site_id', $site->id)->where('date', '>=', now()->subDays(28)->toDateString())
            ->groupBy('query_hash', 'page')->get()->groupBy('query_hash');
        $out = [];
        foreach ($rows as $pages) {
            $total = $pages->sum('imp');
            if ($total < 30) continue;
            $significant = $pages->filter(fn ($p) => $p->imp / $total >= 0.15);
            if ($significant->count() >= 2) {
                $out[] = [
                    'query' => $pages->first()->query,
                    'impressions' => (int) $total,
                    'pages' => $significant->sortByDesc('imp')->map(fn ($p) => ['url' => $p->page, 'share' => round($p->imp / $total, 2), 'position' => round((float) $p->pos, 1), 'clicks' => (int) $p->clk])->values()->all(),
                ];
            }
        }
        usort($out, fn ($a, $b) => $b['impressions'] <=> $a['impressions']);
        return array_slice($out, 0, 30);
    }

    public function decay(Site $site): array
    {
        $now = DB::table('seo_query_metrics')->selectRaw('page_hash, MIN(page) as page, SUM(clicks) as c')->where('site_id', $site->id)
            ->whereBetween('date', [now()->subDays(29)->toDateString(), now()->subDays(2)->toDateString()])->groupBy('page_hash')->pluck('c', 'page_hash');
        $prev = DB::table('seo_query_metrics')->selectRaw('page_hash, MIN(page) as page, SUM(clicks) as c')->where('site_id', $site->id)
            ->whereBetween('date', [now()->subDays(57)->toDateString(), now()->subDays(30)->toDateString()])->groupBy('page_hash')->get()->keyBy('page_hash');
        $out = [];
        foreach ($prev as $hash => $row) {
            $before = (int) $row->c;
            $after = (int) ($now[$hash] ?? 0);
            if ($before >= 10 && $after < $before * 0.7) {
                $out[] = ['page' => $row->page, 'before' => $before, 'after' => $after, 'drop' => round(1 - $after / max(1, $before), 2)];
            }
        }
        usort($out, fn ($a, $b) => ($b['before'] - $b['after']) <=> ($a['before'] - $a['after']));
        $out = array_slice($out, 0, 15);
        foreach (array_slice($out, 0, 5) as $d) {
            ContentItem::firstOrCreate(
                ['site_id' => $site->id, 'type' => 'refresh', 'target_url' => $d['page'], 'status' => 'idea'],
                ['title' => 'به‌روزرسانی: '.urldecode((string) parse_url($d['page'], PHP_URL_PATH)), 'brief' => ['reason' => 'افت '.Fa::percent($d['drop']).' کلیک نسبت به ۲۸ روز قبل', 'data' => $d]]
            );
        }
        return $out;
    }
}
