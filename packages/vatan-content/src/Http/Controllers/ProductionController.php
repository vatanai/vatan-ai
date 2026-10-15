<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\SegmentEdit;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $ws = $this->ws();
        $q = Production::where('workspace_id', $ws)->with(['item', 'latestVersion']);
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        $productions = $q->get()->sortBy(fn ($p) => $p->item?->publish_date?->timestamp ?? PHP_INT_MAX)->values();
        $counts = Production::where('workspace_id', $ws)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('cf::productions.index', [
            'cfTitle' => 'کارخانه‌ی تولید و صف تأیید', 'cfIcon' => 'fa-clapperboard', 'cfHelp' => 'productions',
            'cfSubtitle' => 'هر آیتم تأییدشده‌ی تقویم اینجا ساخته، بازبینی، اصلاح و برای انتشار آماده می‌شود.',
            'productions' => $productions, 'counts' => $counts, 'status' => $request->status,
        ]);
    }

    public function show(Production $production)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $production->load(['item', 'versions', 'edits', 'music', 'snapshots', 'jobs']);

        return view('cf::productions.show', [
            'cfTitle' => $production->title, 'cfIcon' => 'fa-film',
            'cfSubtitle' => trim(($production->item?->code ? $production->item->code.' · ' : '').($production->format ?? '')),
            'p' => $production, 'v' => $production->versions->first(), 'siPost' => $production->si_post_id && class_exists(\App\Models\SmartInstagram\Post::class) ? \App\Models\SmartInstagram\Post::find($production->si_post_id) : null,
        ]);
    }

    public function approve(Request $request, Production $production)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        abort_unless($production->current_version > 0, 422, 'هنوز نسخه‌ای ساخته نشده است.');
        $production->update(['status' => 'approved', 'review_note' => $request->input('note')]);
        $production->versions()->where('version', $production->current_version)->update(['status' => 'approved']);
        $production->item?->update(['production_status' => 'approved']);

        return back()->with('success', 'نسخه‌ی '.$production->current_version.' تأیید شد و آماده‌ی انتشار است.');
    }

    public function reject(Request $request, Production $production)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $request->validate(['note' => 'required|string|max:2000']);
        $production->update(['status' => 'rejected', 'review_note' => $request->note]);
        $production->item?->update(['production_status' => 'rejected']);

        return back()->with('success', 'تولید رد شد.');
    }

    public function segmentEdit(Request $request, Production $production)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $d = $request->validate([
            'start_sec' => 'required|numeric|min:0', 'end_sec' => 'required|numeric|gt:start_sec',
            'kind' => 'required|in:edit,generative', 'note' => 'required|string|max:2000',
        ]);
        SegmentEdit::create($d + ['production_id' => $production->id, 'version' => $production->current_version, 'created_by' => $this->adminName()]);
        $production->update(['status' => 'revision']);
        $production->item?->update(['production_status' => 'revision']);

        return back()->with('success', 'اصلاحیه ثبت شد. فقط همین بازه دوباره ساخته می‌شود و نسخه‌ی جدید برای تأیید می‌آید.');
    }

    public function published(Request $request, Production $production, \Vatan\Content\Services\InstagramBridge $bridge)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $d = $request->validate(['ig_permalink' => 'nullable|url|max:255', 'auto_reply' => 'nullable|boolean']);
        $production->update(['status' => 'published', 'published_at' => now(), 'ig_permalink' => $d['ig_permalink'] ?? $production->ig_permalink,
            'auto_reply' => $request->boolean('auto_reply', $production->auto_reply)]);
        $production->item?->update(['production_status' => 'published']);
        $msg = 'به‌عنوان منتشرشده ثبت شد.';
        if ($bridge->available()) {
            $post = $bridge->postFor($production);
            $camp = $post ? $bridge->campaign($production, auth(config('content-factory.host.admin_guard'))->id()) : null;
            $msg .= $post ? ' به پست اینستاگرام هوشمند وصل شد.' : ' پست هنوز در اینستاگرام هوشمند پیدا نشد؛ بعد از همگام‌سازی خودکار وصل می‌شود.';
            $msg .= $camp ? ' سناریوی کامنت/دایرکت ساخته شد.' : '';
        }

        return back()->with('success', $msg.' برداشت آمار از ۱۰ دقیقه بعد شروع می‌شود.');
    }

    public function instagram(Production $production, \Vatan\Content\Services\InstagramBridge $bridge)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $post = $bridge->postFor($production);
        if (! $post) {
            return back()->with('warning', 'پست این تولید در اینستاگرام هوشمند پیدا نشد. لینک پست را در فرم انتشار وارد کنید یا همگام‌سازی پست‌ها را بزنید.');
        }
        $bridge->campaign($production, auth(config('content-factory.host.admin_guard'))->id());
        $n = $bridge->snapshot($production);

        return back()->with('success', 'اتصال به‌روز شد'.($n ? ' و '.$n.' برداشت آمار ثبت شد.' : '.'));
    }

    public function regenerateCaption(Production $production, \Vatan\Content\Services\Builder $builder)
    {
        abort_unless($production->workspace_id === $this->ws() && $production->item, 404);
        $r = $builder->caption($production->item, $production, true);

        return back()->with($r['source'] === 'ai' ? 'success' : 'warning', $r['source'] === 'ai' ? 'کپشن تازه با پرامپت نسخه‌ی '.$r['prompt_version'].' نوشته شد.'
            : 'هوش در دسترس نبود ('.($r['error'] ?? '').')؛ متن آماده‌ی تقویم استفاده شد.');
    }

    public function rebuild(Request $request, Production $production, \Vatan\Content\Services\Builder $builder)
    {
        abort_unless($production->workspace_id === $this->ws() && $production->item, 404);
        $builder->build($production->item, $this->adminName(), $request->boolean('regen_caption'));
        JobController::spawnLocal();

        return back()->with('success', 'ساخت نسخه‌ی جدید در صف ایجنت مک قرار گرفت.');
    }

    public function caption(Request $request, Production $production)
    {
        abort_unless($production->workspace_id === $this->ws(), 404);
        $production->update($request->validate(['caption' => 'nullable|string|max:5000', 'hashtags' => 'nullable|string|max:1000']));

        return back()->with('success', 'کپشن ذخیره شد.');
    }
}
