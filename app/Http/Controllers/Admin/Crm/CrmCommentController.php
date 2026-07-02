<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmTask;
use App\Models\Crm\CrmTaskComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmCommentController extends Controller
{
    public function index(string $taskId): JsonResponse
    {
        CrmTask::findOrFail($taskId);
        $comments = CrmTaskComment::where('task_id', $taskId)->latest()->get();
        return response()->json($comments);
    }

    public function store(Request $request, string $taskId): JsonResponse
    {
        CrmTask::findOrFail($taskId);

        $data = $request->validate([
            'body'        => 'required|string|max:2000',
            'author_name' => 'nullable|string|max:100',
        ]);

        $admin = Auth::guard('admin')->user();

        $comment = CrmTaskComment::create([
            'task_id'     => $taskId,
            'body'        => $data['body'],
            'author_name' => $data['author_name'] ?? ($admin?->name ?? 'ادمین'),
        ]);

        return response()->json($comment, 201);
    }

    public function destroy(string $taskId, string $commentId): JsonResponse
    {
        $comment = CrmTaskComment::where('task_id', $taskId)->findOrFail($commentId);
        $comment->delete();
        return response()->json(['message' => 'کامنت حذف شد']);
    }
}
