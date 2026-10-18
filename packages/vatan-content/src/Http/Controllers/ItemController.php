<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Job;
use Vatan\Content\Models\MetricSnapshot;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Style;
use Vatan\Content\Services\Builder;
use Vatan\Content\Services\InstagramBridge;
use Vatan\Content\Services\MasterPrompt;
use Vatan\Content\Services\Scorer;
use Vatan\Content\Support\Catalog;
use Vatan\Content\Support\Fa;

/** صفحه‌ی کامل هر پست تقویم: صفر تا صد — استراتژی، محصول، هوک، صحنه‌ها، استوری، کپشن، پرامپت جامع، ساخت، انتشار و آمار. */
class ItemController extends Controller
{
    private function own(CalendarItem $item): void
    {
        abort_unless($item->workspace_id === $this->ws(), 404);
    }

    public function show(CalendarItem $item, MasterPrompt $master)
    {
        $this->own($item);
        $item->load(['calendar', 'production.versions', 'production.snapshots', 'production.jobs', 'styleModel', 'musicTrack']);
        $siblings = CalendarItem::where('calendar_id', $item->calendar_id)->orderBy('publish_date')->orderBy('sort')->get(['id', 'code', 'publish_date']);
        $idx = $siblings->search(fn ($s) => $s->id === $item->id);
        $p = $item->production;
        $siPost = $p && $p->si_post_id && class_exists(\App\Models\SmartInstagram\Post::class) ? \App\Models\SmartInstagram\Post::find($p->si_post_id) : null;
        $jobs = Job::where('workspace_id', $this->ws())->where(fn ($q) => $q->where('production_id', $p?->id ?? 0)
            ->orWhere(fn ($q) => $q->where('type', 'ai_write')->where('payload->item_id', $item->id)))->latest('id')->limit(8)->get();
        $ps = $master->sections($item);

        return view('cf::items.show', [
            'cfTitle' => Fa::digits($item->code).' · '.$item->hook, 'cfIcon' => 'fa-file-video', 'cfHelp' => 'item',
            'cfSubtitle' => Fa::weekday($item->publish_date).' '.Fa::date($item->publish_date, true, true).' · ساعت '.Fa::digits($item->publish_time).' · '.$item->format.' · '.$item->pillar,
            'item' => $item, 'p' => $p, 'siPost' => $siPost, 'jobs' => $jobs,
            'prev' => $idx > 0 ? $siblings[$idx - 1] : null, 'next' => $idx !== false && $idx < $siblings->count() - 1 ? $siblings[$idx + 1] : null,
            'styles' => Style::where('workspace_id', $this->ws())->where('status', '!=', 'archived')->orderBy('sort')->get(),
            'tracks' => MusicTrack::where('workspace_id', $this->ws())->orderBy('title')->get(),
            'catalog' => Catalog::products(), 'promptSections' => $ps, 'compiled' => $master->compile($item),
            'vars' => array_keys($master->vars($item)), 'images' => $this->imageOptions($item),
            'disk' => Storage::disk(config('content-factory.disk', 'public')),
            'aiOn' => app(\Vatan\Content\Services\Ai::class)->available(),
            'windows' => config('content-factory.metric_windows'),
        ]);
    }

    /** تصویرهای آماده برای انتخاب در صحنه‌ها (نمونه‌های وطن + هرچه الان در صحنه‌هاست) */
    private function imageOptions(CalendarItem $item): array
    {
        $disk = Storage::disk(config('content-factory.disk', 'public'));
        $opts = [];
        try {
            foreach (array_slice($disk->files('demo-productshots'), 0, 80) as $f) {
                $opts[] = 'storage:'.$f;
            }
        } catch (\Throwable) {
        }
        $n = ['s' => $item->scenes ?? [], 't' => $item->story_specs ?? [], 'a' => $item->assets ?? []];
        array_walk_recursive($n, function ($v) use (&$opts) {
            if (is_string($v) && preg_match('~^(storage:|lab:|https?://).+\.(png|jpe?g|webp)$~i', $v)) {
                $opts[] = $v;
            }
        });

        return array_values(array_unique($opts));
    }

    public function save(Request $request, CalendarItem $item)
    {
        $this->own($item);
        $d = $request->validate([
            'publish_date' => 'nullable|date', 'publish_time' => 'nullable|string|max:10', 'format' => 'nullable|in:ریلز,اسلایدی,پست',
            'pillar' => 'nullable|string|max:60', 'sector' => 'nullable|string|max:80', 'goal' => 'nullable|string|max:60', 'audience' => 'nullable|string|max:255',
            'idea' => 'nullable|string|max:3000', 'hook' => 'nullable|string|max:255', 'cta' => 'nullable|string|max:255', 'cover_text' => 'nullable|string|max:255',
            'style_id' => 'nullable|integer', 'music_id' => 'nullable|integer', 'engine' => 'nullable|string|max:255', 'build_location' => 'nullable|in:mac,server',
            'product' => 'nullable|string|max:200', 'product_url' => 'nullable|string|max:255', 'note' => 'nullable|string|max:2000', 'experiment' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:5000', 'hashtags' => 'nullable|string|max:1000', 'keywords_text' => 'nullable|string|max:300', 'tracking_url' => 'nullable|string|max:255',
            'visual_prompt' => 'nullable|string|max:3000', 'duration_sec' => 'nullable|integer|min:3|max:180', 'hook_family' => 'nullable|string|max:120', 'narrative' => 'nullable|string|max:2000',
            'scenes_json' => 'nullable|string|max:80000', 'story_specs_json' => 'nullable|string|max:30000', 'plan_json' => 'nullable|string|max:20000',
            'prompt_json' => 'nullable|string|max:80000', 'cover_image' => 'nullable|string|max:255', 'cover_hl' => 'nullable|string|max:200', 'cover_kicker' => 'nullable|string|max:120',
            'music_start' => 'nullable|numeric|min:0|max:600', 'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);
        $json = function (string $k) use ($d) {
            if (! filled($d[$k] ?? null)) {
                return null;
            }
            $v = json_decode($d[$k], true);
            abort_unless(is_array($v), 422, 'ساختار ذخیره‌شده‌ی «'.$k.'» معتبر نیست.');

            return $v;
        };
        $data = collect($d)->except(['keywords_text', 'scenes_json', 'story_specs_json', 'plan_json', 'prompt_json', 'product', 'cover_image', 'cover_hl', 'cover_kicker', 'music_start', 'hook_family', 'narrative'])->all();
        if (array_key_exists('keywords_text', $d)) {
            $data['keywords'] = array_values(array_filter(array_map('trim', preg_split('/[،,]/u', (string) $d['keywords_text']))));
        }
        if (($v = $json('scenes_json')) !== null) {
            $data['scenes'] = array_values($v);
            $data['duration_sec'] = $d['duration_sec'] ?? (int) round(collect($v)->sum('dur') / 30);
        }
        if (($v = $json('story_specs_json')) !== null) {
            $data['story_specs'] = array_values(array_slice($v, 0, 3));
            $data['stories'] = collect($data['story_specs'])->pluck('title')->filter()->values()->all();
        }
        $plan = (array) ($item->ai_plan ?? []);
        if (($v = $json('plan_json')) !== null) {
            $plan['steps'] = array_values($v);
        }
        foreach (['hook_family', 'narrative'] as $k) {
            if (array_key_exists($k, $d)) {
                $plan[$k] = $d[$k];
            }
        }
        $data['ai_plan'] = $plan;
        if (($v = $json('prompt_json')) !== null) {
            $data['build_prompt'] = ['system' => (string) ($v['system'] ?? ''), 'custom' => true,
                'sections' => collect($v['sections'] ?? [])->filter(fn ($s) => filled($s['title'] ?? null) || filled($s['body'] ?? null))
                    ->map(fn ($s) => ['title' => (string) ($s['title'] ?? 'بخش'), 'body' => (string) ($s['body'] ?? ''), 'on' => (bool) ($s['on'] ?? true)])->values()->all()];
        }
        $assets = (array) ($item->assets ?? []);
        foreach (['cover_image', 'cover_kicker', 'music_start'] as $k) {
            if (array_key_exists($k, $d)) {
                $assets[$k] = $k === 'music_start' ? (float) $d[$k] : $d[$k];
            }
        }
        if (array_key_exists('cover_hl', $d)) {
            $assets['cover_hl'] = array_values(array_filter(array_map('trim', preg_split('/[،,]/u', (string) $d['cover_hl']))));
        }
        $data['assets'] = $assets;
        if (filled($d['product'] ?? null)) {
            [$type, $pid] = array_pad(explode(':', $d['product'], 2), 2, null);
            $opt = collect(Catalog::products())->firstWhere('value', $d['product']);
            $data += ['product_type' => $type, 'product_id' => is_numeric($pid) ? (int) $pid : null, 'product_key' => is_numeric($pid) ? null : $pid, 'product_label' => $opt['label'] ?? $item->product_label];
            if (! filled($d['product_url'] ?? null) && ! empty($opt['url'])) {
                $data['product_url'] = $opt['url'];
            }
        }
        if (array_key_exists('style_id', $d)) {
            $st = $d['style_id'] ? Style::find($d['style_id']) : null;
            $data['style_slug'] = $st?->slug;
            $data['style'] = $st?->name_fa;
        }
        $result = (array) ($item->result ?? []);
        $result['edits'] = array_slice(array_merge([['by' => $this->adminName(), 'at' => now()->toIso8601String()]], (array) ($result['edits'] ?? [])), 0, 20);
        $data['result'] = $result;
        $item->fill($data)->save();
        if ($item->production && array_key_exists('caption', $d)) {
            $item->production->update(['caption' => $d['caption'], 'hashtags' => $d['hashtags'] ?? $item->production->hashtags]);
        }
        if ($item->production && in_array($item->production->status, ['queued', 'review', 'revision'], true)) {
            app(Builder::class)->production($item->fresh());
        }
        if ($request->input('then') === 'build') {
            return $this->build($request, $item->fresh(), app(Builder::class));
        }

        return back()->with('success', "پست {$item->code} ذخیره شد.");
    }

    public function build(Request $request, CalendarItem $item, Builder $builder)
    {
        $this->own($item);
        if (empty($item->scenes)) {
            return back()->with('warning', 'برای این پست هنوز صحنه‌ای تعریف نشده است.');
        }
        $running = Job::where('production_id', $item->production?->id ?? 0)->where('type', 'render')->whereIn('status', ['queued', 'running'])->exists();
        if ($running) {
            return back()->with('warning', 'ساخت این پست همین الان در صف یا در حال اجراست.');
        }
        $job = $builder->build($item, $this->adminName(), $request->boolean('regen_caption'));
        JobController::spawnLocal();

        return redirect()->route('cf.item', $item)->with('success', 'سفارش ساخت ثبت شد (کار #'.$job->id.'). '
            .(JobController::agentOnline() ? 'ایجنت مک در حال ساخت است.' : 'ایجنت مک الان آنلاین نیست؛ به‌محض روشن شدن ساخته می‌شود.'));
    }

    public function generate(Request $request, CalendarItem $item, MasterPrompt $master)
    {
        $this->own($item);
        $r = $master->generate($item, $this->adminName(), (string) $request->input('where', 'auto'));
        if (($r['where'] ?? '') === 'mac') {
            JobController::spawnLocal();
        }

        return back()->with($r['ok'] ? 'success' : 'warning', $r['message']);
    }

    public function undo(CalendarItem $item, MasterPrompt $master)
    {
        $this->own($item);

        $ok = $master->undo($item);

        return back()->with($ok ? 'success' : 'warning', $ok ? 'صحنه‌ها، استوری و کپشن به نسخه‌ی قبل از بازنویسی برگشت.' : 'نسخه‌ی قبلی برای بازگشت نیست.');
    }

    public function promptReset(CalendarItem $item)
    {
        $this->own($item);
        $item->update(['build_prompt' => null]);

        return back()->with('success', 'پرامپت این پست به قالب اصلی (آزمایشگاه پرامپت) برگشت.');
    }

    /** ثبت دستی آمار (برای شاخص‌هایی که API نمی‌دهد: بازدید، فالو، کلیک لینک، ثبت‌نام…) */
    public function metrics(Request $request, CalendarItem $item, Scorer $scorer)
    {
        $this->own($item);
        $p = $item->production;
        abort_unless($p, 422, 'هنوز تولیدی برای این پست نیست.');
        $keys = array_keys(config('content-factory.metric_fields'));
        $d = $request->validate(['window' => 'required|string|max:10'] + collect($keys)->mapWithKeys(fn ($k) => [$k => 'nullable|numeric|min:0'])->all());
        $snap = MetricSnapshot::firstOrNew(['production_id' => $p->id, 'window' => $d['window']]);
        $metrics = array_merge((array) ($snap->metrics ?? []), array_filter(collect($d)->only($keys)->all(), fn ($v) => $v !== null && $v !== ''));
        $snap->fill(['captured_at' => now(), 'metrics' => array_map(fn ($v) => is_numeric($v) ? $v + 0 : $v, $metrics), 'source' => $snap->exists ? ($snap->source.'+manual') : 'manual'])->save();
        $scorer->score($p->fresh());

        return back()->with('success', 'آمار بازه‌ی '.$d['window'].' ذخیره و امتیاز دوباره محاسبه شد.');
    }

    public function snapshot(CalendarItem $item, InstagramBridge $bridge)
    {
        $this->own($item);
        $p = $item->production;
        abort_unless($p, 422);
        $n = $bridge->snapshotNow($p);

        return back()->with($n ? 'success' : 'warning', $n ? 'آمار لحظه‌ای از اینستاگرام هوشمند برداشته شد.' : 'پست در اینستاگرام هوشمند پیدا نشد؛ لینک پست را ثبت کنید.');
    }

    /** وضعیت زنده برای صفحه (کارها، نسخه و پیشرفت) */
    public function status(CalendarItem $item)
    {
        $this->own($item);
        $p = $item->production()->first();
        $jobs = Job::where('workspace_id', $this->ws())->where(fn ($q) => $q->where('production_id', $p?->id ?? 0)
            ->orWhere(fn ($q) => $q->where('type', 'ai_write')->where('payload->item_id', $item->id)))->latest('id')->limit(8)->get();

        return [
            'production' => $p ? ['status' => $p->status, 'status_fa' => Fa::label('production_status', $p->status), 'version' => $p->current_version] : null,
            'agent' => JobController::agentOnline(),
            'jobs' => $jobs->map(fn ($j) => ['id' => $j->id, 'type' => Job::TYPES[$j->type] ?? $j->type, 'status' => $j->status, 'status_fa' => Job::STATUSES[$j->status] ?? $j->status,
                'progress' => $j->progress, 'message' => $j->message, 'at' => Fa::digits($j->updated_at?->locale('fa')->diffForHumans())]),
            'busy' => $jobs->whereIn('status', ['queued', 'running'])->isNotEmpty(),
        ];
    }
}
