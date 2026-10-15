<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vatan\Content\Models\Calendar;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\Production;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $ws = $this->ws();
        $calendars = Calendar::where('workspace_id', $ws)->latest('id')->get();
        $calendar = $request->filled('c') ? $calendars->firstWhere('id', (int) $request->c) : $calendars->first();
        $items = $calendar ? $calendar->items()->with(['production', 'styleModel', 'musicTrack'])->get() : collect();
        $filters = ['format' => $request->get('format'), 'pillar' => $request->get('pillar'), 'sector' => $request->get('sector')];
        $filtered = $items->filter(fn ($i) => (! $filters['format'] || $i->format === $filters['format'])
            && (! $filters['pillar'] || $i->pillar === $filters['pillar']) && (! $filters['sector'] || $i->sector === $filters['sector']));

        return view('cf::calendar.index', [
            'cfTitle' => 'تقویم محتوایی هوشمند', 'cfIcon' => 'fa-calendar-days', 'cfHelp' => 'calendar',
            'styles' => \Vatan\Content\Models\Style::where('workspace_id', $ws)->where('status', '!=', 'archived')->orderBy('sort')->get(['id', 'name_fa', 'slug', 'template', 'description']),
            'tracks' => \Vatan\Content\Models\MusicTrack::where('workspace_id', $ws)->orderBy('title')->get(['id', 'title', 'mood', 'tempo_bpm', 'license_status', 'audio_path']),
            'catalog' => \Vatan\Content\Support\Catalog::products(),
            'cfSubtitle' => 'مرحله‌ی صفر اجباری: هیچ محتوایی پیش از تأیید و قفل تقویم وارد صف تولید نمی‌شود.',
            'calendars' => $calendars, 'calendar' => $calendar, 'items' => $items, 'filtered' => $filtered, 'filters' => $filters,
            'view' => $request->get('view', 'calendar'),
            'balance' => $this->balance($items),
        ]);
    }

    /** کنترل توازن پیش از تأیید */
    private function balance($items): array
    {
        $by = fn ($k) => $items->groupBy($k)->map->count()->sortDesc();
        $warnings = [];
        $sectors = $by('sector');
        foreach (['پوشاک و مزون', 'کیف و کفش', 'آرایشی و عطر', 'طلا و جواهر'] as $s) {
            if (($sectors[$s] ?? 0) < 3) {
                $warnings[] = "صنف «{$s}» کمتر از ۳ محتوای اختصاصی دارد.";
            }
        }
        $prev = null;
        foreach ($items as $i) {
            if ($prev && $prev->pillar === $i->pillar && $prev->format === $i->format && $prev->sector === $i->sector) {
                $warnings[] = "{$prev->code} و {$i->code} پشت‌سرهم ستون، قالب و صنف یکسان دارند.";
            }
            $prev = $i;
        }
        $styleUse = $items->whereNotNull('style')->groupBy('style')->map->count()->filter(fn ($c) => $c > max(8, $items->count() * 0.55));
        foreach ($styleUse as $s => $c) {
            $warnings[] = "سبک «{$s}» {$c} بار تکرار شده است.";
        }

        return ['format' => $by('format'), 'pillar' => $by('pillar'), 'sector' => $sectors, 'goal' => $by('goal'),
            'stories' => $items->sum(fn ($i) => count($i->stories ?? [])), 'warnings' => $warnings];
    }

    public function updateItem(Request $request, CalendarItem $item)
    {
        abort_unless($item->workspace_id === $this->ws(), 404);
        if ($item->calendar->isLocked() && ! $request->boolean('force_locked')) {
            return back()->with('warning', 'این تقویم قفل است. برای تغییر، نسخه‌ی جدید بسازید.');
        }
        $data = $request->validate([
            'publish_date' => 'nullable|date', 'publish_time' => 'nullable|string|max:10', 'format' => 'nullable|in:ریلز,اسلایدی,پست',
            'pillar' => 'nullable|string|max:60', 'sector' => 'nullable|string|max:80', 'goal' => 'nullable|string|max:60', 'audience' => 'nullable|string|max:255',
            'idea' => 'nullable|string|max:3000', 'hook' => 'nullable|string|max:255', 'cta' => 'nullable|string|max:255', 'cover_text' => 'nullable|string|max:255',
            'style_id' => 'nullable|integer', 'music_id' => 'nullable|integer', 'engine' => 'nullable|string|max:255', 'build_location' => 'nullable|in:mac,server',
            'product' => 'nullable|string|max:200', 'product_url' => 'nullable|string|max:255', 'note' => 'nullable|string|max:2000', 'experiment' => 'nullable|string|max:255',
            'stories_text' => 'nullable|string|max:3000', 'caption' => 'nullable|string|max:5000', 'hashtags' => 'nullable|string|max:1000',
            'keywords_text' => 'nullable|string|max:300', 'visual_prompt' => 'nullable|string|max:3000', 'scenes_json' => 'nullable|string|max:60000',
            'story_specs_json' => 'nullable|string|max:20000', 'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);
        if (array_key_exists('stories_text', $data)) {
            $data['stories'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $data['stories_text']))));
        }
        if (array_key_exists('keywords_text', $data)) {
            $data['keywords'] = array_values(array_filter(array_map('trim', preg_split('/[،,]/u', (string) $data['keywords_text']))));
        }
        foreach (['scenes_json' => 'scenes', 'story_specs_json' => 'story_specs'] as $in => $col) {
            if (filled($data[$in] ?? null)) {
                $dec = json_decode($data[$in], true);
                if (! is_array($dec)) {
                    return back()->with('error', 'ساختار JSON «'.($col === 'scenes' ? 'صحنه‌ها' : 'استوری‌ها').'» معتبر نیست.');
                }
                $data[$col] = $dec;
            }
        }
        if (filled($data['product'] ?? null)) {
            [$type, $pid] = array_pad(explode(':', $data['product'], 2), 2, null);
            $opt = collect(\Vatan\Content\Support\Catalog::products())->firstWhere('value', $data['product']);
            $data += ['product_type' => $type, 'product_id' => is_numeric($pid) ? (int) $pid : null, 'product_key' => is_numeric($pid) ? null : $pid, 'product_label' => $opt['label'] ?? $item->product_label];
            if (! filled($data['product_url'] ?? null) && ! empty($opt['url'])) {
                $data['product_url'] = $opt['url'];
            }
        }
        if (isset($data['style_id'])) {
            $st = \Vatan\Content\Models\Style::find($data['style_id']);
            $data['style_slug'] = $st?->slug;
            $data['style'] = $st?->name_fa;
        }
        unset($data['stories_text'], $data['keywords_text'], $data['scenes_json'], $data['story_specs_json'], $data['product']);
        $item->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return back()->with('success', "ردیف {$item->code} ذخیره شد.");
    }

    /** دکمه‌ی «ساخت»: بدون معطلی وارد صف ساخت ایجنت مک می‌شود */
    public function build(Request $request, CalendarItem $item, \Vatan\Content\Services\Builder $builder)
    {
        abort_unless($item->workspace_id === $this->ws(), 404);
        if ($item->approval_status === 'rejected') {
            return back()->with('warning', 'این ردیف از تقویم حذف شده است.');
        }
        if (empty($item->scenes)) {
            return back()->with('warning', 'برای این ردیف هنوز صحنه‌ای تعریف نشده است.');
        }
        $job = $builder->build($item, $this->adminName(), $request->boolean('regen_caption'));
        \Vatan\Content\Http\Controllers\JobController::spawnLocal();

        return redirect()->route('cf.productions.show', $job->production_id)->with('success', 'ساخت شروع شد ('.$job->message.'). '
            .(\Vatan\Content\Http\Controllers\JobController::agentOnline() ? 'ایجنت مک در حال رندر است.' : '⚠️ ایجنت مک آنلاین نیست؛ با روشن شدنش رندر می‌شود.'));
    }

    public function approve(Calendar $calendar)
    {
        abort_unless($calendar->workspace_id === $this->ws(), 404);
        if ($calendar->isLocked()) {
            return back()->with('warning', 'این تقویم قبلاً تأیید و قفل شده است.');
        }
        $created = 0;
        DB::transaction(function () use ($calendar, &$created) {
            $calendar->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $this->adminName()]);
            Calendar::where('workspace_id', $calendar->workspace_id)->where('id', '!=', $calendar->id)->where('status', 'approved')->update(['status' => 'archived']);
            foreach ($calendar->items as $item) {
                if ($item->approval_status !== 'rejected') {
                    $item->approval_status = 'approved';
                }
                $item->save();
                if ($item->approval_status === 'approved' && ! $item->production) {
                    // اگر این ردیف در نسخه‌ی قبلی تقویم تولید داشته، همان را به ردیف جدید وصل کن (تولید تکراری نساز)
                    $existing = Production::where('workspace_id', $item->workspace_id)->where('code', 'like', $item->code.'-%')
                        ->where('status', '!=', 'rejected')->latest('id')->first();
                    if ($existing) {
                        $existing->update(['calendar_item_id' => $item->id]);

                        continue;
                    }
                    Production::firstOrCreate(['workspace_id' => $item->workspace_id, 'code' => $item->code.'-'.$calendar->version], [
                        'calendar_item_id' => $item->id, 'title' => (string) $item->hook, 'format' => $item->format,
                        'style_slug' => $item->style_slug, 'status' => 'queued',
                        'gene' => ['pillar' => $item->pillar, 'sector' => $item->sector, 'goal' => $item->goal, 'hook' => $item->hook,
                            'cta' => $item->cta, 'style' => $item->style, 'music_mood' => $item->music_mood, 'prompt_version' => $item->prompt_version,
                            'publish_at' => $item->publish_date?->toDateString().' '.$item->publish_time],
                    ]);
                    $created++;
                }
            }
        });

        return back()->with('success', "تقویم تأیید و قفل شد؛ {$created} آیتم وارد صف تولید شد.");
    }

    public function newVersion(Calendar $calendar)
    {
        abort_unless($calendar->workspace_id === $this->ws(), 404);
        $n = Calendar::where('workspace_id', $calendar->workspace_id)->count() + 1;
        $copy = DB::transaction(function () use ($calendar, $n) {
            $copy = $calendar->replicate(['status', 'approved_at', 'approved_by']);
            $copy->fill(['version' => 'v'.$n, 'status' => 'draft', 'parent_id' => $calendar->id])->save();
            foreach ($calendar->items as $item) {
                $c = $item->replicate(['production_status', 'approval_status', 'result']);
                $c->calendar_id = $copy->id;
                $c->production_status = $item->production?->status ?? 'queued';
                $c->save();
            }

            return $copy;
        });

        return redirect()->route('cf.calendar', ['c' => $copy->id])->with('success', "نسخه‌ی {$copy->version} ساخته شد. بعد از ویرایش، دوباره تأیید کنید.");
    }
}
