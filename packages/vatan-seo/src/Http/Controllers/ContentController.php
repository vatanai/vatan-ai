<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Seo\Agents\ContentWriter;
use Vatan\Seo\Agents\Publisher;
use Vatan\Seo\Connectors\BlocksToHtml;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Services\RunRecorder;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

class ContentController extends BaseController
{
    public function index(Request $request)
    {
        $site = $this->site();
        $status = $request->query('status');
        $q = ContentItem::where('site_id', $site->id)->with('keyword')->latest();
        if ($status && isset(ContentItem::STATUSES[$status])) {
            $q->where('status', $status);
        }
        $taken = ContentItem::where('site_id', $site->id)->whereNotNull('keyword_id')->pluck('keyword_id');
        return $this->view('content.index', [
            'items' => $q->paginate(30)->withQueryString(),
            'status' => $status,
            'counts' => ContentItem::where('site_id', $site->id)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
            'available' => Keyword::where('site_id', $site->id)->targets()->whereNotIn('id', $taken)->orderByDesc('ai_score')->get(),
            'quota' => ['articles' => Budget::limit($site, 'articles_per_month'), 'used' => Budget::articlesThisMonth($site)],
        ]);
    }

    public function generate(Request $request, ContentWriter $writer, RunRecorder $recorder)
    {
        $site = $this->site();
        $data = $request->validate(['keyword_id' => 'required|integer', 'mode' => 'in:brief,full']);
        $kw = Keyword::where('site_id', $site->id)->findOrFail($data['keyword_id']);
        if (($data['mode'] ?? 'full') === 'full' && Budget::articlesThisMonth($site) >= Budget::limit($site, 'articles_per_month')) {
            return back()->with('warning', 'سهمیه‌ی مقاله‌ی ماهانه‌ی پروفایل بودجه پر شده است. فقط بریف بسازید یا بودجه را افزایش دهید.');
        }
        @set_time_limit(600);
        $item = null;
        $run = $recorder->record($site, 'writer', 'content_generate', function () use ($writer, $site, $kw, $data, &$item) {
            $item = $writer->brief($site, $kw);
            if (($data['mode'] ?? 'full') === 'full') {
                $item = $writer->draft($site, $item);
            }
            return [$item->status === 'failed' ? 'failed' : 'success', 'محتوا برای «'.$kw->keyword.'» ← '.ContentItem::STATUSES[$item->status]];
        }, ['trigger' => 'manual']);
        return $item ? redirect()->route('seo.content.show', $item)->with($run->status === 'failed' ? 'error' : 'success', $run->summary) : back()->with('error', $run->summary);
    }

    public function show(ContentItem $item, ContentWriter $writer)
    {
        abort_unless($item->site_id === $this->site()->id, 404);
        return $this->view('content.show', [
            'item' => $item,
            'html' => BlocksToHtml::render((array) $item->blocks),
            'checks' => $item->blocks ? $writer->seoScore($item)[1] : [],
        ]);
    }

    public function publish(ContentItem $item, Publisher $publisher)
    {
        abort_unless($item->site_id === $this->site()->id, 404);
        try {
            $publisher->publish($this->site(), $item, $this->adminRef());
        } catch (\Throwable $e) {
            return back()->with('error', 'انتشار ناموفق بود: '.$e->getMessage());
        }
        return back()->with('success', 'منتشر شد: '.urldecode((string) $item->published_url));
    }

    public function reject(Request $request, ContentItem $item)
    {
        abort_unless($item->site_id === $this->site()->id, 404);
        $item->update(['status' => 'rejected', 'reviewer_note' => $request->input('note'), 'approved_by' => $this->adminRef()]);
        return back()->with('success', 'رد شد.');
    }

    public function redraft(Request $request, ContentItem $item, ContentWriter $writer, RunRecorder $recorder)
    {
        $site = $this->site();
        abort_unless($item->site_id === $site->id, 404);
        @set_time_limit(600);
        if ($note = trim((string) $request->input('note'))) {
            $item->brief = array_merge((array) $item->brief, ['editor_feedback' => $note]);
            $item->save();
        }
        $run = $recorder->record($site, 'writer', 'content_redraft', function () use ($writer, $site, $item) {
            $item = $writer->draft($site, $item);
            return [$item->status === 'failed' ? 'failed' : 'success', 'بازنویسی انجام شد ← '.ContentItem::STATUSES[$item->status]];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }
}
