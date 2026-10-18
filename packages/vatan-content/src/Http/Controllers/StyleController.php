<?php

namespace Vatan\Content\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Vatan\Content\Models\Reference;
use Vatan\Content\Models\Style;
use Vatan\Content\Services\Builder;

class StyleController extends Controller
{
    public const FIELDS = [
        'name_fa' => 'required|string|max:255', 'name_en' => 'nullable|string|max:255', 'description' => 'nullable|string|max:4000',
        'duration' => 'nullable|string|max:30', 'aspect' => 'nullable|string|max:10', 'shots' => 'nullable|string|max:30', 'motion' => 'nullable|string|max:255',
        'text_style' => 'nullable|string|max:255', 'hook_family' => 'nullable|string|max:255', 'cta_family' => 'nullable|string|max:255',
        'music_hint' => 'nullable|string|max:255', 'engine' => 'nullable|string|max:255', 'template' => 'nullable|in:punch,editorial,kinetic,techgrid,stage,tactile',
        'status' => 'nullable|in:pending,active,testing,archived,locked', 'goals_text' => 'nullable|string|max:500', 'sectors_text' => 'nullable|string|max:500',
        'recipe_text' => 'nullable|string|max:4000', 'palette_text' => 'nullable|string|max:500', 'fonts_text' => 'nullable|string|max:300',
        'cover' => 'nullable|image|max:8192', 'sort' => 'nullable|integer|min:0|max:9999',
    ];

    public const THEMES = ['punch' => 'پانچ تضادی', 'editorial' => 'ادیتوریال روشن', 'kinetic' => 'تایپوگرافی ضرب‌آهنگی', 'techgrid' => 'گرید فنی', 'stage' => 'کارت روی صحنه', 'tactile' => 'میز کار لمسی'];

    public function index(Request $request)
    {
        $ws = $this->ws();
        $all = Style::where('workspace_id', $ws)->withCount('references')->with(['references' => fn ($q) => $q->whereNotNull('sheet_path')->limit(1)])->orderBy('sort')->orderBy('id')->get();
        $status = $request->get('status');
        $usage = \Vatan\Content\Models\CalendarItem::where('workspace_id', $ws)->whereNotNull('style_id')->selectRaw('style_id, count(*) c')->groupBy('style_id')->pluck('c', 'style_id');

        return view('cf::styles.index', [
            'cfTitle' => 'بانک سبک‌های ویدیو', 'cfIcon' => 'fa-swatchbook', 'cfHelp' => 'styles',
            'cfSubtitle' => 'هر سبک یک شناسنامه‌ی کامل دارد و به یک قالب رندر وصل است. روی هر کارت بزنید تا جزئیات و ویرایش باز شود.',
            'styles' => $status ? $all->where('status', $status)->values() : $all, 'all' => $all, 'status' => $status, 'usage' => $usage,
            'orphans' => Reference::where('workspace_id', $ws)->whereNull('style_id')->get(),
            'themes' => self::THEMES,
        ]);
    }

    public function create()
    {
        return view('cf::styles.show', ['cfTitle' => 'سبک جدید', 'cfIcon' => 'fa-swatchbook', 'cfHelp' => 'style.show', 's' => null, 'themes' => self::THEMES,
            'styles' => collect(), 'items' => collect(), 'disk' => \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk', 'public'))]);
    }

    public function show(Style $style)
    {
        abort_unless($style->workspace_id === $this->ws(), 404);
        $style->load('references');

        return view('cf::styles.show', [
            'cfTitle' => $style->name_fa, 'cfIcon' => 'fa-swatchbook', 'cfHelp' => 'style.show', 'cfSubtitle' => trim(($style->name_en ? $style->name_en.' · ' : '').'01-styles/'.($style->folder ?: $style->slug)),
            's' => $style, 'themes' => self::THEMES, 'styles' => Style::where('workspace_id', $this->ws())->orderBy('sort')->get(['id', 'name_fa']),
            'items' => \Vatan\Content\Models\CalendarItem::where('workspace_id', $this->ws())->where('style_id', $style->id)->with('production')->orderBy('publish_date')->get(),
            'disk' => \Illuminate\Support\Facades\Storage::disk(config('content-factory.disk', 'public')),
        ]);
    }

    public function destroy(Style $style)
    {
        abort_unless($style->workspace_id === $this->ws(), 404);
        $name = $style->name_fa;
        Reference::where('style_id', $style->id)->update(['style_id' => null, 'status' => 'pending']);
        \Vatan\Content\Models\CalendarItem::where('style_id', $style->id)->update(['style_id' => null]);
        $style->delete();
        \Vatan\Content\Models\Job::create(['workspace_id' => $this->ws(), 'type' => 'sync_files', 'location' => 'mac', 'status' => 'queued', 'created_by' => $this->adminName(), 'payload' => []]);

        return redirect()->route('cf.styles')->with('success', "سبک «{$name}» حذف شد؛ مرجع‌هایش به «بدون سبک» رفتند.");
    }

    public function duplicate(Style $style)
    {
        abort_unless($style->workspace_id === $this->ws(), 404);
        $copy = $style->replicate(['use_count', 'score', 'last_used_at']);
        $copy->fill(['name_fa' => $style->name_fa.' (کپی)', 'slug' => $style->slug.'-'.Str::lower(Str::random(4)), 'status' => 'testing', 'sort' => $style->sort + 1]);
        $copy->folder = $copy->slug;
        $copy->save();

        return redirect()->route('cf.styles.show', $copy)->with('success', 'کپی سبک ساخته شد؛ تغییرش دهید.');
    }

    public function deleteReference(Reference $reference)
    {
        abort_unless($reference->workspace_id === $this->ws(), 404);
        $reference->delete();

        return back()->with('success', 'مرجع از بانک حذف شد (فایل روی مک می‌ماند).');
    }

    private function data(Request $request): array
    {
        $d = $request->validate(self::FIELDS);
        $split = fn ($t) => array_values(array_filter(array_map('trim', preg_split('/[،,]/u', (string) $t))));
        if (array_key_exists('goals_text', $d)) {
            $d['goals'] = $split($d['goals_text']);
        }
        if (array_key_exists('sectors_text', $d)) {
            $d['sectors'] = $split($d['sectors_text']);
        }
        if (array_key_exists('recipe_text', $d)) {
            $d['recipe'] = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $d['recipe_text']))));
        }
        if (array_key_exists('palette_text', $d)) {
            $d['palette'] = array_values(array_filter(array_map('trim', preg_split('/[،,\s]+/u', (string) $d['palette_text'])), fn ($c) => preg_match('/^#[0-9a-f]{3,8}$/i', $c)));
        }
        if (array_key_exists('fonts_text', $d)) {
            $d['fonts'] = $split($d['fonts_text']);
        }
        if ($request->hasFile('cover')) {
            $d['cover_path'] = $request->file('cover')->store(trim(config('content-factory.disk_root', 'content-factory'), '/').'/styles', config('content-factory.disk', 'public'));
        }
        unset($d['goals_text'], $d['sectors_text'], $d['recipe_text'], $d['palette_text'], $d['fonts_text'], $d['cover']);

        return $d;
    }

    public function store(Request $request)
    {
        $d = $this->data($request);
        $slug = Str::slug($d['name_en'] ?? '') ?: 'style-'.Str::lower(Str::random(5));
        $style = Style::create($d + ['workspace_id' => $this->ws(), 'slug' => $slug, 'folder' => $slug, 'status' => $d['status'] ?? 'testing',
            'template' => $d['template'] ?? 'punch', 'sort' => (int) Style::where('workspace_id', $this->ws())->max('sort') + 1]);

        return redirect()->route('cf.styles.show', $style)->with('success', "سبک «{$style->name_fa}» اضافه شد. ایجنت مک پوشه‌ی آن را می‌سازد.");
    }

    public function update(Request $request, Style $style)
    {
        abort_unless($style->workspace_id === $this->ws(), 404);
        if ($request->has('only_status')) {
            $style->update($request->validate(['status' => 'required|in:pending,active,testing,archived,locked']));
        } else {
            $style->update($this->data($request));
        }

        return back()->with('success', "سبک «{$style->name_fa}» به‌روز شد.");
    }

    /** انتقال دستی یک مرجع به سبک دیگر (ایجنت فایل را در پوشه‌ی جدید جابه‌جا می‌کند) */
    public function assign(Request $request, Reference $reference)
    {
        abort_unless($reference->workspace_id === $this->ws(), 404);
        $d = $request->validate(['style_id' => 'nullable|integer']);
        $reference->update(['style_id' => $d['style_id'] ?: null, 'status' => 'classified', 'style_confidence' => 'تأیید ادمین']);
        \Vatan\Content\Models\Job::create(['workspace_id' => $this->ws(), 'type' => 'sync_files', 'location' => 'mac', 'status' => 'queued', 'created_by' => $this->adminName(), 'payload' => []]);

        return back()->with('success', 'مرجع جابه‌جا شد.');
    }
}
