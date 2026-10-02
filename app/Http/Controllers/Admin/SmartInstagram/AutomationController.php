<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\Admin;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Services\SmartInstagram\Automation\AutomationEngine;
use App\Services\SmartInstagram\Automation\AutomationTemplates;
use App\Services\SmartInstagram\MetricsService;
use App\Services\SmartInstagram\OperationLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** سازنده‌ی اتومیشن و سناریو (پروپوزال ۵.۷) — نسخه‌دار، با حالت آزمایشی و حفاظ ایمنی. */
class AutomationController extends Controller
{
    public const STATUSES = ['draft' => 'پیش‌نویس', 'test' => 'آزمایشی', 'active' => 'فعال', 'paused' => 'متوقف'];

    public function index(Request $request): View
    {
        $this->authorizeAbility('view');
        $rules = AutomationRule::query()->where('workspace_id', $this->ws())->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'test' THEN 1 WHEN 'draft' THEN 2 ELSE 3 END")->orderBy('priority')->get();
        $recentRuns = AutomationRun::query()->where('workspace_id', $this->ws())->with(['rule:id,name', 'contact:id,username,display_name'])->latest('id')->limit(15)->get();

        return view('admin.smart-instagram.automations.index', [
            'rules' => $rules,
            'recentRuns' => $recentRuns,
            'templates' => AutomationTemplates::all(),
            'statuses' => self::STATUSES,
            'triggers' => AutomationEngine::TRIGGERS,
            'canManage' => $this->context->can($this->admin(), 'manage_automation'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAbility('manage_automation');
        $template = AutomationTemplates::all()[(string) $request->query('template')] ?? null;
        $rule = new AutomationRule($template ? collect($template)->only(['name', 'trigger', 'keywords', 'match_mode', 'actions', 'guards'])->all() + ['status' => 'test'] : [
            'trigger' => 'comment_keyword', 'match_mode' => 'contains', 'status' => 'draft', 'keywords' => [],
            'actions' => [['type' => 'private_reply', 'text' => '']], 'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 720, 'stop_on_sensitive' => true],
        ]);

        if ($request->filled('scope')) {
            $rule->scope_ref = mb_substr((string) $request->query('scope'), 0, 120);
            $rule->name = trim(($rule->name ?: 'سناریو').' — محتوای اختصاصی');
        }

        return $this->form($rule);
    }

    public function edit(AutomationRule $rule): View
    {
        $this->authorizeAbility('manage_automation');
        $this->own($rule);

        return $this->form($rule);
    }

    public function show(AutomationRule $rule): View
    {
        $this->authorizeAbility('view');
        $this->own($rule);

        return view('admin.smart-instagram.automations.show', [
            'rule' => $rule,
            'runs' => $rule->runs()->with('contact:id,username,display_name')->latest('id')->paginate(25),
            'statuses' => self::STATUSES,
            'triggers' => AutomationEngine::TRIGGERS,
            'actionsMap' => AutomationEngine::ACTIONS,
        ]);
    }

    public function store(Request $request, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $data = $this->validated($request);
        $rule = AutomationRule::query()->create($data + [
            'workspace_id' => $this->ws(),
            'version' => 1,
            'created_by' => $this->admin()?->id,
            'updated_by' => $this->admin()?->id,
        ]);
        $logger->log('automation.created', 'قانون «'.$rule->name.'» ساخته شد ('.self::STATUSES[$rule->status].').', $rule);

        return redirect()->route('admin.smart-instagram.automations.show', $rule)->with('success', 'قانون ذخیره شد. پیش از فعال‌سازی، آن را در حالت «آزمایشی» بسنجید.');
    }

    public function update(Request $request, AutomationRule $rule, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($rule);
        $data = $this->validated($request);
        $behaviour = ['trigger', 'scope_ref', 'keywords', 'match_mode', 'conditions', 'actions', 'guards'];
        $changed = collect($behaviour)->contains(fn ($key) => json_encode($rule->{$key}) !== json_encode($data[$key] ?? null));

        $rule->fill($data + ['updated_by' => $this->admin()?->id]);
        if ($changed) {
            $rule->version++;
        }
        $rule->save();
        $logger->log('automation.updated', 'قانون «'.$rule->name.'» ویرایش شد'.($changed ? ' — نسخه‌ی '.$rule->version : '').'.', $rule);

        return redirect()->route('admin.smart-instagram.automations.show', $rule)->with('success', 'تغییرات ذخیره شد'.($changed ? ' (نسخه‌ی '.$rule->version.')' : '').'.');
    }

    public function status(Request $request, AutomationRule $rule, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($rule);
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_keys(self::STATUSES))]]);
        $rule->forceFill(['status' => $data['status'], 'updated_by' => $this->admin()?->id, 'last_error' => $data['status'] === 'active' ? null : $rule->last_error])->save();
        $logger->log('automation.status', 'وضعیت قانون «'.$rule->name.'» → '.self::STATUSES[$data['status']], $rule);
        app(MetricsService::class)->forget();

        return back()->with('success', 'وضعیت قانون: '.self::STATUSES[$data['status']]);
    }

    public function simulate(Request $request, AutomationRule $rule, AutomationEngine $engine): JsonResponse
    {
        $this->authorizeAbility('view');
        $this->own($rule);
        $data = $request->validate([
            'text' => ['required', 'string', 'max:500'],
            'source' => ['required', 'in:dm,comment,story_reply,mention,ad'],
            'new_contact' => ['nullable', 'boolean'],
            'scope_ref' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json($engine->simulate($rule, $data['text'], $data['source'], $request->boolean('new_contact', true), $data['scope_ref'] ?? null));
    }

    public function destroy(AutomationRule $rule, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('manage_automation');
        $this->own($rule);
        $name = $rule->name;
        $rule->delete();
        $logger->log('automation.deleted', 'قانون «'.$name.'» حذف شد.', null, [], 'warning');

        return redirect()->route('admin.smart-instagram.automations.index')->with('success', 'قانون حذف شد.');
    }

    private function form(AutomationRule $rule): View
    {
        return view('admin.smart-instagram.automations.form', [
            'rule' => $rule,
            'statuses' => self::STATUSES,
            'triggers' => AutomationEngine::TRIGGERS,
            'actionsMap' => AutomationEngine::ACTIONS,
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'trigger' => ['required', 'in:'.implode(',', array_keys(AutomationEngine::TRIGGERS))],
            'scope_ref' => ['nullable', 'string', 'max:120'],
            'keywords' => ['nullable', 'string', 'max:1000'],
            'match_mode' => ['required', 'in:contains,word,exact'],
            'status' => ['required', 'in:draft,test,active,paused'],
            'priority' => ['nullable', 'integer', 'between:1,999'],
            'conditions.business_hours' => ['nullable', 'in:any,inside,outside'],
            'conditions.require_tag' => ['nullable', 'string', 'max:60'],
            'conditions.exclude_tag' => ['nullable', 'string', 'max:60'],
            'guards.max_per_contact_per_day' => ['nullable', 'integer', 'between:0,20'],
            'guards.cooldown_minutes' => ['nullable', 'integer', 'between:0,10080'],
            'guards.stop_on_sensitive' => ['nullable', 'boolean'],
            'actions' => ['required', 'array', 'min:1', 'max:10'],
            'actions.*.type' => ['required', 'in:'.implode(',', array_keys(AutomationEngine::ACTIONS))],
            'actions.*.text' => ['nullable', 'string', 'max:1000'],
            'actions.*.tag' => ['nullable', 'string', 'max:60'],
            'actions.*.admin_id' => ['nullable', 'integer'],
            'actions.*.stage' => ['nullable', 'string', 'max:30'],
            'actions.*.due_hours' => ['nullable', 'integer', 'between:1,720'],
        ]);

        $keywords = array_values(array_filter(array_map('trim', preg_split('/[\n،,]+/u', (string) ($data['keywords'] ?? '')) ?: [])));
        if (in_array($data['trigger'], ['comment_keyword', 'dm_keyword'], true) && !$keywords) {
            abort(back()->withInput()->withErrors(['keywords' => 'برای این شروع‌کننده حداقل یک کلمه‌ی کلیدی لازم است.']));
        }

        $actions = collect($data['actions'])->map(fn ($a) => array_filter([
            'type' => $a['type'],
            'text' => isset($a['text']) ? trim((string) $a['text']) : null,
            'tag' => isset($a['tag']) ? trim((string) $a['tag']) : null,
            'admin_id' => isset($a['admin_id']) ? (int) $a['admin_id'] : null,
            'stage' => $a['stage'] ?? null,
            'due_hours' => isset($a['due_hours']) ? (int) $a['due_hours'] : null,
        ], fn ($v) => $v !== null && $v !== ''))->values();

        foreach ($actions as $i => $action) {
            if (in_array($action['type'], ['public_reply', 'private_reply', 'send_dm'], true) && empty($action['text'])) {
                abort(back()->withInput()->withErrors(["actions.$i.text" => 'متن پیام برای اقدام «'.AutomationEngine::ACTIONS[$action['type']].'» لازم است.']));
            }
            if (in_array($action['type'], ['public_reply', 'private_reply'], true) && $data['trigger'] !== 'comment_keyword') {
                abort(back()->withInput()->withErrors(["actions.$i.type" => 'پاسخ به کامنت فقط با شروع‌کننده‌ی «کلمه‌ی کلیدی در کامنت» ممکن است.']));
            }
        }

        return [
            'name' => $data['name'],
            'trigger' => $data['trigger'],
            'scope_ref' => ($data['scope_ref'] ?? null) ?: null,
            'keywords' => $keywords,
            'match_mode' => $data['match_mode'],
            'status' => $data['status'],
            'priority' => (int) ($data['priority'] ?? 100),
            'conditions' => [
                'business_hours' => $data['conditions']['business_hours'] ?? 'any',
                'require_tag' => ($data['conditions']['require_tag'] ?? null) ?: null,
                'exclude_tag' => ($data['conditions']['exclude_tag'] ?? null) ?: null,
            ],
            'guards' => [
                'max_per_contact_per_day' => (int) ($data['guards']['max_per_contact_per_day'] ?? 1),
                'cooldown_minutes' => (int) ($data['guards']['cooldown_minutes'] ?? 0),
                'stop_on_sensitive' => $request->boolean('guards.stop_on_sensitive'),
            ],
            'actions' => $actions->all(),
        ];
    }

    private function own(AutomationRule $rule): void
    {
        abort_unless((int) $rule->workspace_id === $this->ws(), 404);
    }
}
