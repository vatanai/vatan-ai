<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\DailyMetric;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Support\Prompt;

/** متن گزارش‌های تلگرام (HTML ساده‌ی تلگرام) */
class Reporter
{
    public function __construct(private Ai $ai) {}

    public function panelUrl(string $path = ''): string
    {
        return url(config('seo-engine.host.route_prefix', 'admin/seo').($path ? '/'.ltrim($path, '/') : ''));
    }

    public function totals(Site $site, int $days, int $offset = 0): array
    {
        $q = DailyMetric::where('site_id', $site->id)
            ->whereBetween('date', [now()->subDays($days + $offset + 2)->toDateString(), now()->subDays($offset + 3)->toDateString()]);
        $rows = $q->get();
        $imp = (int) $rows->sum('impressions');
        return [
            'clicks' => (int) $rows->sum('clicks'),
            'impressions' => $imp,
            'ctr' => $imp ? $rows->sum('clicks') / $imp : 0,
            'position' => $imp ? $rows->sum(fn ($r) => $r->position * $r->impressions) / $imp : null,
        ];
    }

    protected function change(float|int|null $now, float|int|null $before, bool $lowerIsBetter = false): string
    {
        if ($now === null || ! $before) {
            return '';
        }
        $pct = ($now - $before) / $before;
        $good = $lowerIsBetter ? $pct < 0 : $pct > 0;
        return ' '.($good ? '🟢' : ($pct == 0 ? '⚪️' : '🔴')).' '.Fa::digits(($pct > 0 ? '+' : '').round($pct * 100)).'٪';
    }

    public function daily(Site $site): string
    {
        $w = $this->totals($site, 7);
        $p = $this->totals($site, 7, 7);
        $tasks = Task::where('site_id', $site->id)->open()->whereNotNull('due_on')->where('due_on', '<=', now()->toDateString())->orderByDesc('priority')->limit(6)->get();
        $done = Task::where('site_id', $site->id)->where('status', 'done')->where('completed_at', '>=', now()->startOfDay())->count();
        $review = ContentItem::where('site_id', $site->id)->where('status', 'review')->count();

        $t = "☀️ <b>خلاصه‌ی سئوی امروز — ".e($site->name)."</b>\n".Fa::date(now())."\n\n";
        $t .= "📈 کلیک ۷ روز: <b>".Fa::n($w['clicks']).'</b>'.$this->change($w['clicks'], $p['clicks'])."\n";
        $t .= "👁 ایمپرشن: <b>".Fa::short($w['impressions']).'</b>'.$this->change($w['impressions'], $p['impressions'])."\n";
        $t .= "🎯 میانگین رتبه: <b>".($w['position'] ? Fa::n($w['position'], 1) : '—').'</b>'.$this->change($w['position'], $p['position'], true)."\n\n";
        $t .= "✅ امروز انجام شد: ".Fa::n($done)." تسک\n";
        if ($review) {
            $t .= "📝 منتظر تأیید شما: ".Fa::n($review)." مقاله (/review)\n";
        }
        if ($tasks->isNotEmpty()) {
            $t .= "\n<b>اولویت‌های امروز:</b>\n";
            foreach ($tasks as $task) {
                $t .= '• '.e($task->title).($task->automation === 'auto' ? ' 🤖' : ' 👤')."\n";
            }
        }
        $t .= "\n💰 هزینه‌ی AI این ماه: ".Fa::usd(Budget::spentThisMonth($site)).' از '.Fa::usd(Budget::cap($site));

        return $t;
    }

    public function keywords(Site $site): string
    {
        $kws = Keyword::where('site_id', $site->id)->targets()->orderByRaw('current_position IS NULL, current_position')->limit(25)->get();
        if ($kws->isEmpty()) {
            return 'هنوز کلمه‌ی هدفی انتخاب نشده است.';
        }
        $t = "🎯 <b>رتبه‌ی کلمات هدف</b>\n\n";
        foreach ($kws as $k) {
            $d = $k->delta();
            $arrow = $d === null ? '' : ($d > 0 ? ' 🟢▲'.Fa::n($d, 1) : ($d < 0 ? ' 🔴▼'.Fa::n(abs($d), 1) : ''));
            $t .= ($k->current_position ? '<b>'.Fa::n($k->current_position, 1).'</b>' : '—').' · '.e($k->keyword).$arrow."\n";
        }
        return $t;
    }

    public function budget(Site $site): string
    {
        return "💰 <b>هزینه‌ی هوش مصنوعی</b>\n\nمصرف این ماه: <b>".Fa::usd(Budget::spentThisMonth($site)).'</b> از سقف '.Fa::usd(Budget::cap($site))
            ."\nپیش‌بینی پایان ماه: ".Fa::usd(Budget::forecast($site))
            ."\nپروفایل: ".e((string) data_get($site->profile(), 'label'))
            ."\nمقاله‌های این ماه: ".Fa::n(Budget::articlesThisMonth($site)).' از '.Fa::n(Budget::limit($site, 'articles_per_month'));
    }

    public function weekly(Site $site, bool $withInsight = true): string
    {
        $w = $this->totals($site, 7);
        $p = $this->totals($site, 7, 7);
        $targets = Keyword::where('site_id', $site->id)->targets()->get();
        $winners = $targets->filter(fn ($k) => ($k->delta() ?? 0) >= 1)->sortByDesc(fn ($k) => $k->delta())->take(5);
        $losers = $targets->filter(fn ($k) => ($k->delta() ?? 0) <= -1)->sortBy(fn ($k) => $k->delta())->take(5);
        $done = Task::where('site_id', $site->id)->where('status', 'done')->where('completed_at', '>=', now()->subDays(7))->count();
        $published = ContentItem::where('site_id', $site->id)->where('status', 'published')->where('published_at', '>=', now()->subDays(7))->count();
        $top10 = $targets->filter(fn ($k) => $k->current_position && $k->current_position <= 10)->count();

        $t = "📊 <b>گزارش هفتگی سئو — ".e($site->name)."</b>\n\n";
        $t .= 'کلیک: <b>'.Fa::n($w['clicks']).'</b>'.$this->change($w['clicks'], $p['clicks'])."\n";
        $t .= 'ایمپرشن: <b>'.Fa::short($w['impressions']).'</b>'.$this->change($w['impressions'], $p['impressions'])."\n";
        $t .= 'CTR: <b>'.Fa::percent($w['ctr']).'</b> · رتبه‌ی میانگین: <b>'.($w['position'] ? Fa::n($w['position'], 1) : '—')."</b>\n";
        $t .= 'کلمات هدف در صفحه‌ی اول: <b>'.Fa::n($top10).' از '.Fa::n($targets->count())."</b>\n\n";
        if ($winners->isNotEmpty()) {
            $t .= "🏆 <b>برنده‌ها</b>\n".$winners->map(fn ($k) => '▲'.Fa::n($k->delta(), 1).' '.e($k->keyword).' ← '.Fa::n($k->current_position, 1))->implode("\n")."\n\n";
        }
        if ($losers->isNotEmpty()) {
            $t .= "⚠️ <b>افت‌ها</b>\n".$losers->map(fn ($k) => '▼'.Fa::n(abs($k->delta()), 1).' '.e($k->keyword).' ← '.Fa::n($k->current_position, 1))->implode("\n")."\n\n";
        }
        $t .= '🤖 کارهای انجام‌شده: '.Fa::n($done).' تسک · '.Fa::n($published)." مقاله منتشر شد\n";
        $t .= '💰 هزینه‌ی AI ماه: '.Fa::usd(Budget::spentThisMonth($site)).' / '.Fa::usd(Budget::cap($site));

        if ($withInsight && $this->ai->available($site, 'fast')) {
            try {
                $insight = $this->ai->text($site, 'fast', 'weekly-insight', Prompt::get('weekly-insight', ['data' => strip_tags($t)]), 'تحلیل کن.', ['max_tokens' => 500, 'critical' => true]);
                $t .= "\n\n🧠 <b>تحلیل</b>\n".e($insight);
            } catch (\Throwable) {
            }
        }
        return $t;
    }

    public function approvalCard(ContentItem $item): array
    {
        $q = (array) $item->quality;
        $text = "📝 <b>مقاله‌ی آماده‌ی تأیید</b>\n\n<b>".e((string) $item->title)."</b>\n"
            .'کلمه: '.e((string) $item->keyword?->keyword)."\n"
            .'طول: '.Fa::n($item->word_count).' کلمه · امتیاز سئو: '.Fa::n($item->seo_score).'/۱۰۰'
            .(isset($q['ai']['quality']) ? ' · کیفیت: '.Fa::n($q['ai']['quality']).'/۱۰۰' : '')."\n"
            .'هزینه‌ی تولید: '.Fa::usd($item->cost_usd)."\n\n"
            .'<i>'.e(mb_substr((string) $item->meta_description, 0, 200)).'</i>';
        $buttons = [
            [['text' => '✅ تأیید و انتشار', 'callback_data' => 'seo:approve:'.$item->id], ['text' => '⛔️ رد', 'callback_data' => 'seo:reject:'.$item->id]],
        ];
        $url = $this->panelUrl('content/'.$item->id);
        if (str_starts_with($url, 'https://')) { // تلگرام فقط لینک https عمومی را می‌پذیرد
            $buttons[] = [['text' => '👁 مشاهده در پنل', 'url' => $url]];
        }
        return [$text, $buttons];
    }
}
