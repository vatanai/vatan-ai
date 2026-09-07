<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ArticleComment;
use App\Services\ArticleAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ArticleCommentController extends Controller
{
    public function store(Request $request, Article $article, ArticleAnalyticsService $analytics): RedirectResponse
    {
        abort_unless($article->isPubliclyVisible() && $article->allow_comments, 404);
        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = ArticleComment::query()->where('article_id', $article->id)
                ->whereNull('parent_id')->where('status', 'approved')->findOrFail($data['parent_id']);
        }

        $article->comments()->create([
            'parent_id' => $parent?->id,
            'user_id' => $request->user()->id,
            'body' => trim(strip_tags($data['body'])),
            'status' => 'pending',
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . '|' . config('app.key')) : null,
            'user_agent_hash' => $request->userAgent() ? hash('sha256', $request->userAgent()) : null,
        ]);

        $analytics->record($article, 'comment_submit', $request);

        return back()->with('comment_success', 'دیدگاه شما ثبت شد و پس از بررسی نمایش داده می‌شود.')->withFragment('comments');
    }
}
