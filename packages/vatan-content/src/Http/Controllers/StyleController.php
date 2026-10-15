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
        'recipe_text' => 'nullable|string|max:4000',
    ];

    public function index(Request $request)
    {
        $ws = $this->ws();

        return view('cf::styles.index', [
            'cfTitle' => 'بانک سبک‌های ویدیو', 'cfIcon' => 'fa-swatchbook', 'cfHelp' => 'styles',
            'cfSubtitle' => 'هر سبک یک شناسنامه‌ی کامل دارد و به یک قالب رندر وصل است. ویدیوی مرجع را در پوشه‌ی مک بریزید و «تحلیل» را بزنید.',
            'styles' => Style::where('workspace_id', $ws)->with('references')->orderBy('sort')->orderBy('id')->get(),
            'orphans' => Reference::where('workspace_id', $ws)->whereNull('style_id')->get(),
            'themes' => ['punch' => 'پانچ تضادی', 'editorial' => 'ادیتوریال روشن', 'kinetic' => 'تایپوگرافی ضرب‌آهنگی', 'techgrid' => 'گرید فنی', 'stage' => 'کارت روی صحنه', 'tactile' => 'میز کار لمسی'],
            'open' => $request->get('style'),
        ]);
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
        unset($d['goals_text'], $d['sectors_text'], $d['recipe_text']);

        return $d;
    }

    public function store(Request $request)
    {
        $d = $this->data($request);
        $slug = Str::slug($d['name_en'] ?? '') ?: 'style-'.Str::lower(Str::random(5));
        $style = Style::create($d + ['workspace_id' => $this->ws(), 'slug' => $slug, 'folder' => $slug, 'status' => $d['status'] ?? 'testing',
            'template' => $d['template'] ?? 'punch', 'sort' => (int) Style::where('workspace_id', $this->ws())->max('sort') + 1]);

        return redirect()->route('cf.styles', ['style' => $style->id])->with('success', "سبک «{$style->name_fa}» اضافه شد. ایجنت مک پوشه‌ی آن را می‌سازد.");
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
