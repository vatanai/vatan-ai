<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CrmMicrotask;
use App\Models\Crm\CrmActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CrmTask::with(['assignee', 'microtasks', 'comments'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->assignee_id);
        }

        return response()->json($query->get()->map(fn($t) => $this->formatTask($t)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id'  => 'required|uuid',
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'required|in:urgent,high,medium,low',
            'status'      => 'required|in:backlog,todo,inprogress,done',
            'deadline'    => 'nullable|string|max:20',
            'start_date'  => 'nullable|string|max:20',
            'assignee_id' => 'nullable|uuid',
            'microtasks'  => 'nullable|array',
            'microtasks.*.text' => 'required|string|max:200',
            'microtasks.*.done' => 'boolean',
        ]);

        $microtasksData = $data['microtasks'] ?? [];
        unset($data['microtasks']);

        $isDone = $data['status'] === 'done';
        $data['done'] = $isDone;
        $data['completed_at'] = $isDone ? now() : null;

        $task = DB::transaction(function () use ($data, $microtasksData) {
            $task = CrmTask::create($data);
            foreach ($microtasksData as $i => $m) {
                CrmMicrotask::create([
                    'task_id'    => $task->id,
                    'text'       => $m['text'],
                    'done'       => $m['done'] ?? false,
                    'sort_order' => $i,
                ]);
            }
            return $task;
        });

        CrmActivityLog::log('task', 'ایجاد تسک: ' . $task->title, null, null, $task->project_id, $task->id);

        return response()->json($this->formatTask($task->load(['assignee', 'microtasks', 'comments'])), 201);
    }

    public function show(string $id): JsonResponse
    {
        $task = CrmTask::with(['assignee', 'microtasks', 'comments', 'project'])->findOrFail($id);
        return response()->json($this->formatTask($task));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $task = CrmTask::findOrFail($id);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'priority'    => 'sometimes|in:urgent,high,medium,low',
            'status'      => 'sometimes|in:backlog,todo,inprogress,done',
            'deadline'    => 'nullable|string|max:20',
            'start_date'  => 'nullable|string|max:20',
            'assignee_id' => 'nullable|uuid',
            'microtasks'  => 'nullable|array',
            'microtasks.*.text' => 'required|string|max:200',
            'microtasks.*.done' => 'boolean',
        ]);

        $microtasksData = null;
        if (array_key_exists('microtasks', $data)) {
            $microtasksData = $data['microtasks'];
            unset($data['microtasks']);
        }

        if (isset($data['status'])) {
            $isDone = $data['status'] === 'done';
            $data['done'] = $isDone;
            if ($isDone && ! $task->completed_at) {
                $data['completed_at'] = now();
            } elseif (! $isDone) {
                $data['completed_at'] = null;
            }
        }

        DB::transaction(function () use ($task, $data, $microtasksData) {
            $task->update($data);
            if ($microtasksData !== null) {
                $task->microtasks()->delete();
                foreach ($microtasksData as $i => $m) {
                    CrmMicrotask::create([
                        'task_id'    => $task->id,
                        'text'       => $m['text'],
                        'done'       => $m['done'] ?? false,
                        'sort_order' => $i,
                    ]);
                }
            }
        });

        CrmActivityLog::log('task', 'ویرایش تسک: ' . $task->title, null, null, $task->project_id, $task->id);

        return response()->json($this->formatTask($task->load(['assignee', 'microtasks', 'comments'])));
    }

    public function destroy(string $id): JsonResponse
    {
        $task = CrmTask::findOrFail($id);
        $title = $task->title;
        $projectId = $task->project_id;

        $task->microtasks()->delete();
        $task->delete();

        CrmActivityLog::log('task', 'حذف تسک: ' . $title, null, null, $projectId, $id);

        return response()->json(['message' => 'تسک حذف شد']);
    }

    public function toggle(string $id): JsonResponse
    {
        $task = CrmTask::with('microtasks')->findOrFail($id);
        $newDone = ! $task->done;

        $task->done = $newDone;
        $task->status = $newDone ? 'done' : 'inprogress';
        $task->completed_at = $newDone ? now() : null;

        if ($newDone) {
            $task->microtasks()->update(['done' => true]);
        }

        $task->save();

        CrmActivityLog::log(
            'task',
            $newDone ? 'تکمیل تسک: ' . $task->title : 'بازگشایی تسک: ' . $task->title,
            null, null,
            $task->project_id,
            $task->id
        );

        return response()->json($this->formatTask($task->load(['assignee', 'microtasks', 'comments'])));
    }

    // ─── Private ─────────────────────────────────────────────

    private function formatTask(CrmTask $t): array
    {
        return [
            'id'          => $t->id,
            'project_id'  => $t->project_id,
            'title'       => $t->title,
            'description' => $t->description,
            'priority'    => $t->priority,
            'status'      => $t->status,
            'done'        => $t->done,
            'deadline'    => $t->deadline,
            'start_date'  => $t->start_date,
            'assignee_id' => $t->assignee_id,
            'assignee'    => $t->relationLoaded('assignee') ? $t->assignee : null,
            'completed_at'=> $t->completed_at?->timestamp * 1000,
            'created_at'  => $t->created_at?->timestamp * 1000,
            'microtasks'  => $t->relationLoaded('microtasks') ? $t->microtasks : [],
            'comments'    => $t->relationLoaded('comments') ? $t->comments : [],
        ];
    }
}
