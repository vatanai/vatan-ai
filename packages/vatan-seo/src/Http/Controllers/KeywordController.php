<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vatan\Seo\Agents\Advisor;
use Vatan\Seo\Agents\Clustering;
use Vatan\Seo\Agents\KeywordDiscovery;
use Vatan\Seo\Models\Cluster;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Services\RunRecorder;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class KeywordController extends BaseController
{
    public function index(Request $request)
    {
        $site = $this->site();
        $tab = in_array($request->query('tab'), ['targets', 'candidates', 'archived'], true) ? $request->query('tab') : 'targets';
        $status = ['targets' => 'target', 'candidates' => 'candidate', 'archived' => 'archived'][$tab];
        $q = Keyword::where('site_id', $site->id)->where('status', $status)->with('cluster');
        if ($s = trim((string) $request->query('q'))) {
            $q->where('normalized', 'like', '%'.Fa::normalizeKeyword($s).'%');
        }
        if ($intent = $request->query('intent')) {
            $q->where('intent', $intent);
        }
        if ($source = $request->query('source')) {
            $q->where('source', $source);
        }
        $q = match ($request->query('sort', $tab === 'targets' ? 'position' : 'score')) {
            'position' => $q->orderByRaw('current_position IS NULL, current_position'),
            'impressions' => $q->orderByDesc('impressions_28d'),
            'change' => $q->orderByRaw('(previous_position - current_position) DESC'),
            default => $q->orderByDesc('ai_score')->orderByDesc('impressions_28d'),
        };
        $keywords = $q->paginate(50)->withQueryString();

        $spark = [];
        if ($tab === 'targets') {
            $rows = DB::table('seo_keyword_ranks')->whereIn('keyword_id', $keywords->pluck('id'))->where('date', '>=', now()->subDays(30)->toDateString())->orderBy('date')->get(['keyword_id', 'position']);
            foreach ($rows as $r) {
                $spark[$r->keyword_id][] = $r->position;
            }
        }

        return $this->view('keywords.index', [
            'tab' => $tab,
            'keywords' => $keywords,
            'spark' => $spark,
            'counts' => Keyword::where('site_id', $site->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'limit' => Budget::limit($site, 'tracked_keywords', 20),
            'clusters' => Cluster::where('site_id', $site->id)->withCount('keywords')->orderByDesc('keywords_count')->limit(12)->get(),
        ]);
    }

    public function store(Request $request, Installer $installer)
    {
        $site = $this->site();
        $data = $request->validate(['keywords' => 'required|string|max:5000', 'as' => 'in:target,candidate']);
        $lines = array_filter(array_map(fn ($l) => Fa::cleanKeyword($l), preg_split('/[\r\n,،]+/u', $data['keywords'])));
        $asTarget = ($data['as'] ?? 'candidate') === 'target';
        $added = 0;
        $skipped = 0;
        foreach (array_unique($lines) as $line) {
            if ($asTarget && $site->keywords()->targets()->count() >= Budget::limit($site, 'tracked_keywords', 20)) {
                $skipped++;
                continue;
            }
            $kw = Keyword::firstOrNew(['site_id' => $site->id, 'normalized' => Fa::normalizeKeyword($line)]);
            $isNew = ! $kw->exists;
            $kw->keyword = $kw->keyword ?: $line;
            $kw->source = $kw->source ?: 'manual';
            if ($asTarget && $kw->status !== 'target') {
                $kw->status = 'target';
                $kw->targeted_at = now();
            } elseif ($isNew) {
                $kw->status = 'candidate';
            }
            $kw->save();
            if ($kw->status === 'target') {
                $installer->keywordTasks($site, $kw);
            }
            $added += (int) $isNew;
        }
        return back()->with('success', Fa::n($added).' کلمه اضافه شد.'.($skipped ? ' '.Fa::n($skipped).' مورد به‌خاطر سقف کلمات هدف پروفایل اضافه نشد.' : ''));
    }

    public function discover(RunRecorder $recorder, KeywordDiscovery $discovery)
    {
        $site = $this->site();
        @set_time_limit(600);
        $run = $recorder->record($site, 'researcher', 'discovery', function () use ($site, $discovery) {
            $r = $discovery->run($site);
            return ['success', sprintf('%s محصول بررسی شد؛ %s پیشنهاد تازه و %s به‌روزرسانی.', Fa::n($r['products']), Fa::n($r['created']), Fa::n($r['updated'])), $r];
        }, ['trigger' => 'manual']);
        return redirect()->route('seo.keywords.index', ['tab' => 'candidates'])->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }

    public function bulk(Request $request, Installer $installer, Clustering $clustering)
    {
        $site = $this->site();
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'action' => 'required|in:target,candidate,archive,delete']);
        $items = Keyword::where('site_id', $site->id)->whereIn('id', $data['ids'])->get();
        $limit = Budget::limit($site, 'tracked_keywords', 20);
        $n = 0;
        $blocked = 0;
        foreach ($items as $kw) {
            switch ($data['action']) {
                case 'target':
                    if ($kw->status !== 'target' && $site->keywords()->targets()->count() >= $limit) {
                        $blocked++;
                        break;
                    }
                    $kw->update(['status' => 'target', 'targeted_at' => $kw->targeted_at ?? now(), 'target_url' => $kw->target_url ?: data_get($kw->meta, 'product_url')]);
                    $installer->keywordTasks($site, $kw);
                    $n++;
                    break;
                case 'candidate':
                case 'archive':
                    $kw->update(['status' => $data['action'] === 'archive' ? 'archived' : 'candidate']);
                    Task::where('keyword_id', $kw->id)->open()->update(['status' => 'skipped', 'last_message' => 'کلمه از فهرست هدف خارج شد.']);
                    $n++;
                    break;
                case 'delete':
                    $kw->delete();
                    $n++;
            }
        }
        if ($data['action'] === 'target' && $site->keywords()->targets()->count() >= 3 && \Vatan\Seo\Models\Cluster::where('site_id', $site->id)->doesntExist()) {
            try {
                $clustering->run($site);
            } catch (\Throwable) {
            }
        }
        return back()->with($blocked ? 'warning' : 'success', Fa::n($n).' کلمه به‌روزرسانی شد.'.($blocked ? ' سقف کلمات هدف پروفایل بودجه ('.Fa::n($limit).') پر است.' : ''));
    }

    public function show(Keyword $keyword)
    {
        abort_unless($keyword->site_id === $this->site()->id, 404);
        $ranks = $keyword->ranks()->where('date', '>=', now()->subDays(120)->toDateString())->get();
        $pages = DB::table('seo_query_metrics')->selectRaw('page, SUM(clicks) clicks, SUM(impressions) impressions, SUM(position*impressions)/NULLIF(SUM(impressions),0) position')
            ->where('site_id', $keyword->site_id)->where('query_hash', sha1($keyword->normalized))->where('date', '>=', now()->subDays(28)->toDateString())
            ->groupBy('page')->orderByDesc('impressions')->limit(5)->get();
        return $this->view('keywords.show', [
            'kw' => $keyword,
            'ranks' => $ranks,
            'chart' => ['labels' => $ranks->map(fn ($r) => Fa::dayLabel($r->date))->values(), 'position' => $ranks->pluck('position')->values(), 'clicks' => $ranks->pluck('clicks')->values()],
            'pages' => $pages,
            'tasks' => Task::where('keyword_id', $keyword->id)->orderBy('due_on')->get(),
            'content' => ContentItem::where('keyword_id', $keyword->id)->latest()->get(),
            'links' => app(Advisor::class)->internalLinks($this->site(), $keyword),
            'wave' => (int) data_get($keyword->meta, 'wave_week', 0),
        ]);
    }

    public function update(Request $request, Keyword $keyword, Installer $installer)
    {
        abort_unless($keyword->site_id === $this->site()->id, 404);
        $data = $request->validate([
            'target_url' => 'nullable|url|max:700', 'intent' => 'nullable|in:'.implode(',', array_keys(Keyword::INTENTS)),
            'priority' => 'nullable|integer|min:1|max:5', 'notes' => 'nullable|string|max:2000', 'status' => 'nullable|in:target,candidate,archived',
        ]);
        if (($data['status'] ?? null) === 'target' && $keyword->status !== 'target') {
            if ($this->site()->keywords()->targets()->count() >= Budget::limit($this->site(), 'tracked_keywords', 20)) {
                return back()->with('warning', 'سقف کلمات هدف پروفایل بودجه پر است.');
            }
            $data['targeted_at'] = now();
        }
        $keyword->update(array_filter($data, fn ($v) => $v !== null) + ['notes' => $data['notes'] ?? $keyword->notes]);
        if ($keyword->status === 'target') {
            $installer->keywordTasks($this->site(), $keyword);
        }
        return back()->with('success', 'ذخیره شد.');
    }

    public function advice(Keyword $keyword, Advisor $advisor, RunRecorder $recorder)
    {
        $site = $this->site();
        abort_unless($keyword->site_id === $site->id, 404);
        $run = $recorder->record($site, 'strategist', 'onpage_advice', function () use ($advisor, $site, $keyword) {
            $advisor->onpage($site, $keyword);
            return ['success', 'پیشنهاد بهینه‌سازی برای «'.$keyword->keyword.'» آماده شد.'];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }
}
