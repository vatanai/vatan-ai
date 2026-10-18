<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\SegmentEdit;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        return view('cf::productions.index', [
            'cfTitle' => 'صف ساخت و انتشار', 'cfIcon' => 'fa-clapperboard', 'cfHelp' => 'queue',
            'cfSubtitle' => 'همه‌ی پست‌های تقویم، وضعیت ساخت روی مک، نسخه‌ها، هزینه، انتشار و آمار — زنده.',
            'rows' => $this->rows(), 'status' => $request->get('status'),
        ]);
    }

    public function feed()
    {
        return ['rows' => $this->rows(), 'agent' => JobController::agentOnline(), 'at' => \Vatan\Content\Support\Fa::digits(now(config('content-factory.host.timezone'))->format('H:i:s'))];
    }

    /** ردیف‌های صف: هر پست تقویم فعال + تولیدهای بی‌ردیف */
    private function rows(): array
    {
        $ws = $this->ws();
        $disk = \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk', 'public'));
        $cal = \Vatan\Content\Models\Calendar::where('workspace_id', $ws)->orderByRaw("status = 'approved' desc")->latest('id')->first();
        $items = $cal ? $cal->items()->with(['production.latestVersion', 'production.snapshots', 'styleModel', 'musicTrack'])->orderBy('publish_date')->get() : collect();
        $prodIds = $items->pluck('production.id')->filter()->all();
        $jobs = \Vatan\Content\Models\Job::where('workspace_id', $ws)->whereIn('production_id', $prodIds)->whereIn('type', ['render'])->latest('id')->get()->groupBy('production_id');
        $F = \Vatan\Content\Support\Fa::class;
        $today = now(config('content-factory.host.timezone'))->toDateString();

        return $items->map(function ($i) use ($disk, $jobs, $F, $today) {
            $p = $i->production;
            $j = $p ? optional($jobs->get($p->id))->first() : null;
            $v = $p?->latestVersion;
            $snap = $p ? $p->snapshots->sortByDesc('captured_at')->first() : null;
            $m = (array) ($snap->metrics ?? []);
            $st = $p->status ?? 'planned';
            if ($j && in_array($j->status, ['queued', 'running'], true)) {
                $st = $j->status === 'running' ? 'rendering' : 'queued';
            } elseif ($j && $j->status === 'failed' && in_array($p->status, ['queued', 'rendering'], true)) {
                $st = 'failed';
            }
            $labels = config('content-factory.labels.production_status') + ['planned' => 'در تقویم', 'failed' => 'ناموفق'];

            return [
                'id' => $i->id, 'code' => $F::digits($i->code), 'date' => $F::date($i->publish_date, false, true), 'weekday' => $F::weekday($i->publish_date),
                'time' => $F::digits($i->publish_time), 'iso' => $i->publish_date?->toDateString(), 'past' => $i->publish_date?->toDateString() < $today,
                'format' => $i->format, 'hook' => (string) $i->hook, 'product' => (string) $i->product_label, 'style' => $i->styleModel->name_fa ?? $i->style,
                'music' => $i->musicTrack ? trim(($i->musicTrack->mood ?: 'موزیک #'.$i->musicTrack->id).($i->musicTrack->tempo_bpm ? ' · '.$F::n($i->musicTrack->tempo_bpm).' BPM' : '')) : 'انتخاب خودکار', 'stories' => count((array) $i->story_specs), 'scenes' => count((array) $i->scenes),
                'rejected' => $i->approval_status === 'rejected', 'status' => $st, 'status_fa' => $labels[$st] ?? $st,
                'version' => $p?->current_version ?: 0, 'cover' => ($v?->cover_path ?? $p?->cover_path) ? $disk->url($v?->cover_path ?? $p->cover_path) : null,
                'video' => $v?->video_path ? $disk->url($v->video_path) : null,
                'job' => $j ? ['status' => $j->status, 'progress' => $j->progress, 'message' => $j->message, 'agent' => $j->agent, 'at' => $F::digits($j->updated_at?->locale('fa')->diffForHumans())] : null,
                'location' => $i->build_location === 'server' ? 'سرور' : 'مک', 'seconds' => $p?->build_seconds, 'cost' => $p ? round((float) $p->cost_usd, 3) : 0,
                'published' => $p?->published_at ? $F::date($p->published_at, false, true) : null, 'permalink' => $p?->ig_permalink,
                'reach' => $m['reach'] ?? $m['views'] ?? null, 'likes' => $m['likes'] ?? null, 'saves' => $m['saves'] ?? null, 'comments' => $m['comments'] ?? null,
                'score' => $p?->score_total !== null && $p ? (float) $p->score_total : null, 'window' => $snap?->window,
                'url' => route('cf.item', $i), 'prod_url' => $p ? route('cf.productions.show', $p) : null, 'build_url' => route('cf.item.build', $i),
            ];
        })->values()->all();
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
