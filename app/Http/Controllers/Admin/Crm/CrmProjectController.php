<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmProject;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CrmActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmProjectController extends Controller
{
    public function index(): JsonResponse
    {
        $projects = CrmProject::with(['manager', 'members'])
            ->withCount('tasks')
            ->orderByRaw("CASE WHEN archived = true THEN 1 ELSE 0 END")
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatProject($p));

        return response()->json($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'emoji'       => 'nullable|string|max:10',
            'status'      => 'required|in:planning,waiting,inprogress,done,stopped',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|string|max:20',
            'start_date'  => 'nullable|string|max:20',
            'end_date'    => 'nullable|string|max:20',
            'manager_id'  => 'nullable|uuid',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'uuid',
        ]);

        $memberIds = $data['member_ids'] ?? [];
        unset($data['member_ids']);
        $data['emoji'] = $data['emoji'] ?: '📁';

        $project = CrmProject::create($data);
        $project->members()->sync($memberIds);

        CrmActivityLog::log('project', 'ایجاد پروژه: ' . $project->name, null, null, $project->id);

        return response()->json($this->formatProject($project->load(['manager', 'members'])), 201);
    }

    public function show(string $id): JsonResponse
    {
        $project = CrmProject::with([
            'manager',
            'members',
            'tasks.microtasks',
            'tasks.assignee',
            'tasks.comments',
            'activityLogs' => fn($q) => $q->orderByDesc('created_at')->limit(20),
        ])->findOrFail($id);

        return response()->json($this->formatProject($project));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $project = CrmProject::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|string|max:150',
            'emoji'       => 'nullable|string|max:10',
            'status'      => 'sometimes|in:planning,waiting,inprogress,done,stopped',
            'description' => 'nullable|string',
            'deadline'    => 'nullable|string|max:20',
            'start_date'  => 'nullable|string|max:20',
            'end_date'    => 'nullable|string|max:20',
            'manager_id'  => 'nullable|uuid',
            'archived'    => 'boolean',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'uuid',
        ]);

        if (array_key_exists('member_ids', $data)) {
            $project->members()->sync($data['member_ids'] ?? []);
            unset($data['member_ids']);
        }

        $project->update($data);

        CrmActivityLog::log('project', 'ویرایش پروژه: ' . $project->name, null, null, $project->id);

        return response()->json($this->formatProject($project->load(['manager', 'members'])));
    }

    public function destroy(string $id): JsonResponse
    {
        $project = CrmProject::findOrFail($id);
        $name = $project->name;

        $project->tasks()->each(fn($t) => $t->microtasks()->delete());
        $project->tasks()->delete();
        $project->delete();

        CrmActivityLog::log('project', 'حذف پروژه: ' . $name);

        return response()->json(['message' => 'پروژه حذف شد']);
    }

    // ─── Private ─────────────────────────────────────────────

    private function formatProject(CrmProject $p): array
    {
        $tasks = $p->relationLoaded('tasks') ? $p->tasks : $p->tasks()->get();

        // محاسبه progress
        $progress = 0;
        if ($tasks->count()) {
            $total = $tasks->sum(function ($task) {
                $micros = $task->relationLoaded('microtasks') ? $task->microtasks : $task->microtasks()->get();
                if ($micros->isEmpty()) return $task->done ? 100 : 0;
                return $micros->count()
                    ? (int) round($micros->where('done', true)->count() / $micros->count() * 100)
                    : ($task->done ? 100 : 0);
            });
            $progress = (int) round($total / $tasks->count());
        }

        return [
            'id'          => $p->id,
            'name'        => $p->name,
            'emoji'       => $p->emoji,
            'status'      => $p->status,
            'description' => $p->description,
            'deadline'    => $p->deadline,
            'start_date'  => $p->start_date,
            'end_date'    => $p->end_date,
            'archived'    => $p->archived,
            'created_at'  => $p->created_at?->timestamp * 1000,
            'progress'    => $progress,
            'manager_id'  => $p->manager_id,
            'manager'     => $p->relationLoaded('manager') ? $p->manager : null,
            'member_ids'  => $p->relationLoaded('members') ? $p->members->pluck('id') : [],
            'members'     => $p->relationLoaded('members') ? $p->members : [],
            'tasks'       => $p->relationLoaded('tasks') ? $p->tasks : [],
            'activity_logs' => $p->relationLoaded('activityLogs') ? $p->activityLogs : [],
        ];
    }
}
