<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Carbon;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Playbook;

/**
 * برنامه‌ریز ۹۰ روزه: پلی‌بوک roadmap.php را به تسک‌های تاریخ‌دار تبدیل می‌کند.
 *  - هفته‌ها از شنبه‌ی هفته‌ی شروع پروژه (started_on) شماره می‌خورند.
 *  - افق غلتان: همیشه تا ۱۳ هفته جلوتر از امروز تسک روزانه/هفتگی ساخته می‌شود.
 *  - تسک‌های یک‌باره‌ی زیرساخت/اهداف تاریخ هفته و روزشان را از assign می‌گیرند.
 *  - کلمات هدف در «موج»های هفتگی پخش می‌شوند (سهمیه‌ی مقاله ÷ ۴ در هفته).
 * همه‌ی عملیات ایدمپوتنت است (dedupe_key).
 */
class RoadmapPlanner
{
    public const DAYS = ['sat' => 0, 'sun' => 1, 'mon' => 2, 'tue' => 3, 'wed' => 4, 'thu' => 5, 'fri' => 6];
    public const DAY_LABELS = ['sat' => 'شنبه', 'sun' => 'یکشنبه', 'mon' => 'دوشنبه', 'tue' => 'سه‌شنبه', 'wed' => 'چهارشنبه', 'thu' => 'پنجشنبه', 'fri' => 'جمعه'];

    public function __construct(private Installer $installer) {}

    public function plan(): array
    {
        return Playbook::roadmap();
    }

    protected function tz(): string
    {
        return (string) config('seo-engine.host.timezone', 'Asia/Tehran');
    }

    /** شنبه‌ی هفته‌ی اول */
    public function startSaturday(Site $site): Carbon
    {
        $start = Carbon::parse($site->started_on ?? $site->created_at ?? now(), $this->tz())->startOfDay();
        while ($start->dayOfWeek !== Carbon::SATURDAY) {
            $start->subDay();
        }
        return $start;
    }

    public function weekStart(Site $site, int $week): Carbon
    {
        return $this->startSaturday($site)->addWeeks($week - 1);
    }

    public function dateFor(Site $site, int $week, string $day): Carbon
    {
        return $this->weekStart($site, $week)->addDays(self::DAYS[$day] ?? 0);
    }

    public function weekOf(Site $site, $date = null): int
    {
        $d = Carbon::parse($date ?? now(), $this->tz())->startOfDay();
        return max(1, intdiv((int) $this->startSaturday($site)->diffInDays($d, false), 7) + 1);
    }

    public function currentWeek(Site $site): int
    {
        return $this->weekOf($site, now($this->tz()));
    }

    public function theme(Site $site, int $week): array
    {
        $plan = $this->plan();
        return $plan['weeks'][$week] ?? $plan['cycle_theme'];
    }

    public function articlesPerWeek(Site $site): int
    {
        return max(1, (int) ceil(Budget::limit($site, 'articles_per_month', 8) / 4));
    }

    /**
     * ساخت/تکمیل برنامه تا افق. $reschedule=true تاریخ تسک‌های باز یک‌باره را دوباره از برنامه تنظیم می‌کند.
     * @return array{created:int, rescheduled:int, expired:int}
     */
    public function ensure(Site $site, bool $reschedule = false): array
    {
        $plan = $this->plan();
        $stats = ['created' => 0, 'rescheduled' => 0, 'expired' => 0];
        $current = $this->currentWeek($site);
        $lastWeek = $current + (int) $plan['horizon_weeks'] - 1;
        $articles = $this->articlesPerWeek($site);

        // ۱) تاریخ تسک‌های یک‌باره‌ی زیرساخت و اهداف
        foreach ($plan['assign'] as $key => [$week, $day]) {
            $task = Task::where('site_id', $site->id)->where('playbook_key', $key)->whereNull('keyword_id')->first();
            if (! $task) {
                continue;
            }
            $date = $this->dateFor($site, $week, $day)->toDateString();
            if ($task->due_on?->toDateString() !== $date && ($reschedule || ! $task->period_key) && ! in_array($task->status, ['done', 'skipped'], true)) {
                $task->update(['due_on' => $date, 'period_key' => 'W'.str_pad((string) $week, 2, '0', STR_PAD_LEFT)]);
                $stats['rescheduled']++;
            }
        }

        // ۲) آیین‌های هفتگی، روزانه، نقطه‌های عطف و چرخه
        for ($w = 1; $w <= $lastWeek; $w++) {
            $wk = 'W'.str_pad((string) $w, 2, '0', STR_PAD_LEFT);
            $isPast = $w < $current;

            foreach ($plan['milestones'] as $m) {
                if ($m['week'] === $w) {
                    $stats['created'] += (int) $this->make($site, $m + ['kind' => 'milestone'], $this->dateFor($site, $w, $m['day']), $wk);
                }
            }
            if ($w > 13) {
                foreach ($plan['cycle'] as $c) {
                    $every = (int) ($c['every'] ?? 4);
                    if (($w - 14 - (int) $c['offset']) % $every === 0 && $w - 14 >= (int) $c['offset']) {
                        $stats['created'] += (int) $this->make($site, $c + ['kind' => 'milestone'], $this->dateFor($site, $w, $c['day']), $wk);
                    }
                }
            }
            if ($isPast) {
                continue; // آیین‌ها و روزانه‌ها برای هفته‌های گذشته ساخته نمی‌شوند
            }
            foreach ($plan['weekly'] as $r) {
                if ($w < (int) $r['from'] || (isset($r['to']) && $w > (int) $r['to'])) {
                    continue;
                }
                if (isset($r['every']) && ($w - (int) $r['from']) % (int) $r['every'] !== 0) {
                    continue;
                }
                $r['title'] = str_replace('{articles}', (string) $articles, $r['title']);
                $stats['created'] += (int) $this->make($site, $r + ['kind' => 'weekly', 'frequency' => 'weekly'], $this->dateFor($site, $w, $r['day']), $wk);
            }
            foreach ($plan['daily'] as $d) {
                if ($w < (int) $d['from']) {
                    continue;
                }
                foreach ($plan['workdays'] as $day) {
                    $date = $this->dateFor($site, $w, $day);
                    if ($date->lt(now($this->tz())->startOfDay())) {
                        continue;
                    }
                    $stats['created'] += (int) $this->make($site, $d + ['kind' => 'daily', 'frequency' => 'daily'], $date, $date->toDateString());
                }
            }
        }

        // ۳) موج‌بندی کلمات هدف
        foreach ($site->keywords()->targets()->orderByDesc('priority')->orderByDesc('ai_score')->orderBy('id')->get() as $kw) {
            $stats['created'] += $this->keywordTasks($site, $kw, $reschedule);
        }

        // ۴) پاک‌سازی: تسک‌های روزانه‌ی گذشته منقضی شوند؛ آیین‌های هفتگی بیش از ۷ روز عقب‌افتاده هم
        $today = now($this->tz())->toDateString();
        $stats['expired'] += Task::where('site_id', $site->id)->where('kind', 'daily')->whereIn('status', ['todo', 'needs_action'])
            ->where('due_on', '<', $today)->update(['status' => 'skipped', 'last_message' => 'روزش گذشت (تسک روزانه).']);
        $stats['expired'] += Task::where('site_id', $site->id)->where('kind', 'weekly')->whereIn('status', ['todo', 'needs_action'])
            ->where('due_on', '<', now($this->tz())->subDays(7)->toDateString())->update(['status' => 'skipped', 'last_message' => 'هفته‌اش گذشت؛ در هفته‌ی جاری تکرار شده است.']);
        // تسک‌های دوره‌ای نسخه‌ی قدیمی (rec.*) با برنامه‌ی ۹۰ روزه جایگزین شده‌اند
        Task::where('site_id', $site->id)->where('playbook_key', 'like', 'rec.%')->whereNotIn('status', ['done', 'skipped'])
            ->update(['status' => 'skipped', 'last_message' => 'جایگزین برنامه‌ی ۹۰ روزه شد.']);

        $site->putSetting('roadmap.synced_at', now()->toDateTimeString());
        $site->putSetting('roadmap.horizon_week', $lastWeek);
        $site->save();

        return $stats;
    }

    protected function make(Site $site, array $item, Carbon $date, string $period, ?Keyword $kw = null): bool
    {
        $item['pillar'] ??= in_array($item['category'] ?? '', ['crawl', 'index', 'performance', 'mobile', 'trust', 'geo', 'schema'], true) ? 'infrastructure' : 'goals';
        return $this->installer->task($site, $item, $date, $kw, $period);
    }

    /** هفته‌ی موج یک کلمه (ثابت می‌ماند تا برنامه به‌هم نریزد) */
    public function waveWeek(Site $site, Keyword $kw): int
    {
        $saved = (int) data_get($kw->meta, 'wave_week', 0);
        if ($saved > 0) {
            return $saved;
        }
        $plan = $this->plan();
        $perWeek = $this->articlesPerWeek($site);
        $week = max((int) $plan['keyword_first_week'], $this->currentWeek($site));
        $taken = Keyword::where('site_id', $site->id)->where('id', '!=', $kw->id)->get()
            ->map(fn ($k) => (int) data_get($k->meta, 'wave_week', 0))->filter()->countBy();
        while (($taken[$week] ?? 0) >= $perWeek) {
            $week++;
        }
        $kw->update(['meta' => array_merge((array) $kw->meta, ['wave_week' => $week])]);
        return $week;
    }

    public function keywordTasks(Site $site, Keyword $kw, bool $reschedule = false): int
    {
        $plan = $this->plan();
        $week = $this->waveWeek($site, $kw);
        $n = 0;
        foreach (Playbook::goals()['per_keyword'] as $item) {
            $day = $plan['keyword_days'][$item['key']] ?? 'sat';
            $date = $this->dateFor($site, $week, $day);
            $item['title'] = str_replace('{keyword}', $kw->keyword, $item['title']);
            $created = $this->installer->task($site, $item + ['pillar' => 'goals', 'kind' => 'keyword'], $date, $kw);
            $n += (int) $created;
            if (! $created) {
                Task::where('site_id', $site->id)->where('keyword_id', $kw->id)->where('playbook_key', $item['key'])
                    ->whereNotIn('status', ['done', 'skipped'])
                    ->where(fn ($q) => $reschedule ? $q : $q->whereNull('due_on'))
                    ->update(['due_on' => $date->toDateString()]);
            }
        }
        return $n;
    }
}
