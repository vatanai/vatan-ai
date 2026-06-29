<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CrmActivityLog::orderByDesc('created_at');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('personnel_id')) {
            $query->where('personnel_id', $request->personnel_id);
        }

        $logs = $query->limit(100)->get()->map(fn($log) => [
            'id'             => $log->id,
            'type'           => $log->type,
            'userId'         => $log->personnel_id,
            'userName'       => $log->personnel_name,
            'action'         => $log->action,
            'projectId'      => $log->project_id,
            'taskId'         => $log->task_id,
            'timestamp'      => $log->created_at->timestamp * 1000,
        ]);

        return response()->json($logs);
    }
}
