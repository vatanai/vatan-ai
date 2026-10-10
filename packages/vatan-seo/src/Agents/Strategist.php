<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Facades\DB;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Services\Reporter;
use Vatan\Seo\Services\RoadmapPlanner;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Support\Prompt;

/**
 * استراتژیست هفتگی — «متخصصی که تصمیم می‌گیرد چه کاری انجام شود».
 * داده‌ی واقعی (KPI، رتبه‌ها، فرصت‌ها، مشکلات فنی، تسک‌های باز) را جمع می‌کند، از مدل استراتژیست
 * ۵ اقدام اولویت‌دار می‌گیرد و آن‌ها را به‌صورت تسک در همین هفته‌ی برنامه ثبت می‌کند.
 * بدون AI (یا بودجه‌ی تمام‌شده) نسخه‌ی قاعده‌محور همان منطق را اجرا می‌کند.
 */
class Strategist
{
    public function __construct(private Ai $ai, private Insights $insights, private Installer $installer, private RoadmapPlanner $planner, private Reporter $reporter) {}

    public function data(Site $site): array
    {
        $w = $this->reporter->totals($site, 7);
        $p = $this->reporter->totals($site, 7, 7);
        $opp = (array) $site->setting('last_opportunities', []);
        if (! $opp) {
            try {
                $opp = $this->insights->opportunities($site);
            } catch (\Throwable) {
                $opp = [];
            }
        }
        $crawl = Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->first();
        return [
            'kpi_7d' => ['clicks' => $w['clicks'], 'impressions' => $w['impressions'], 'ctr' => round($w['ctr'], 4), 'position' => $w['position'] ? round($w['position'], 1) : null],
            'kpi_prev_7d' => ['clicks' => $p['clicks'], 'impressions' => $p['impressions'], 'position' => $p['position'] ? round($p['position'], 1) : null],
            'targets' => $site->keywords()->targets()->get()->map(fn ($k) => ['keyword' => $k->keyword, 'position' => $k->current_position, 'prev' => $k->previous_position, 'target_url' => $k->target_url, 'impressions_28d' => $k->impressions_28d])->take(25)->values()->all(),
            'striking_distance' => array_slice((array) ($opp['striking'] ?? []), 0, 10),
            'low_ctr' => array_slice((array) ($opp['low_ctr'] ?? []), 0, 8),
            'technical_issues' => $crawl ? collect($crawl->issues)->map(fn ($i, $k) => ['issue' => $i['title'], 'level' => $i['level'], 'count' => $i['count'], 'sample' => array_slice($i['urls'], 0, 2)])->values()->all() : 'هنوز خزش نشده',
            'technical_score' => $crawl?->score,
            'needs_action' => Task::where('site_id', $site->id)->where('status', 'needs_action')->orderByDesc('priority')->limit(8)->pluck('title')->all(),
            'content' => ['in_review' => ContentItem::where('site_id', $site->id)->where('status', 'review')->count(), 'published_30d' => ContentItem::where('site_id', $site->id)->where('status', 'published')->where('published_at', '>=', now()->subDays(30))->count()],
        ];
    }

    public function weekly(Site $site): array
    {
        $week = $this->planner->currentWeek($site);
        $theme = $this->planner->theme($site, $week);
        $data = $this->data($site);
        $plan = null;
        if ($this->ai->available($site, 'strategist')) {
            try {
                $plan = $this->ai->json($site, 'strategist', 'weekly-strategy', Prompt::get('weekly-strategy', [
                    'brand' => Prompt::brand($site), 'week' => $week, 'theme' => $theme['theme'], 'goal' => $theme['goal'],
                    'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                ]), 'برنامه‌ی این هفته را بچین.', ['max_tokens' => 2500, 'temperature' => 0.3, 'critical' => true]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $plan = is_array($plan) && ! empty($plan['actions']) ? $plan + ['source' => 'ai'] : $this->ruleBased($data, $theme) + ['source' => 'rules'];

        $weekKey = 'W'.str_pad((string) $week, 2, '0', STR_PAD_LEFT);
        $days = ['sun', 'mon', 'tue', 'wed', 'wed'];
        foreach (array_slice((array) $plan['actions'], 0, 5) as $i => $a) {
            if (empty($a['title'])) {
                continue;
            }
            $impact = max(1, min(5, (int) ($a['impact'] ?? 3)));
            $effort = max(1, min(5, (int) ($a['effort'] ?? 2)));
            $this->installer->task($site, [
                'key' => 'ai.action.'.substr(md5($a['title']), 0, 10),
                'title' => mb_substr((string) $a['title'], 0, 180),
                'why' => trim(($a['why'] ?? '').(! empty($a['url']) ? ' — صفحه: '.urldecode($a['url']) : '').(! empty($a['keyword']) ? ' — کلمه: '.$a['keyword'] : '')),
                'pillar' => ($a['type'] ?? '') === 'technical' ? 'infrastructure' : 'goals',
                'kind' => 'opportunity',
                'category' => ['onpage' => 'onpage', 'content' => 'content', 'technical' => 'crawl', 'links' => 'authority', 'research' => 'research'][$a['type'] ?? ''] ?? 'strategy',
                'impact' => $impact, 'effort' => $effort, 'automation' => 'assisted',
            ], $this->planner->dateFor($site, $week, $days[$i]), null, $weekKey);
        }
        // صفحات پیشنهادی برای تسک «بهینه‌سازی ۳ صفحه»
        if (! empty($plan['pages_to_optimize'])) {
            Task::where('site_id', $site->id)->where('playbook_key', 'wk.onpage')->where('period_key', $weekKey)
                ->update(['result' => json_encode(['urls' => array_slice((array) $plan['pages_to_optimize'], 0, 3)], JSON_UNESCAPED_UNICODE), 'last_message' => 'صفحات پیشنهادی استراتژیست برای این هفته:']);
        }
        $site->putSetting('weekly_plan', ['at' => now()->toDateTimeString(), 'week' => $week, 'focus' => $plan['focus'] ?? '', 'summary' => $plan['summary'] ?? '', 'actions' => $plan['actions'], 'source' => $plan['source']]);
        $site->save();

        return $plan + ['week' => $week, 'theme' => $theme];
    }

    /** نسخه‌ی بدون AI: همان اولویت‌بندی با قاعده */
    protected function ruleBased(array $d, array $theme): array
    {
        $actions = [];
        foreach ((array) ($d['technical_issues'] ?? []) as $i) {
            if (is_array($i) && ($i['level'] ?? '') === 'danger') {
                $actions[] = ['title' => 'رفع: '.$i['issue'], 'why' => Fa::n($i['count']).' صفحه درگیر است.', 'type' => 'technical', 'impact' => 5, 'effort' => 2];
            }
        }
        foreach (array_slice((array) $d['striking_distance'], 0, 2) as $s) {
            $actions[] = ['title' => 'تقویت «'.$s['query'].'» (رتبه '.Fa::n($s['position'], 1).')', 'why' => Fa::n($s['impressions']).' ایمپرشن در ۲۸ روز؛ با لینک داخلی و تکمیل محتوا به صفحه‌ی اول نزدیک می‌شود.', 'type' => 'onpage', 'keyword' => $s['query'], 'impact' => 4, 'effort' => 2];
        }
        if (! empty($d['low_ctr'][0])) {
            $actions[] = ['title' => 'بازنویسی عنوان و توضیحات برای «'.$d['low_ctr'][0]['query'].'»', 'why' => 'CTR کمتر از نصف حد انتظار برای رتبه‌ی فعلی.', 'type' => 'onpage', 'impact' => 4, 'effort' => 1];
        }
        if (($d['content']['in_review'] ?? 0) > 0) {
            $actions[] = ['title' => 'تأیید '.Fa::n($d['content']['in_review']).' پیش‌نویس آماده', 'why' => 'محتوای آماده‌ی منتشرنشده ارزشی تولید نمی‌کند.', 'type' => 'content', 'impact' => 3, 'effort' => 1];
        }
        return ['focus' => $theme['theme'], 'summary' => 'برنامه‌ی قاعده‌محور (هوش مصنوعی در دسترس نبود).', 'actions' => array_slice($actions, 0, 5), 'pages_to_optimize' => []];
    }
}
