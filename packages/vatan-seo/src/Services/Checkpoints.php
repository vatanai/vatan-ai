<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Checks\CheckResult;
use Vatan\Seo\Models\Audit;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\DailyMetric;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Telegram\SeoBot;

/** خط پایه و نقطه‌های بازبینی روز ۳۰/۶۰/۹۰ (مقایسه‌ی عددی، بدون هزینه‌ی AI) */
class Checkpoints
{
    public function snapshot(Site $site): array
    {
        $rows = DailyMetric::where('site_id', $site->id)->where('date', '>=', now()->subDays(31)->toDateString())->get();
        $imp = (int) $rows->sum('impressions');
        $targets = $site->keywords()->targets()->get();
        $crawl = Audit::where('site_id', $site->id)->where('type', 'crawl')->latest()->first();
        $speed = Audit::where('site_id', $site->id)->where('type', 'pagespeed')->latest()->first();
        return [
            'at' => now()->toDateString(),
            'clicks_28d' => (int) $rows->sum('clicks'),
            'impressions_28d' => $imp,
            'ctr' => $imp ? round($rows->sum('clicks') / $imp, 4) : null,
            'position' => $imp ? round($rows->sum(fn ($r) => $r->position * $r->impressions) / $imp, 1) : null,
            'queries' => (int) ($rows->sortByDesc('date')->first()?->queries_count ?? 0),
            'targets' => $targets->count(),
            'targets_top10' => $targets->filter(fn ($k) => $k->current_position && $k->current_position <= 10)->count(),
            'targets_top20' => $targets->filter(fn ($k) => $k->current_position && $k->current_position <= 20)->count(),
            'crawl_score' => $crawl?->score,
            'speed_mobile' => $speed?->score,
            'published' => ContentItem::where('site_id', $site->id)->where('status', 'published')->count(),
            'tasks_done' => Task::where('site_id', $site->id)->where('status', 'done')->count(),
            'ai_visibility' => data_get($site->setting('geo_probe.last', []), 'mentioned'),
        ];
    }

    public function baseline(Site $site): CheckResult
    {
        if ($b = $site->setting('baseline')) {
            return CheckResult::ok('خط پایه در '.Fa::date($b['at']).' ثبت شده است.', $b);
        }
        if (! DailyMetric::where('site_id', $site->id)->exists()) {
            return CheckResult::wait('بعد از اتصال سرچ کنسول و اولین همگام‌سازی، خط پایه خودکار ثبت می‌شود.');
        }
        $snap = $this->snapshot($site);
        $site->putSetting('baseline', $snap);
        $site->save();
        return CheckResult::ok(sprintf('خط پایه ثبت شد: %s کلیک و %s ایمپرشن در ۲۸ روز، میانگین رتبه %s.', Fa::n($snap['clicks_28d']), Fa::n($snap['impressions_28d']), $snap['position'] ? Fa::n($snap['position'], 1) : '—'), $snap);
    }

    public function checkpoint(Site $site, int $day, Task $task): CheckResult
    {
        $base = $site->setting('baseline');
        if (! $base) {
            return CheckResult::wait('اول خط پایه باید ثبت شود (نیاز به اتصال سرچ کنسول).');
        }
        $now = $this->snapshot($site);
        $pct = fn ($a, $b) => $b ? (($a - $b) / $b) : null;
        $rows = [
            'کلیک ۲۸ روز' => [$base['clicks_28d'], $now['clicks_28d'], $pct($now['clicks_28d'], $base['clicks_28d'])],
            'ایمپرشن ۲۸ روز' => [$base['impressions_28d'], $now['impressions_28d'], $pct($now['impressions_28d'], $base['impressions_28d'])],
            'میانگین رتبه' => [$base['position'], $now['position'], null],
            'کلمات هدف در صفحه‌ی اول' => [$base['targets_top10'], $now['targets_top10'], null],
            'امتیاز فنی' => [$base['crawl_score'], $now['crawl_score'], null],
            'سرعت موبایل' => [$base['speed_mobile'], $now['speed_mobile'], null],
            'مقاله‌ی منتشرشده' => [$base['published'], $now['published'], null],
        ];
        $lines = [];
        foreach ($rows as $label => [$b, $n, $p]) {
            $lines[] = $label.': '.($b === null ? '—' : Fa::n($b, is_float($b) ? 1 : 0)).' ← '.($n === null ? '—' : Fa::n($n, is_float($n) ? 1 : 0)).($p !== null ? ' ('.($p >= 0 ? '+' : '−').Fa::percent(abs($p), 0).')' : '');
        }
        $title = $day >= 90 ? 'بازبینی روز ۹۰' : ($day >= 60 ? 'بازبینی روز ۶۰' : 'بازبینی روز ۳۰');
        $site->putSetting('checkpoints.'.$day.'.'.now()->format('Ymd'), $now);
        $site->save();
        $bot = app(SeoBot::class);
        if ($bot->configured()) {
            $bot->broadcast('📍 <b>'.e($title).' — '.e($site->name)."</b>\n\n".e(implode("\n", $lines)));
        }
        return CheckResult::ok($title.' ثبت شد. '.implode(' · ', array_slice($lines, 0, 3)), ['baseline' => $base, 'now' => $now, 'lines' => $lines]);
    }
}
