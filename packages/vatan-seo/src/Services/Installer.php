<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Carbon;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Playbook;

/**
 * ساخت و همگام‌سازی تسک‌ها و سناریوها از روی پلی‌بوک.
 * کاملاً ایدمپوتنت: اجرای دوباره چیزی را تکرار یا بازنویسی نمی‌کند (فقط موارد جدید پلی‌بوک اضافه می‌شوند).
 */
class Installer
{
    public function install(Site $site): array
    {
        $created = ['tasks' => 0, 'scenarios' => 0];
        $start = $site->started_on ? Carbon::parse($site->started_on) : now();

        foreach (Playbook::infrastructure() as $item) {
            $created['tasks'] += (int) $this->task($site, $item + ['pillar' => 'infrastructure', 'kind' => 'audit'], $start->copy()->addDays((int) ($item['due_day'] ?? 7)));
        }
        foreach (Playbook::goals()['setup'] as $item) {
            $created['tasks'] += (int) $this->task($site, $item + ['pillar' => 'goals', 'kind' => 'audit'], $start->copy()->addDays((int) ($item['due_day'] ?? 7)));
        }
        foreach (Playbook::scenarios() as $s) {
            $scenario = Scenario::firstOrNew(['site_id' => $site->id, 'key' => $s['key']]);
            if (! $scenario->exists) {
                $scenario->fill([
                    'title' => $s['title'],
                    'why' => $s['why'] ?? null,
                    'pillar' => $s['pillar'] ?? 'goals',
                    'handler' => $s['handler'],
                    'frequency' => $s['frequency'],
                    'at' => $s['at'] ?? '06:00',
                    'weekday' => $s['weekday'] ?? null,
                    'day' => $s['day'] ?? null,
                    'config' => array_merge($s['config'] ?? [], isset($s['profile_key']) ? ['profile_key' => $s['profile_key']] : []),
                    'is_enabled' => true,
                ]);
                $scenario->next_run_at = app(Scheduler::class)->nextRun($scenario);
                $scenario->save();
                $created['scenarios']++;
            } else {
                // متن و توضیح از پلی‌بوک به‌روز می‌شود؛ زمان‌بندی و تنظیمات کاربر دست‌نخورده می‌ماند
                $scenario->update(['title' => $s['title'], 'why' => $s['why'] ?? null, 'handler' => $s['handler']]);
            }
        }
        $plan = $this->syncRecurring($site);
        $created['tasks'] += $plan['created'];

        return $created;
    }

    /** برنامه‌ی ۹۰ روزه (روزانه، هفتگی، نقطه‌های عطف، موج کلمات) — RoadmapPlanner */
    public function syncRecurring(Site $site, bool $reschedule = false): array
    {
        return app(RoadmapPlanner::class)->ensure($site, $reschedule);
    }

    public function keywordTasks(Site $site, Keyword $kw): int
    {
        return app(RoadmapPlanner::class)->keywordTasks($site, $kw);
    }

    /** @return bool true اگر تسک تازه ساخته شد */
    public function task(Site $site, array $item, ?Carbon $due = null, ?Keyword $kw = null, ?string $period = null): bool
    {
        $dedupe = $item['key'].'|'.($kw?->id ?? '').'|'.($period ?? '');
        $task = Task::firstOrNew(['site_id' => $site->id, 'dedupe_key' => $dedupe]);
        if ($task->exists) {
            if ($task->status !== 'done') {
                $task->update(['title' => $item['title'], 'why' => $item['why'] ?? null]);
            }
            return false;
        }
        $automation = $item['automation'] ?? 'auto';
        $task->fill([
            'keyword_id' => $kw?->id,
            'playbook_key' => $item['key'],
            'pillar' => $item['pillar'],
            'kind' => $item['kind'] ?? 'audit',
            'category' => $item['category'] ?? null,
            'title' => $item['title'],
            'why' => $item['why'] ?? null,
            'frequency' => $item['frequency'] ?? 'once',
            'automation' => $automation,
            'check' => $item['check'] ?? null,
            'impact' => (int) ($item['impact'] ?? 3),
            'effort' => (int) ($item['effort'] ?? 2),
            'priority' => Task::priorityFor((int) ($item['impact'] ?? 3), (int) ($item['effort'] ?? 2), $automation),
            'status' => 'todo',
            'due_on' => $due?->toDateString(),
            'period_key' => $period,
        ]);
        $task->save();

        return true;
    }
}
