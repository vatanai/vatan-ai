<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmMicrotask;
use App\Models\Crm\CrmTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmMicrotaskController extends Controller
{
    public function toggle(string $taskId, string $microId): JsonResponse
    {
        $micro = CrmMicrotask::where('task_id', $taskId)->findOrFail($microId);
        $micro->done = ! $micro->done;
        $micro->save();

        // اگر همه میکروتسک‌ها done شدند، تسک را هم done کن
        $task = CrmTask::with('microtasks')->find($taskId);
        if ($task && $task->microtasks->isNotEmpty() && $task->microtasks->every('done')) {
            $task->done = true;
            $task->status = 'done';
            $task->completed_at = now();
            $task->save();
        } elseif ($task && $task->done && ! $task->microtasks->every('done')) {
            $task->done = false;
            $task->status = 'inprogress';
            $task->completed_at = null;
            $task->save();
        }

        return response()->json([
            'microtask' => $micro,
            'task_done' => $task?->done,
            'task_status' => $task?->status,
        ]);
    }

    public function destroy(string $taskId, string $microId): JsonResponse
    {
        $micro = CrmMicrotask::where('task_id', $taskId)->findOrFail($microId);
        $micro->delete();
        return response()->json(['message' => 'حذف شد']);
    }
}
