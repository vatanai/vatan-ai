<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Scenarios\AuditRunner;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Support\Fa;

class PlanController extends BaseController
{
    public function index(Request $request, Installer $installer)
    {
        $site = $this->site();
        if (! $site->setting('roadmap.synced_at') || \Illuminate\Support\Carbon::parse($site->setting('roadmap.synced_at'))->diffInMinutes(now()) > 60 || \Vatan\Seo\Models\Task::where('site_id', $site->id)->where('kind', 'daily')->doesntExist()) {
            $installer->syncRecurring($site);
        }
        $view = in_array($request->query('view'), ['roadmap', 'today', 'week', 'month', 'all', 'done'], true) ? $request->query('view') : 'roadmap';
        $pillar = in_array($request->query('pillar'), ['infrastructure', 'goals'], true) ? $request->query('pillar') : null;

        $base = Task::where('site_id', $site->id)->with('keyword');
        if ($pillar) {
            $base->where('pillar', $pillar);
        }
        $q = clone $base;
        if (in_array($view, ['week', 'month', 'all'], true)) {
            $q->where('kind', '!=', 'daily'); // روزانه‌ها فقط در «امروز» و تقویم
        }
        match ($view) {
            'today' => $q->open()->where(fn ($w) => $w->where('due_on', '<=', now()->toDateString())->orWhere('status', 'needs_action')),
            'week' => $q->open()->where(fn ($w) => $w->whereNull('due_on')->orWhere('due_on', '<=', now()->addDays(7)->toDateString())),
            'month' => $q->open()->where(fn ($w) => $w->whereNull('due_on')->orWhere('due_on', '<=', now()->addDays(31)->toDateString())),
            'done' => $q->whereIn('status', ['done', 'skipped'])->latest('completed_at'),
            'roadmap' => $q->whereRaw('1 = 0'),
            default => $q->open(),
        };
        $tasks = $q->orderByRaw("CASE status WHEN 'needs_action' THEN 0 WHEN 'waiting_approval' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END")
            ->orderBy('due_on')->orderByDesc('priority')->limit(300)->get();

        $all = Task::where('site_id', $site->id)->get(['id', 'pillar', 'kind', 'category', 'status']);
        $progress = [];
        foreach (['infrastructure', 'goals'] as $p) {
            // پیشرفت ستون‌ها فقط تسک‌های اصلی (نه روزانه/هفتگی تکراری) را می‌شمارد
            $set = $all->where('pillar', $p)->whereNotIn('kind', ['recurring', 'daily', 'weekly'])->where('status', '!=', 'skipped');
            $progress[$p] = ['total' => $set->count(), 'done' => $set->where('status', 'done')->count(), 'action' => $set->where('status', 'needs_action')->count()];
        }
        $categories = $all->where('pillar', 'infrastructure')->where('kind', 'audit')->groupBy('category')->map(fn ($g) => ['total' => $g->count(), 'done' => $g->where('status', 'done')->count()]);

        $planner = app(\Vatan\Seo\Services\RoadmapPlanner::class);
        return $this->view('plan', [
            'roadmap' => $view === 'roadmap' ? $this->roadmap($site, $planner, $pillar) : null,
            'currentWeek' => $planner->currentWeek($site),
            'weeklyPlan' => (array) $site->setting('weekly_plan', []),
            'view' => $view,
            'pillar' => $pillar,
            'tasks' => $tasks,
            'groups' => $this->groupByPeriod($tasks, $view),
            'progress' => $progress,
            'categories' => $categories,
            'scenarios' => Scenario::where('site_id', $site->id)->where('is_enabled', true)->orderBy('next_run_at')->get(['id', 'title', 'last_status', 'next_run_at']),
            'counts' => [
                'roadmap' => (clone $base)->whereNotNull('due_on')->where('kind', '!=', 'daily')->count(),
                'today' => (clone $base)->open()->where(fn ($w) => $w->where('due_on', '<=', now()->toDateString())->orWhere('status', 'needs_action'))->count(),
                'week' => (clone $base)->open()->where(fn ($w) => $w->whereNull('due_on')->orWhere('due_on', '<=', now()->addDays(7)->toDateString()))->count(),
                'month' => (clone $base)->open()->where(fn ($w) => $w->whereNull('due_on')->orWhere('due_on', '<=', now()->addDays(31)->toDateString()))->count(),
                'all' => (clone $base)->open()->count(),
                'done' => (clone $base)->whereIn('status', ['done', 'skipped'])->count(),
            ],
        ]);
    }

    /** داده‌ی تقویم ۹۰ روزه: هفته‌ها با تم، پیشرفت و تسک‌های هر روز */
    protected function roadmap($site, \Vatan\Seo\Services\RoadmapPlanner $planner, ?string $pillar): array
    {
        $horizon = (int) ($site->setting('roadmap.horizon_week') ?: $planner->currentWeek($site) + 12);
        $start = $planner->startSaturday($site);
        $end = $start->copy()->addWeeks($horizon);
        $q = Task::where('site_id', $site->id)->whereNotNull('due_on')->whereBetween('due_on', [$start->toDateString(), $end->toDateString()])->with('keyword');
        if ($pillar) {
            $q->where('pillar', $pillar);
        }
        $tasks = $q->orderBy('due_on')->orderByDesc('priority')->get();
        $weeks = [];
        for ($w = 1; $w <= $horizon; $w++) {
            $ws = $planner->weekStart($site, $w);
            $we = $ws->copy()->addDays(6);
            $set = $tasks->filter(fn ($t) => $t->due_on->between($ws, $we));
            $main = $set->where('kind', '!=', 'daily');
            $daily = $set->where('kind', 'daily');
            $byDay = [];
            foreach (\Vatan\Seo\Services\RoadmapPlanner::DAYS as $code => $offset) {
                $date = $ws->copy()->addDays($offset);
                $items = $main->filter(fn ($t) => $t->due_on->isSameDay($date))->values();
                if ($items->isNotEmpty()) {
                    $byDay[$code] = ['date' => $date, 'tasks' => $items];
                }
            }
            $counted = $main->where('status', '!=', 'skipped');
            $weeks[$w] = [
                'week' => $w,
                'start' => $ws, 'end' => $we,
                'theme' => $planner->theme($site, $w),
                'days' => $byDay,
                'total' => $counted->count(),
                'done' => $counted->where('status', 'done')->count(),
                'action' => $main->where('status', 'needs_action')->count(),
                'daily_total' => $daily->count(),
                'daily_done' => $daily->where('status', 'done')->count(),
            ];
        }
        return $weeks;
    }

    public function rebuild(\Vatan\Seo\Services\RoadmapPlanner $planner)
    {
        $r = $planner->ensure($this->site(), true);
        return back()->with('success', sprintf('برنامه‌ی ۹۰ روزه به‌روز شد: %s تسک تازه، %s زمان‌بندی دوباره.', \Vatan\Seo\Support\Fa::n($r['created']), \Vatan\Seo\Support\Fa::n($r['rescheduled'])));
    }

    protected function groupByPeriod($tasks, string $view): array
    {
        if ($view === 'done') {
            return ['انجام‌شده' => $tasks];
        }
        $groups = ['نیاز به اقدام شما' => collect(), 'عقب‌افتاده' => collect(), 'امروز' => collect(), 'این هفته' => collect(), 'این ماه' => collect(), 'بعداً' => collect()];
        foreach ($tasks as $t) {
            $key = match (true) {
                $t->status === 'needs_action' && $t->automation !== 'auto' => 'نیاز به اقدام شما',
                $t->due_on && $t->due_on->lt(now()->startOfDay()) => 'عقب‌افتاده',
                ! $t->due_on || $t->due_on->isToday() => 'امروز',
                $t->due_on->lte(now()->addDays(7)) => 'این هفته',
                $t->due_on->lte(now()->addDays(31)) => 'این ماه',
                default => 'بعداً',
            };
            $groups[$key]->push($t);
        }
        return array_filter($groups, fn ($g) => $g->isNotEmpty());
    }

    public function update(Request $request, Task $task)
    {
        abort_unless($task->site_id === $this->site()->id, 404);
        $data = $request->validate(['status' => 'required|in:todo,in_progress,done,skipped', 'note' => 'nullable|string|max:1000']);
        $task->status = $data['status'];
        if ($data['status'] === 'done') {
            $task->completed_at = now();
            $task->completed_by = $this->adminRef();
        } elseif ($data['status'] !== 'skipped') {
            $task->completed_at = null;
        }
        if (! empty($data['note'])) {
            $task->last_message = $data['note'];
        }
        $task->save();
        return back()->with('success', '«'.$task->title.'» ← '.Task::STATUSES[$task->status]);
    }

    public function check(Task $task, AuditRunner $runner)
    {
        abort_unless($task->site_id === $this->site()->id, 404);
        @set_time_limit(180);
        $runner->apply($this->site(), $task);
        return back()->with($task->status === 'done' ? 'success' : ($task->status === 'needs_action' ? 'warning' : 'success'), $task->last_message);
    }

    public function checkAll(AuditRunner $runner)
    {
        @set_time_limit(600);
        $scenario = Scenario::where('site_id', $this->site()->id)->where('key', 'audit_runner')->first() ?? new Scenario();
        [, $summary] = $runner->handle($this->site(), $scenario, null, true);
        return back()->with('success', $summary);
    }
}
