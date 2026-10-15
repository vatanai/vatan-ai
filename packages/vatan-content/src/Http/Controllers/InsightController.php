<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\Insight;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\Reference;
use Vatan\Content\Models\Style;

class InsightController extends Controller
{
    public function index()
    {
        $ws = $this->ws();
        $published = Production::where('workspace_id', $ws)->where('status', 'published')->with(['snapshots', 'item'])->latest('published_at')->get();
        // رتبه‌بندی اجزا (فقط وقتی امتیاز داریم)
        $scored = $published->whereNotNull('score_total');
        $rank = fn (string $key) => $scored->groupBy(fn ($p) => $p->gene[$key] ?? '—')->map(fn ($g, $k) => ['name' => $k, 'n' => $g->count(), 'avg' => round($g->avg('score_total'), 1)])->sortByDesc('avg')->values();
        $account = null;
        if (class_exists(\App\Models\SmartInstagram\Post::class)) {
            try {
                $posts = \App\Models\SmartInstagram\Post::query()->whereNotNull('published_at')->latest('published_at')->limit(30)->get();
                if ($posts->count()) {
                    $account = ['n' => $posts->count(), 'likes' => round($posts->avg('like_count'), 1), 'comments' => round($posts->avg('comments_count'), 1),
                        'saves' => round($posts->avg('saved_count'), 1), 'shares' => round($posts->avg('shares_count'), 1), 'reach' => round($posts->avg('reach_count'), 1),
                        'reels' => $posts->where('media_type', 'VIDEO')->count(), 'best' => $posts->sortByDesc(fn ($p) => $p->saved_count * 3 + $p->shares_count * 4 + $p->comments_count * 2 + $p->like_count)->first()];
                }
            } catch (\Throwable) {
            }
        }

        return view('cf::insights', [
            'cfTitle' => 'تحلیل و یادگیری', 'cfIcon' => 'fa-chart-line', 'cfHelp' => 'insights',
            'cfSubtitle' => 'آمار زمان‌مند هر انتشار به سبک، هوک، موسیقی و پرامپت وصل می‌شود. با داده‌ی کم، نتیجه «نامشخص» اعلام می‌شود.',
            'insights' => Insight::where('workspace_id', $ws)->orderByRaw("status = 'pending' desc")->latest()->get()->groupBy('kind'),
            'published' => $published, 'windows' => config('content-factory.metric_windows'), 'account' => $account,
            'ranks' => ['style' => $rank('style'), 'hook_family' => $rank('hook_family'), 'pillar' => $rank('pillar'), 'music_mood' => $rank('music_mood')],
            'library' => ['refs' => Reference::where('workspace_id', $ws)->count(), 'styles' => Style::where('workspace_id', $ws)->count(),
                'music' => MusicTrack::where('workspace_id', $ws)->count(), 'avgShot' => round((float) Reference::where('workspace_id', $ws)->avg('avg_shot_len'), 1),
                'avgDur' => round((float) Reference::where('workspace_id', $ws)->avg('duration'), 1)],
        ]);
    }

    public function decide(Request $request, Insight $insight)
    {
        abort_unless($insight->workspace_id === $this->ws(), 404);
        $d = $request->validate(['status' => 'required|in:accepted,rejected,testing']);
        $insight->update($d + ['decided_by' => $this->adminName(), 'decided_at' => now()]);

        return back()->with('success', 'تصمیم ثبت شد.');
    }
}
