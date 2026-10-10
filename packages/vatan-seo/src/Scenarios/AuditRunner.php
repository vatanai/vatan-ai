<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Checks\CheckRegistry;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Support\Fa;

/** اجرای بررسی خودکار تسک‌های باز؛ قبولی = تیک خودکار، رد = «نیاز به اقدام» با جزئیات */
class AuditRunner implements Handler
{
    public function __construct(private CheckRegistry $checks) {}

    public function handle(Site $site, Scenario $scenario, ?string $only = null, bool $force = false): array
    {
        $q = Task::where('site_id', $site->id)->open()->whereIn('automation', ['auto', 'assisted'])->whereNotNull('check');
        if ($only) {
            $q->where('check', 'like', $only.'%');
        }
        if (! $force) {
            $q->where(fn ($w) => $w->whereNull('last_checked_at')->orWhere('last_checked_at', '<', now()->subHours(20)));
        }
        // تسک‌های روزانه/هفتگی/عطف فقط از روز سررسیدشان بررسی می‌شوند
        $q->where(fn ($w) => $w->whereNotIn('kind', ['daily', 'weekly', 'milestone'])->orWhere('due_on', '<=', now()->toDateString()));
        $tasks = $q->orderBy('due_on')->orderByDesc('priority')->limit(60)->get();
        $stats = ['done' => 0, 'needs_action' => 0, 'waiting' => 0];
        foreach ($tasks as $task) {
            $this->apply($site, $task);
            $stats[$task->status === 'done' ? 'done' : ($task->status === 'needs_action' ? 'needs_action' : 'waiting')]++;
        }
        return ['success', sprintf('%s تسک بررسی شد: %s قبول و تیک خورد، %s نیاز به اقدام، %s منتظر داده.', Fa::n($tasks->count()), Fa::n($stats['done']), Fa::n($stats['needs_action']), Fa::n($stats['waiting'])), $stats];
    }

    public function apply(Site $site, Task $task): Task
    {
        $r = $this->checks->run($site, $task);
        $task->fill([
            'last_checked_at' => now(),
            'attempts' => $task->attempts + 1,
            'last_message' => $r->message,
            'result' => $r->data ?: $task->result,
        ]);
        if ($r->pass === true) {
            $task->fill(['status' => 'done', 'completed_at' => now(), 'completed_by' => 'agent']);
        } elseif ($r->pass === false) {
            $task->status = 'needs_action';
        } elseif ($task->status === 'needs_action') {
            $task->status = 'todo';
        }
        $task->save();
        return $task;
    }
}
