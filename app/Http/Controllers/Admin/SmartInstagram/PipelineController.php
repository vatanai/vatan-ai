<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\Admin;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Deal;
use App\Models\SmartInstagram\Task;
use App\Services\SmartInstagram\MetricsService;
use App\Services\SmartInstagram\OperationLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** قیف فروش و وظایف (پروپوزال ۵.۸). */
class PipelineController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAbility('view');
        $tab = $request->query('tab') === 'tasks' ? 'tasks' : 'board';
        $stages = config('smart_instagram.pipeline_stages');

        $deals = Deal::query()->where('workspace_id', $this->ws())
            ->where(fn ($q) => $q->where('outcome', 'open')->orWhere('closed_at', '>=', now()->subDays(30)))
            ->with(['contact:id,username,display_name,lead_score', 'owner:id,name'])
            ->orderByDesc('updated_at')->limit(400)->get()->groupBy('stage');

        $summary = Deal::query()->where('workspace_id', $this->ws())->where('outcome', 'open')
            ->select('stage', DB::raw('COUNT(*) as total'), DB::raw('SUM(value_toman) as value'))->groupBy('stage')->get()->keyBy('stage');

        $taskFilter = in_array($request->query('due'), ['today', 'overdue', 'upcoming', 'done'], true) ? $request->query('due') : 'open';
        $tasks = Task::query()->where('workspace_id', $this->ws())
            ->when($taskFilter === 'open', fn ($q) => $q->where('status', 'open'))
            ->when($taskFilter === 'today', fn ($q) => $q->where('status', 'open')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]))
            ->when($taskFilter === 'overdue', fn ($q) => $q->where('status', 'open')->where('due_at', '<', now()))
            ->when($taskFilter === 'upcoming', fn ($q) => $q->where('status', 'open')->where('due_at', '>', now()->endOfDay()))
            ->when($taskFilter === 'done', fn ($q) => $q->where('status', 'done'))
            ->with(['contact:id,username,display_name', 'assignee:id,name'])
            ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')
            ->paginate(25, ['*'], 'tasks_page')->withQueryString();

        return view('admin.smart-instagram.pipeline', [
            'tab' => $tab,
            'stages' => $stages,
            'deals' => $deals,
            'summary' => $summary,
            'tasks' => $tasks,
            'taskFilter' => $taskFilter,
            'taskCounts' => [
                'today' => Task::query()->where('workspace_id', $this->ws())->where('status', 'open')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count(),
                'overdue' => Task::query()->where('workspace_id', $this->ws())->where('status', 'open')->where('due_at', '<', now())->count(),
            ],
            'admins' => Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'canManage' => $this->context->can($this->admin(), 'manage_sales'),
        ]);
    }

    public function storeDeal(Request $request, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $data = $request->validate([
            'contact_id' => ['required', 'integer'],
            'conversation_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:190'],
            'value_toman' => ['nullable', 'integer', 'min:0'],
            'stage' => ['nullable', 'in:'.implode(',', array_keys(config('smart_instagram.pipeline_stages')))],
        ]);
        $contact = Contact::query()->where('workspace_id', $this->ws())->findOrFail($data['contact_id']);
        $lastMessage = \App\Models\SmartInstagram\Message::query()->whereIn('conversation_id', $contact->conversations()->pluck('id'))
            ->where('direction', 'in')->oldest('occurred_at')->first(['source_type', 'source_ref']);

        $deal = Deal::query()->create([
            'workspace_id' => $this->ws(),
            'contact_id' => $contact->id,
            'conversation_id' => $data['conversation_id'] ?? null,
            'title' => $data['title'],
            'value_toman' => (int) ($data['value_toman'] ?? 0),
            'stage' => $data['stage'] ?? 'new',
            'source_type' => $lastMessage?->source_type ?? $contact->first_source,
            'source_ref' => $lastMessage?->source_ref ?? $contact->first_source_ref,
            'owner_admin_id' => $this->admin()?->id,
            'stage_changed_at' => now(),
        ]);
        $logger->log('deal.created', 'فرصت «'.$deal->title.'» ساخته شد.', $deal);
        app(MetricsService::class)->forget();

        return back()->with('success', 'فرصت فروش ساخته شد.');
    }

    public function updateDeal(Request $request, Deal $deal, OperationLogger $logger): RedirectResponse
    {
        $this->authorizeAbility('reply');
        abort_unless((int) $deal->workspace_id === $this->ws(), 404);
        $data = $request->validate([
            'stage' => ['nullable', 'in:'.implode(',', array_keys(config('smart_instagram.pipeline_stages')))],
            'value_toman' => ['nullable', 'integer', 'min:0'],
            'lost_reason' => ['nullable', 'string', 'max:190'],
            'title' => ['nullable', 'string', 'max:190'],
        ]);

        if (!empty($data['stage']) && $data['stage'] !== $deal->stage) {
            $deal->stage = $data['stage'];
            $deal->stage_changed_at = now();
            $deal->outcome = match ($data['stage']) {
                'won', 'after_sale' => 'won',
                'lost' => 'lost',
                default => 'open',
            };
            $deal->closed_at = $deal->outcome === 'open' ? null : ($deal->closed_at ?? now());
            if ($deal->outcome === 'won') {
                $deal->contact?->forceFill(['lead_status' => 'customer'])->save();
            }
        }
        foreach (['value_toman', 'lost_reason', 'title'] as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $deal->{$field} = $data[$field];
            }
        }
        $deal->save();
        $logger->log('deal.updated', 'فرصت «'.$deal->title.'» به مرحله‌ی '.(config('smart_instagram.pipeline_stages')[$deal->stage] ?? $deal->stage).' رفت.', $deal);
        app(MetricsService::class)->forget();

        return back()->with('success', 'فرصت به‌روز شد.');
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $this->authorizeAbility('reply');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'contact_id' => ['nullable', 'integer'],
            'conversation_id' => ['nullable', 'integer'],
            'due_at' => ['nullable', 'date'],
            'type' => ['nullable', 'in:first_reply,send_sample,send_link,follow_up,call,request_photo,record_result,repurchase'],
            'assigned_admin_id' => ['nullable', 'integer', 'exists:admins,id'],
        ]);
        if (!empty($data['contact_id'])) {
            Contact::query()->where('workspace_id', $this->ws())->findOrFail($data['contact_id']);
        }

        Task::query()->create([
            'workspace_id' => $this->ws(),
            'contact_id' => $data['contact_id'] ?? null,
            'conversation_id' => $data['conversation_id'] ?? null,
            'type' => $data['type'] ?? 'follow_up',
            'title' => $data['title'],
            // ورودی datetime-local به وقت تهران است؛ در دیتابیس به UTC ذخیره می‌شود.
            'due_at' => !empty($data['due_at']) ? \Illuminate\Support\Carbon::parse($data['due_at'], 'Asia/Tehran')->utc() : now()->addDay(),
            'assigned_admin_id' => $data['assigned_admin_id'] ?? $this->admin()?->id,
            'created_via' => 'human',
        ]);
        app(MetricsService::class)->forget();

        return back()->with('success', 'وظیفه ثبت شد.');
    }

    public function updateTask(Request $request, Task $task): RedirectResponse
    {
        $this->authorizeAbility('reply');
        abort_unless((int) $task->workspace_id === $this->ws(), 404);
        $data = $request->validate(['status' => ['required', 'in:open,done,cancelled'], 'result' => ['nullable', 'string', 'max:190']]);
        $task->forceFill([
            'status' => $data['status'],
            'result' => $data['result'] ?? $task->result,
            'completed_at' => $data['status'] === 'done' ? now() : null,
        ])->save();
        app(MetricsService::class)->forget();

        return back()->with('success', $data['status'] === 'done' ? 'وظیفه انجام شد.' : 'وظیفه به‌روز شد.');
    }
}
