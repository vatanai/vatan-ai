<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Seo\Agents\ContentWriter;
use Vatan\Seo\Connectors\BlocksToHtml;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Services\RunRecorder;
use Vatan\Seo\Services\AutoPublisher;
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

    public function generate(Request $request, ContentWriter $writer, RunRecorder $recorder, AutoPublisher $publisher)
    {
        $site = $this->site();
        $data = $request->validate(['keyword_id' => 'required|integer', 'mode' => 'in:brief,full']);
        $kw = Keyword::where('site_id', $site->id)->findOrFail($data['keyword_id']);
        $existing = ContentItem::where('site_id', $site->id)->where('keyword_id', $kw->id)->latest()->first();
        if ($existing) {
            return redirect()->route('seo.content.show', $existing)->with('warning', 'برای این کلمه محتوا وجود دارد؛ برای جلوگیری از مقاله‌ی تکراری همان مورد باز شد.');
        }
        if (($data['mode'] ?? 'full') === 'full' && Budget::articlesThisMonth($site) >= Budget::limit($site, 'articles_per_month')) {
            return back()->with('warning', 'سهمیه‌ی مقاله‌ی ماهانه‌ی پروفایل بودجه پر شده است. فقط بریف بسازید یا بودجه را افزایش دهید.');
        }
        @set_time_limit(600);
        $item = null;
        $run = $recorder->record($site, 'writer', 'content_generate', function () use ($writer, $publisher, $site, $kw, $data, &$item) {
            $item = $writer->brief($site, $kw);
            if (($data['mode'] ?? 'full') === 'full') {
                $item = $writer->draft($site, $item);
                if ($item->status === 'review') $item = $publisher->publish($site, $item, 'seo-dashboard-auto');
            }
            return [$item->status === 'failed' ? 'failed' : 'success', 'محتوا برای «'.$kw->keyword.'» ← '.ContentItem::STATUSES[$item->status], ['content_item_id' => $item->id, 'url' => $item->published_url]];
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

    public function redraft(Request $request, ContentItem $item, ContentWriter $writer, RunRecorder $recorder, AutoPublisher $publisher)
    {
        $site = $this->site();
        abort_unless($item->site_id === $site->id, 404);
        abort_if($item->status === 'published', 409, 'بازنویسی مستقیم مقاله‌ی منتشرشده هنوز پشتیبانی نمی‌شود.');
        @set_time_limit(600);
        if ($note = trim((string) $request->input('note'))) {
            $item->brief = array_merge((array) $item->brief, ['editor_feedback' => $note]);
            $item->save();
        }
        $run = $recorder->record($site, 'writer', 'content_redraft', function () use ($writer, $publisher, $site, $item) {
            $item = $writer->draft($site, $item);
            if ($item->status === 'review') $item = $publisher->publish($site, $item, 'seo-dashboard-auto');
            return [$item->status === 'failed' ? 'failed' : 'success', 'بازنویسی/انتشار ← '.ContentItem::STATUSES[$item->status], ['content_item_id' => $item->id, 'url' => $item->published_url]];
        }, ['trigger' => 'manual']);
        return back()->with($run->status === 'failed' ? 'error' : 'success', $run->summary);
    }
}
