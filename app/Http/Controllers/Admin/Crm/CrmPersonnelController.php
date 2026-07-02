<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmPersonnel;
use App\Models\Crm\CrmActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmPersonnelController extends Controller
{
    public function index(): JsonResponse
    {
        $personnel = CrmPersonnel::withCount([
            'assignedTasks',
            'assignedTasks as done_tasks_count' => fn($q) => $q->where('status', 'done'),
            'assignedTasks as inprog_tasks_count' => fn($q) => $q->where('status', 'inprogress'),
        ])->orderBy('name')->get();

        return response()->json($personnel);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'mobile'    => 'nullable|string|max:20',
            'role'      => 'required|string|max:60',
            'email'     => 'nullable|email|max:150',
            'join_date' => 'nullable|string|max:20',
            'active'    => 'boolean',
        ]);

        $personnel = CrmPersonnel::create($data);

        CrmActivityLog::log('personnel', 'افزودن نیرو: ' . $personnel->name, $personnel->id, $personnel->name);

        return response()->json($personnel, 201);
    }

    public function show(string $id): JsonResponse
    {
        $personnel = CrmPersonnel::with([
            'projects',
            'assignedTasks.microtasks',
            'attendance' => fn($q) => $q->orderByDesc('date')->limit(10),
        ])->findOrFail($id);

        return response()->json($personnel);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $personnel = CrmPersonnel::findOrFail($id);

        $data = $request->validate([
            'name'      => 'sometimes|string|max:100',
            'mobile'    => 'nullable|string|max:20',
            'role'      => 'sometimes|string|max:60',
            'email'     => 'nullable|email|max:150',
            'join_date' => 'nullable|string|max:20',
            'active'    => 'boolean',
        ]);

        $personnel->update($data);

        CrmActivityLog::log('personnel', 'ویرایش نیرو: ' . $personnel->name, $personnel->id, $personnel->name);

        return response()->json($personnel);
    }

    public function destroy(string $id): JsonResponse
    {
        $personnel = CrmPersonnel::findOrFail($id);
        $name = $personnel->name;

        // حذف نرم — داده‌ها باقی می‌ماند
        $personnel->delete();

        CrmActivityLog::log('personnel', 'حذف نیرو: ' . $name);

        return response()->json(['message' => 'حذف شد']);
    }

    public function assignProjects(Request $request, string $id): JsonResponse
    {
        $personnel = CrmPersonnel::findOrFail($id);

        $request->validate([
            'project_ids'   => 'required|array',
            'project_ids.*' => 'uuid',
        ]);

        $personnel->projects()->sync($request->project_ids);

        CrmActivityLog::log('personnel', 'بروزرسانی پروژه‌های ' . $personnel->name, $personnel->id, $personnel->name);

        return response()->json(['message' => 'پروژه‌ها بروزرسانی شد']);
    }
}
