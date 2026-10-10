<?php

namespace Vatan\Seo\Http\Controllers;

use Vatan\Seo\Data\Google\ServiceAccount;
use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\DailyMetric;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\KeywordRank;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class OverviewController extends BaseController
{
    public function __invoke()
    {
        $site = $this->site();
        $site->markSeen();
        $range = (int) request('range', 28);
        $range = in_array($range, [7, 28, 90], true) ? $range : 28;

        $metrics = DailyMetric::where('site_id', $site->id)->where('date', '>=', now()->subDays($range * 2 + 3)->toDateString())->orderBy('date')->get();
        $cut = now()->subDays($range + 2)->toDateString();
        $cur = $metrics->filter(fn ($m) => $m->date->toDateString() > $cut);
        $prev = $metrics->filter(fn ($m) => $m->date->toDateString() <= $cut);
        $sum = function ($set) {
            $imp = (int) $set->sum('impressions');
            return [
                'clicks' => (int) $set->sum('clicks'),
                'impressions' => $imp,
                'ctr' => $imp ? $set->sum('clicks') / $imp : null,
                'position' => $imp ? $set->sum(fn ($r) => $r->position * $r->impressions) / $imp : null,
            ];
        };
        $kpi = ['cur' => $sum($cur), 'prev' => $sum($prev)];

        $targets = Keyword::where('site_id', $site->id)->targets()->get();
        $buckets = ['top3' => 0, 'top10' => 0, 'top20' => 0, 'rest' => 0, 'none' => 0];
        foreach ($targets as $k) {
            $buckets[$k->bucket()]++;
        }

        // روند رتبه‌ی ۸ کلمه‌ی هدف برتر
        $topTargets = $targets->sortBy(fn ($k) => $k->current_position ?? 999)->take(8);
        $ranks = KeywordRank::whereIn('keyword_id', $topTargets->pluck('id'))->where('date', '>=', now()->subDays(60)->toDateString())->orderBy('date')->get()->groupBy('keyword_id');

        $tasks = Task::where('site_id', $site->id)->get();
        $main = $tasks->whereNotIn('kind', ['recurring', 'daily', 'weekly'])->where('status', '!=', 'skipped');
        $pillar = fn ($p) => [
            'total' => $main->where('pillar', $p)->count(),
            'done' => $main->where('pillar', $p)->where('status', 'done')->count(),
        ];

        $chart = [
            'labels' => $cur->map(fn ($m) => Fa::dayLabel($m->date))->values(),
            'clicks' => $cur->pluck('clicks')->values(),
            'impressions' => $cur->pluck('impressions')->values(),
            'position' => $cur->pluck('position')->values(),
        ];
        $rankChart = [
            'series' => $topTargets->map(fn ($k) => [
                'label' => $k->keyword,
                'points' => ($ranks[$k->id] ?? collect())->map(fn ($r) => ['x' => $r->date->toDateString(), 'label' => Fa::dayLabel($r->date), 'y' => $r->position])->values(),
            ])->values(),
        ];

        return $this->view('overview', [
            'range' => $range,
            'kpi' => $kpi,
            'spark' => ['clicks' => $chart['clicks'], 'impressions' => $chart['impressions'], 'position' => $chart['position']],
            'chart' => $chart,
            'rankChart' => $rankChart,
            'targets' => $targets,
            'topTargets' => $topTargets,
            'buckets' => $buckets,
            'infra' => $pillar('infrastructure'),
            'goals' => $pillar('goals'),
            'today' => Task::where('site_id', $site->id)->open()->where('kind', '!=', 'daily')->where(fn ($q) => $q->whereNull('due_on')->orWhere('due_on', '<=', now()->addDays(2)->toDateString()))->orderByRaw("status = 'needs_action' desc")->orderByDesc('priority')->limit(7)->get(),
            'alerts' => Alert::where('site_id', $site->id)->latest()->limit(5)->get(),
            'runs' => Run::where('site_id', $site->id)->latest()->limit(6)->get(),
            'review' => ContentItem::where('site_id', $site->id)->where('status', 'review')->count(),
            'crawl' => Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->first(),
            'speed' => Audit::where('site_id', $site->id)->where('type', 'pagespeed')->latest()->first(),
            'budget' => ['spent' => Budget::spentThisMonth($site), 'cap' => Budget::cap($site), 'ratio' => Budget::usageRatio($site), 'forecast' => Budget::forecast($site)],
            'gscReady' => ServiceAccount::configured(),
            'week' => (function () use ($site) {
                $planner = app(\Vatan\Seo\Services\RoadmapPlanner::class);
                $w = $planner->currentWeek($site);
                $ws = $planner->weekStart($site, $w);
                $set = Task::where('site_id', $site->id)->where('kind', '!=', 'daily')->where('status', '!=', 'skipped')->whereBetween('due_on', [$ws->toDateString(), $ws->copy()->addDays(6)->toDateString()])->get();
                return ['n' => $w, 'theme' => $planner->theme($site, $w), 'start' => $ws, 'total' => $set->count(), 'done' => $set->where('status', 'done')->count(), 'plan' => (array) $site->setting('weekly_plan', [])];
            })(),
            'lastDate' => $metrics->last()?->date,
        ]);
    }
}
