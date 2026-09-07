<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ArticleComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleCommentController extends Controller
{
    public function index(Request $request): View
    {
        $query = ArticleComment::with(['article', 'user']);
        if ($status = $request->query('status')) $query->where('status', $status);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($comments) => $comments->where('body', 'like', "%{$search}%")
                ->orWhereHas('article', fn ($article) => $article->where('title', 'like', "%{$search}%")));
        }
        $comments = $query->latest()->paginate(30)->withQueryString();
        $counts = ArticleComment::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.articles.comments', compact('comments', 'counts'));
    }

    public function update(Request $request, ArticleComment $comment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,approved,rejected,spam']]);
        $comment->update([
            'status' => $data['status'],
            'approved_by' => $data['status'] === 'approved' ? $request->user('admin')->id : null,
            'approved_at' => $data['status'] === 'approved' ? now() : null,
        ]);

        return back()->with('success', 'وضعیت دیدگاه بروزرسانی شد.');
    }

    public function destroy(ArticleComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'دیدگاه حذف شد.');
    }
}
