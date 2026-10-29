<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\Cache;
use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Run;
use Vatan\Seo\Models\Scenario;

/** اجرای یک سناریو با قفل (بدون هم‌پوشانی)، ثبت اجرا و محاسبه‌ی نوبت بعد */
class ScenarioRunner
{
    public const AGENTS = [
        'GscSync' => 'monitor', 'RankUpdate' => 'monitor', 'HealthCheck' => 'auditor', 'AuditRunner' => 'auditor',
        'CrawlAudit' => 'auditor', 'PageSpeedAudit' => 'auditor', 'DailyPlan' => 'strategist', 'Opportunities' => 'strategist',
        'Cannibalization' => 'strategist', 'ContentDecay' => 'strategist', 'Discovery' => 'researcher', 'ContentPipeline' => 'writer',
        'WeeklyReport' => 'reporter', 'StrategistReview' => 'strategist',
    ];

    public function __construct(private RunRecorder $recorder, private Scheduler $scheduler, private Notifier $notifier) {}

    public function run(Scenario $scenario, string $trigger = 'schedule'): ?Run
    {
        $lock = Cache::lock('seo-engine:scenario:'.$scenario->id, 1800);
        if (! $lock->get()) {
            return null;
        }
        try {
            $site = $scenario->site;
            $class = 'Vatan\\Seo\\Scenarios\\'.$scenario->handler;
            $action = \Illuminate\Support\Str::snake($scenario->key);
            $run = $this->recorder->record($site, self::AGENTS[$scenario->handler] ?? 'system', $action, function () use ($class, $site, $scenario) {
                if (! class_exists($class)) {
                    return ['failed', 'هندلر سناریو پیدا نشد: '.$scenario->handler];
                }
                return app($class)->handle($site, $scenario);
            }, ['scenario_id' => $scenario->id, 'trigger' => $trigger]);

            $wasFailing = $scenario->last_status === 'failed' || $scenario->fail_count > 0;
            $scenario->forceFill([
                'last_run_at' => now(),
                'last_status' => $run->status,
                'last_summary' => $run->summary,
                'run_count' => $scenario->run_count + 1,
                'fail_count' => $run->status === 'failed' ? $scenario->fail_count + 1 : 0,
                'next_run_at' => $this->scheduler->nextRun($scenario),
            ])->save();

            if ($run->status === 'failed') {
                $recent = Alert::where('site_id', $site->id)->where('type', 'scenario_failure')
                    ->where('title', 'شکست سناریو: '.$scenario->title)->where('created_at', '>=', now()->subHours(6))->exists();
                if (! $recent) {
                    $this->notifier->alert($site, 'danger', 'scenario_failure', 'شکست سناریو: '.$scenario->title,
                        mb_substr((string) $run->summary, 0, 900)."\n\nسامانه اجرای بعدی را طبق برنامه تلاش می‌کند؛ اگر نیاز به اقدام فوری باشد، تنظیمات اتصال را بررسی کنید.",
                        ['scenario_id' => $scenario->id, 'scenario_key' => $scenario->key, 'run_id' => $run->id, 'fail_count' => $scenario->fail_count]);
                }
            } elseif ($wasFailing) {
                $this->notifier->alert($site, 'success', 'scenario_recovered', 'سناریو دوباره فعال شد: '.$scenario->title,
                    mb_substr((string) $run->summary, 0, 700),
                    ['scenario_id' => $scenario->id, 'scenario_key' => $scenario->key, 'run_id' => $run->id]);
            }

            return $run;
        } finally {
            $lock->release();
        }
    }

    /** اجرای همه‌ی سناریوهای سررسیده (فراخوانی از seo:tick) */
    public function runDue(int $max = 3): array
    {
        $out = [];
        $due = Scenario::where('is_enabled', true)->where(fn ($q) => $q->whereNull('next_run_at')->orWhere('next_run_at', '<=', now()))
            ->whereHas('site', fn ($q) => $q->where('is_active', true))->orderBy('next_run_at')->limit($max)->get();
        foreach ($due as $scenario) {
            if ($scenario->next_run_at === null) {
                $scenario->update(['next_run_at' => $this->scheduler->nextRun($scenario)]);
                continue;
            }
            $run = $this->run($scenario);
            $out[] = $scenario->key.': '.($run?->status ?? 'locked');
        }
        return $out;
    }
}
