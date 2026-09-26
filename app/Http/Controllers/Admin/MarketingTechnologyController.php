<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GrowthContent;
use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Models\Admin;
use App\Models\MarketingCampaign;
use App\Models\MarketingContent;
use App\Models\MarketingOperationRun;
use App\Models\MarketingCostEvent;
use App\Models\MarketingEvent;
use App\Models\MarketingIntegration;
use App\Models\MarketingScenario;
use App\Models\Product;
use App\Models\SalesPartnerLead;
use App\Models\SalesPartnerStage;
use App\Models\SalesPartnerActivity;
use App\Models\SalesPartnerTeamSetting;
use App\Models\CustomerJourney;
use App\Models\CustomerJourneyTask;
use App\Services\CustomerJourneyService;
use App\Services\MarketingCostAnalysisService;
use App\Services\MarketingAnalyticsService;
use App\Services\MetaInstagramApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketingTechnologyController extends Controller
{
    public function index(): View
    {
        return view('admin.marketing-technology.index', [
            'title' => 'تکنولوژی مارکتینگ',
            'metrics' => $this->metrics(),
            'modules' => $this->modules(),
            'pipeline' => $this->pipeline(),
        ]);
    }

    public function partners(Request $request): View
    {
        $ready = Schema::hasTable('sales_partner_leads');
        $stages = $this->partnerStages();
        $view = $request->string('view', 'all')->toString();
        $allowedViews = ['all', 'today', 'overdue', 'no_follow_up'];
        if (!in_array($view, $allowedViews, true)) {
            $view = 'all';
        }

        $stageFilter = $request->filled('stage') && is_numeric($request->input('stage'))
            ? (int) $request->input('stage')
            : null;
        $channelFilter = $request->string('channel')->toString();
        $priorityFilter = $request->string('priority')->toString();
        $assigneeFilter = $request->string('assigned_to')->toString();
        $search = trim($request->string('q')->toString());

        $applyTaskFilters = function ($query) use ($stageFilter, $channelFilter, $priorityFilter, $assigneeFilter, $search): void {
            if ($stageFilter !== null && $stageFilter >= 0 && $stageFilter <= 10) {
                $query->where('stage', $stageFilter);
            }
            if (in_array($channelFilter, ['instagram', 'telegram', 'both', 'other'], true)) {
                $query->where('channel', $channelFilter);
            }
            if (in_array($priorityFilter, ['low', 'normal', 'high'], true)) {
                $query->where('priority', $priorityFilter);
            }
            if ($assigneeFilter === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($assigneeFilter) && (int) $assigneeFilter > 0) {
                $query->where('assigned_to', (int) $assigneeFilter);
            }
            if ($search !== '') {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('handle', 'like', "%{$search}%");
                });
            }
        };

        $leads = $ready
            ? tap(SalesPartnerLead::query()->with('assignee')->where('status', 'active'), $applyTaskFilters)
                ->when($view === 'today', fn ($query) => $query->whereNotNull('next_follow_up_at')->whereDate('next_follow_up_at', '<=', today()))
                ->when($view === 'overdue', fn ($query) => $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', today()->startOfDay()))
                ->when($view === 'no_follow_up', fn ($query) => $query->whereNull('next_follow_up_at'))
                ->orderBy('stage')
                ->orderByRaw('CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at')
                ->get()
            : collect();

        $todayFollowUps = $ready
            ? tap(SalesPartnerLead::query()->with('assignee')->where('status', 'active')->whereNotNull('next_follow_up_at'), $applyTaskFilters)
                ->whereDate('next_follow_up_at', '<=', today())
                ->orderBy('next_follow_up_at')
                ->orderBy('stage')
                ->get()
            : collect();

        $allActiveLeads = $ready
            ? SalesPartnerLead::query()->where('status', 'active')->get(['stage', 'next_follow_up_at', 'positive_reply_count', 'assigned_to'])
            : collect();
        $admins = Schema::hasTable('admins')
            ? Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role'])
            : collect();
        $dailyTarget = 20;
        $dailyContactsQuery = Schema::hasTable('sales_partner_activities')
            ? SalesPartnerActivity::query()->whereDate('contacted_at', today())
            : null;
        if ($dailyContactsQuery && $assigneeFilter === 'unassigned') {
            $dailyContactsQuery->whereHas('lead', fn ($query) => $query->whereNull('assigned_to'));
        } elseif ($dailyContactsQuery && is_numeric($assigneeFilter) && (int) $assigneeFilter > 0) {
            $dailyContactsQuery->whereHas('lead', fn ($query) => $query->where('assigned_to', (int) $assigneeFilter));
        }
        $dailyContacts = $dailyContactsQuery?->count() ?? 0;

        $leadsByStage = collect($stages)->mapWithKeys(function (array $stage) use ($leads): array {
            return [$stage['key'] => $leads->where('stage', $stage['key'])->values()];
        });

        return view('admin.marketing-technology.partners', [
            'title' => 'مسیر همکاران فروش',
            'ready' => $ready,
            'stages' => $stages,
            'leads' => $leads,
            'leadsByStage' => $leadsByStage,
            'todayFollowUps' => $todayFollowUps,
            'admins' => $admins,
            'dailyTarget' => $dailyTarget,
            'dailyContacts' => $dailyContacts,
            'filters' => [
                'view' => $view,
                'stage' => $stageFilter,
                'channel' => $channelFilter,
                'priority' => $priorityFilter,
                'assigned_to' => $assigneeFilter,
                'q' => $search,
            ],
            'metrics' => [
                'total' => $allActiveLeads->count(),
                'due' => $allActiveLeads->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lte(now()->endOfDay()) ?? false)->count(),
                'overdue' => $allActiveLeads->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false)->count(),
                'unassigned' => $allActiveLeads->whereNull('assigned_to')->count(),
                'daily_target' => $dailyTarget,
                'daily_contacts' => $dailyContacts,
                'positive' => $allActiveLeads->filter(fn (SalesPartnerLead $lead): bool => $lead->positive_reply_count > 0)->count(),
                'active' => $allActiveLeads->where('stage', 10)->count(),
            ],
        ]);
    }

    public function partnerQueue(Request $request): View
    {
        $ready = Schema::hasTable('sales_partner_leads')
            && Schema::hasTable('sales_partner_stages')
            && Schema::hasTable('sales_partner_activities');
        $admin = $request->user('admin');
        $scope = $request->string('scope', 'mine')->toString();
        if (! in_array($scope, ['mine', 'all'], true)) {
            $scope = 'mine';
        }

        $stages = $this->partnerStages();
        $stageFilter = $request->filled('stage') && is_numeric($request->input('stage'))
            ? (int) $request->input('stage')
            : null;

        $queue = $ready
            ? SalesPartnerLead::query()
                ->with('assignee')
                ->where('status', 'active')
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<=', today()->endOfDay())
                ->when($scope === 'mine' && $admin, fn ($query) => $query->where('assigned_to', $admin->id))
                ->when($stageFilter !== null && $stageFilter >= 0 && $stageFilter <= 10, fn ($query) => $query->where('stage', $stageFilter))
                ->orderByRaw('CASE WHEN next_follow_up_at < ? THEN 0 ELSE 1 END', [today()->startOfDay()])
                ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
                ->orderBy('next_follow_up_at')
                ->orderBy('stage')
                ->limit(50)
                ->get()
            : collect();

        $todayActivities = $ready
            ? SalesPartnerActivity::query()->whereDate('contacted_at', today())->get(['admin_id', 'result', 'sales_partner_lead_id'])
            : collect();
        $target = 20;
        if ($admin && Schema::hasTable('sales_partner_team_settings')) {
            $target = max(0, (int) (SalesPartnerTeamSetting::query()->where('admin_id', $admin->id)->value('daily_contact_target') ?? 20));
        }
        $todayContacts = $admin
            ? $todayActivities->where('admin_id', $admin->id)->count()
            : $todayActivities->count();
        $overdue = $queue->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false)->count();
        $stageMap = collect($stages)->keyBy('key');

        return view('admin.marketing-technology.partner-queue', [
            'title' => 'صف عملیاتی امروز',
            'ready' => $ready,
            'admin' => $admin,
            'queue' => $queue,
            'stages' => $stages,
            'stageMap' => $stageMap,
            'scope' => $scope,
            'stageFilter' => $stageFilter,
            'todayContacts' => $todayContacts,
            'target' => $target,
            'progress' => $target > 0 ? min(100, (int) round(($todayContacts / $target) * 100)) : 0,
            'overdue' => $overdue,
        ]);
    }

    public function partnerSettings(): View
    {
        $ready = Schema::hasTable('sales_partner_stages');
        $stages = $ready
            ? SalesPartnerStage::query()->orderBy('stage')->get()
            : collect(SalesPartnerStage::defaultDefinitions());

        return view('admin.marketing-technology.partner-settings', [
            'title' => 'تنظیمات مسیر همکاران فروش',
            'ready' => $ready,
            'stages' => $stages,
            'messageTypes' => [
                'none' => 'بدون پیام',
                'voice' => 'وویس',
                'message' => 'پیام متنی',
                'both' => 'وویس یا پیام',
            ],
        ]);
    }

    public function customerJourney(Request $request, CustomerJourneyService $journeyService): View
    {
        $ready = Schema::hasTable('customer_journeys')
            && Schema::hasTable('customer_journey_tasks')
            && Schema::hasTable('customer_journey_events')
            && Schema::hasTable('customer_journey_stages');
        $pointFilter = $request->filled('point') && is_numeric($request->input('point'))
            ? max(1, min(8, (int) $request->input('point')))
            : null;
        $statusFilter = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());

        if ($ready && $request->boolean('sync', true)) {
            \App\Models\User::query()->where('status', '!=', 'blocked')->orderBy('id')->chunkById(100, function ($users) use ($journeyService): void {
                foreach ($users as $user) {
                    $journeyService->sync($user, 'همگام‌سازی داشبورد مسیر کاربران');
                }
            });
        }

        $journeys = $ready
            ? CustomerJourney::query()->with(['user', 'tasks' => fn ($query) => $query->where('status', 'pending')->orderBy('due_at')])
                ->when($pointFilter !== null, fn ($query) => $query->where('point', $pointFilter))
                ->when(in_array($statusFilter, [CustomerJourneyService::ACTIVE, CustomerJourneyService::HUMAN_REVIEW, CustomerJourneyService::PAUSED, CustomerJourneyService::CLOSED], true), fn ($query) => $query->where('status', $statusFilter))
                ->when($search !== '', function ($query) use ($search): void {
                    $query->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
                })
                ->orderByRaw('CASE WHEN status = ? THEN 0 WHEN next_action_at <= ? THEN 1 ELSE 2 END', [CustomerJourneyService::HUMAN_REVIEW, now()])
                ->orderBy('point')
                ->orderBy('next_action_at')
                ->paginate(20)
                ->withQueryString()
            : null;

        $allJourneys = $ready ? CustomerJourney::query()->with('user:id,name,last_name')->get(['id', 'user_id', 'point', 'status', 'next_action_at', 'readiness_score']) : collect();
        $pendingHuman = $ready ? CustomerJourneyTask::query()->where('task_type', 'human_review')->where('status', 'pending')->count() : 0;
        $todayTasks = $ready ? CustomerJourneyTask::query()->where('status', 'pending')->whereDate('due_at', today())->count() : 0;
        $pointStats = collect($journeyService->pointDefinitions())->mapWithKeys(function (array $definition, int $point) use ($allJourneys): array {
            $rows = $allJourneys->where('point', $point);

            return [$point => [
                'total' => $rows->where('status', '!=', CustomerJourneyService::CLOSED)->count(),
                'active' => $rows->where('status', CustomerJourneyService::ACTIVE)->count(),
                'human_review' => $rows->where('status', CustomerJourneyService::HUMAN_REVIEW)->count(),
                'paused' => $rows->where('status', CustomerJourneyService::PAUSED)->count(),
                'closed' => $rows->where('status', CustomerJourneyService::CLOSED)->count(),
                'samples' => $rows->where('status', '!=', CustomerJourneyService::CLOSED)->take(3)->map(function (CustomerJourney $journey): array {
                    return ['name' => trim(($journey->user?->name ?: '').' '.($journey->user?->last_name ?: '')) ?: 'کاربر بدون نام', 'status' => $journey->status];
                })->values()->all(),
            ]];
        })->all();

        return view('admin.marketing-technology.customer-journey', [
            'title' => 'مسیر کاربران',
            'ready' => $ready,
            'journeys' => $journeys,
            'pointDefinitions' => $journeyService->pointDefinitions(),
            'settings' => $journeyService->settings(),
            'filters' => ['point' => $pointFilter, 'status' => $statusFilter, 'q' => $search],
            'pointStats' => $pointStats,
            'metrics' => [
                'total' => $allJourneys->where('status', '!=', CustomerJourneyService::CLOSED)->count(),
                'today_tasks' => $todayTasks,
                'human_review' => $pendingHuman,
                'average_score' => (int) round($allJourneys->avg('readiness_score') ?: 0),
                'points' => $allJourneys->where('status', '!=', CustomerJourneyService::CLOSED)->countBy('point'),
            ],
        ]);
    }

    public function updateCustomerJourneyPoint(Request $request, CustomerJourney $customerJourney, CustomerJourneyService $journeyService): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'point' => ['required', 'integer', 'between:1,8'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $journeyService->moveManually($customerJourney, (int) $data['point'], $data['reason'], $request->user('admin')?->id);

        $message = 'پوینت کاربر و تسک مرحله بعد ثبت شد.';
        if ($request->expectsJson()) {
            $definition = $journeyService->pointDefinitions()[(int) $data['point']] ?? null;
            return response()->json(['ok' => true, 'message' => $message, 'journey_id' => $customerJourney->id, 'point' => (int) $data['point'], 'point_label' => $definition['short'] ?? '']);
        }
        return back()->with('success', $message);
    }

    public function reviewCustomerJourney(Request $request, CustomerJourney $customerJourney, CustomerJourneyService $journeyService): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $journeyService->sendToHumanReview($customerJourney, $data['reason'], $request->user('admin')?->id);

        $message = 'کاربر وارد لیست بررسی انسانی شد.';
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message, 'journey_id' => $customerJourney->id, 'status' => 'human_review']) : back()->with('success', $message);
    }

    public function completeCustomerJourneyTask(Request $request, CustomerJourneyTask $customerJourneyTask, CustomerJourneyService $journeyService): RedirectResponse|JsonResponse
    {
        $journeyService->completeTask($customerJourneyTask, $request->user('admin')?->id);

        $message = 'تسک انجام‌شده ثبت شد.';
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message, 'task_id' => $customerJourneyTask->id]) : back()->with('success', $message);
    }

    public function updateCustomerJourneySettings(Request $request, CustomerJourneyService $journeyService): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'auto_credit_percent' => ['required', 'integer', 'between:0,100'],
            'station_size' => ['required', 'integer', 'min:1', 'max:100000'],
            'final_credit_threshold' => ['required', 'integer', 'min:0', 'max:100000'],
            'inactivity_days' => ['required', 'integer', 'min:1', 'max:90'],
            'max_daily_tasks' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
        $journeyService->updateSettings($data, $request->user('admin')?->id);

        $message = 'تنظیمات مسیر کاربران ذخیره شد.';
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
    }

    public function updateCustomerJourneyStage(Request $request, int $point, CustomerJourneyService $journeyService): RedirectResponse|JsonResponse
    {
        abort_unless(Schema::hasTable('customer_journey_stages'), 503, 'تنظیمات مراحل مسیر کاربران هنوز آماده نشده است.');
        abort_unless($point >= 1 && $point <= 8, 404);

        $data = $request->validate([
            'short' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'task_title' => ['required', 'string', 'max:180'],
            'task_body' => ['nullable', 'string', 'max:5000'],
            'channel' => ['required', Rule::in(['none', 'sms', 'call', 'direct', 'email', 'in_app', 'multi'])],
            'message_template' => ['nullable', 'string', 'max:5000'],
            'delay_minutes' => ['required', 'integer', 'min:0', 'max:525600'],
            'human_required' => ['nullable', 'boolean'],
            'advance_rule' => ['nullable', 'string', 'max:3000'],
            'stop_rule' => ['nullable', 'string', 'max:3000'],
            'enabled' => ['nullable', 'boolean'],
        ]);
        $data['human_required'] = $request->boolean('human_required');
        $data['enabled'] = $request->boolean('enabled');
        $journeyService->updatePointDefinition($point, $data);

        $message = "تنظیمات گام {$point} مسیر کاربران ذخیره شد.";
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message, 'point' => $point]) : back()->with('success', $message);
    }

    public function updatePartnerStageSettings(Request $request, SalesPartnerStage $salesPartnerStage): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:500'],
            'goal' => ['nullable', 'string', 'max:5000'],
            'task' => ['nullable', 'string', 'max:5000'],
            'script' => ['nullable', 'string', 'max:10000'],
            'follow_up' => ['nullable', 'string', 'max:1000'],
            'default_follow_up_hours' => ['nullable', 'integer', 'min:0', 'max:8760'],
            'max_follow_ups' => ['required', 'integer', 'min:0', 'max:20'],
            'message_type' => ['required', Rule::in(['none', 'voice', 'message', 'both'])],
            'advance_when' => ['nullable', 'string', 'max:5000'],
            'stop_when' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['updated_by'] = $request->user('admin')?->id;
        $salesPartnerStage->update($data);

        $message = "تنظیمات مرحله {$salesPartnerStage->stage} ذخیره شد.";
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
    }

    public function storePartner(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(Schema::hasTable('sales_partner_leads'), 503, 'مدل مسیر همکاران فروش هنوز روی این محیط اجرا نشده است.');

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:160'],
            'handle' => ['required', 'string', 'max:160'],
            'channel' => ['required', Rule::in(['instagram', 'telegram', 'both', 'other'])],
            'profile_url' => ['nullable', 'url:http,https', 'max:2048'],
            'acquisition_source' => ['nullable', Rule::in(['instagram', 'telegram', 'google', 'website', 'referral', 'manual', 'other'])],
            'stage' => ['required', 'integer', 'between:0,10'],
            'priority' => ['required', Rule::in(['low', 'normal', 'high'])],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $data['handle'] = trim($data['handle']);
        $data['acquisition_source'] = $data['acquisition_source'] ?? 'manual';
        $data['next_follow_up_at'] = $data['next_follow_up_at'] ?? $this->defaultStageFollowUpAt((int) $data['stage']);
        $data['created_by'] = $request->user('admin')?->id;
        $data['assigned_to'] = $request->user('admin')?->id;

        $lead = SalesPartnerLead::query()->create($data + [
            'status' => 'active',
            'stage_changed_at' => now(),
        ]);

        $message = 'سرنخ همکار فروش به مسیر اضافه شد.';
        if ($request->expectsJson()) {
            $lead->load('assignee');
            return response()->json([
                'ok' => true,
                'message' => $message,
                'lead' => ['id' => $lead->id, 'stage' => $lead->stage],
                'html' => view('admin.marketing-technology.partials.partner-lead-card', [
                    'lead' => $lead,
                    'acquisitionSourceLabels' => $this->partnerAcquisitionSourceLabels(),
                ])->render(),
            ]);
        }
        return back()->with('success', $message);
    }

    public function updatePartnerStage(Request $request, SalesPartnerLead $salesPartnerLead): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['stage' => ['required', 'integer', 'between:0,10']]);
        $salesPartnerLead->update([
            'stage' => (int) $data['stage'],
            'stage_changed_at' => now(),
            'next_follow_up_at' => $this->defaultStageFollowUpAt((int) $data['stage']),
        ]);

        $message = 'مرحله همکار فروش به‌روزرسانی شد.';
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message, 'stage' => $salesPartnerLead->stage]) : back()->with('success', $message);
    }

    public function updatePartnerAssignee(Request $request, SalesPartnerLead $salesPartnerLead): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::exists('admins', 'id')->where(fn ($query) => $query->where('is_active', true))],
        ]);
        $salesPartnerLead->update(['assigned_to' => $data['assigned_to'] ?? null]);

        $message = 'مسئول پیگیری همکار فروش به‌روزرسانی شد.';
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
    }

    public function markPartnerContacted(Request $request, SalesPartnerLead $salesPartnerLead): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'contact_type' => ['required', Rule::in(['voice', 'message', 'call', 'other'])],
            'result' => ['required', Rule::in(['no_response', 'positive', 'negative', 'follow_up'])],
            'next_follow_up_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:3000'],
        ]);

        $salesPartnerLead->increment('contact_count');
        if ($data['result'] !== 'no_response') {
            $salesPartnerLead->increment('reply_count');
        }
        if ($data['result'] === 'positive') {
            $salesPartnerLead->increment('positive_reply_count');
        }
        $defaultFollowUp = $this->defaultStageFollowUpAt((int) $salesPartnerLead->stage);
        $salesPartnerLead->update([
            'last_contact_at' => now(),
            'last_contact_type' => $data['contact_type'],
            'last_contact_result' => $data['result'],
            'last_contact_note' => $data['note'] ?? null,
            'next_follow_up_at' => $data['next_follow_up_at'] ?? ($data['result'] === 'negative' ? null : ($defaultFollowUp ?? now()->addDay())),
        ]);
        if (Schema::hasTable('sales_partner_activities')) {
            SalesPartnerActivity::query()->create([
                'sales_partner_lead_id' => $salesPartnerLead->id,
                'admin_id' => $request->user('admin')?->id,
                'contact_type' => $data['contact_type'],
                'result' => $data['result'],
                'note' => $data['note'] ?? null,
                'contacted_at' => now(),
                'next_follow_up_at' => $salesPartnerLead->next_follow_up_at,
            ]);
        }

        $message = 'تماس ثبت شد و پیگیری بعدی در صف قرار گرفت.';
        if ($request->expectsJson()) {
            $freshLead = $salesPartnerLead->fresh();
            return response()->json([
                'ok' => true,
                'message' => $message,
                'lead' => [
                    'id' => $freshLead->id,
                    'contact_count' => $freshLead->contact_count,
                    'next_follow_up_at' => $freshLead->next_follow_up_at?->toIso8601String(),
                ],
            ]);
        }
        return back()->with('success', $message);
    }

    public function partnerTeam(): View
    {
        $ready = Schema::hasTable('sales_partner_leads')
            && Schema::hasTable('sales_partner_team_settings');
        $admins = Schema::hasTable('admins')
            ? Admin::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'role'])
            : collect();
        $settings = $ready
            ? SalesPartnerTeamSetting::query()->whereIn('admin_id', $admins->pluck('id'))->get()->keyBy('admin_id')
            : collect();
        $activeLeads = Schema::hasTable('sales_partner_leads')
            ? SalesPartnerLead::query()->where('status', 'active')->get(['id', 'assigned_to', 'stage', 'next_follow_up_at', 'last_contact_at', 'positive_reply_count', 'created_at'])
            : collect();
        $todayActivities = Schema::hasTable('sales_partner_activities')
            ? SalesPartnerActivity::query()->whereDate('contacted_at', today())->get(['sales_partner_lead_id', 'admin_id', 'result'])
            : collect();

        $teamRows = $admins->map(function (Admin $admin) use ($settings, $activeLeads, $todayActivities): array {
            $assigned = $activeLeads->where('assigned_to', $admin->id);
            $targetSetting = $settings->get($admin->id);
            $target = max(0, (int) ($targetSetting?->daily_contact_target ?? 20));
            $contacts = $todayActivities->filter(function (SalesPartnerActivity $activity) use ($assigned): bool {
                return $assigned->contains('id', $activity->sales_partner_lead_id);
            });
            $contactCount = $contacts->count();
            $due = $assigned->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lte(now()->endOfDay()) ?? false)->count();
            $overdue = $assigned->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false)->count();
            $positive = $contacts->where('result', 'positive')->count();

            return [
                'admin' => $admin,
                'setting' => $targetSetting,
                'target' => $target,
                'contacts' => $contactCount,
                'progress' => $target > 0 ? min(100, (int) round(($contactCount / $target) * 100)) : 0,
                'assigned' => $assigned->count(),
                'due' => $due,
                'overdue' => $overdue,
                'positive' => $positive,
                'response_rate' => $contactCount > 0 ? (int) round(($contacts->where('result', '!=', 'no_response')->count() / $contactCount) * 100) : 0,
                'active' => $targetSetting?->is_active ?? true,
            ];
        })->values();

        $activeTarget = (int) $teamRows->where('active', true)->sum('target');
        $todayContacts = $todayActivities->filter(function (SalesPartnerActivity $activity) use ($activeLeads): bool {
            return $activeLeads->contains('id', $activity->sales_partner_lead_id);
        })->count();
        $overdueCount = $activeLeads->filter(fn (SalesPartnerLead $lead): bool => $lead->next_follow_up_at?->lt(today()->startOfDay()) ?? false)->count();
        $unassignedCount = $activeLeads->whereNull('assigned_to')->count();
        $noFollowUpCount = $activeLeads->whereNull('next_follow_up_at')->count();
        $positiveToday = $todayActivities->where('result', 'positive')->count();

        return view('admin.marketing-technology.partner-team', [
            'title' => 'عملکرد تیم فروش',
            'ready' => $ready,
            'teamRows' => $teamRows,
            'alerts' => [
                ['title' => 'پیگیری‌های عقب‌افتاده', 'count' => $overdueCount, 'description' => 'کارت‌هایی که امروز باید زودتر بررسی شوند.', 'url' => route('admin.marketing-technology.partners', ['view' => 'overdue'])],
                ['title' => 'کارت‌های بدون مسئول', 'count' => $unassignedCount, 'description' => 'سرنخ‌هایی که هنوز به یک نفر سپرده نشده‌اند.', 'url' => route('admin.marketing-technology.partners', ['assigned_to' => 'unassigned'])],
                ['title' => 'بدون زمان پیگیری', 'count' => $noFollowUpCount, 'description' => 'کارت‌هایی که قدم بعدی مشخص ندارند.', 'url' => route('admin.marketing-technology.partners', ['view' => 'no_follow_up'])],
            ],
            'metrics' => [
                'active_leads' => $activeLeads->count(),
                'today_contacts' => $todayContacts,
                'today_target' => $activeTarget,
                'overdue' => $overdueCount,
                'unassigned' => $unassignedCount,
                'positive_today' => $positiveToday,
            ],
        ]);
    }

    public function updatePartnerTeamSetting(Request $request, Admin $admin): RedirectResponse|JsonResponse
    {
        abort_unless(Schema::hasTable('sales_partner_team_settings'), 503, 'تنظیمات تیم فروش هنوز روی این محیط اجرا نشده است.');
        abort_unless($admin->is_active, 404);

        $data = $request->validate([
            'daily_contact_target' => ['required', 'integer', 'between:0,200'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        SalesPartnerTeamSetting::query()->updateOrCreate(
            ['admin_id' => $admin->id],
            [
                'daily_contact_target' => (int) $data['daily_contact_target'],
                'is_active' => $request->boolean('is_active'),
            ],
        );

        $message = "هدف روزانه {$admin->name} ذخیره شد.";
        return $request->expectsJson() ? response()->json(['ok' => true, 'message' => $message]) : back()->with('success', $message);
    }

    public function contentCalendar(Request $request): View
    {
        $ready = $this->foundationReady();
        $query = $ready ? MarketingContent::with(['campaign', 'scenario', 'product'])->latest('publish_at')->latest() : null;
        if ($query && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($query && $request->filled('from')) {
            $query->whereDate('publish_at', '>=', $request->date('from'));
        }
        if ($query && $request->filled('to')) {
            $query->whereDate('publish_at', '<=', $request->date('to'));
        }

        return view('admin.marketing-technology.content-calendar', [
            'title' => 'تقویم و صف محتوا',
            'ready' => $ready,
            'contents' => $query ? $query->paginate(12)->withQueryString() : $this->emptyPaginator(),
            'campaigns' => $ready ? MarketingCampaign::query()->whereIn('status', ['draft', 'active'])->orderBy('name')->get() : collect(),
            'scenarios' => $ready ? MarketingScenario::query()->whereIn('status', ['draft', 'active'])->orderBy('name')->get() : collect(),
            'products' => Schema::hasTable('products') ? Product::query()->where('status', 'active')->orderBy('name_fa')->limit(200)->get(['id', 'name_fa', 'product_code']) : collect(),
            'operations' => $ready ? MarketingOperationRun::with('content')->latest()->limit(12)->get() : collect(),
        ]);
    }

    public function storeContent(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_scenario_id' => ['nullable', 'exists:marketing_scenarios,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'content_type' => ['required', Rule::in(['reel', 'post', 'story', 'video'])],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'ready', 'published', 'paused'])],
            'publish_at' => ['nullable', 'date'],
            'hook' => ['nullable', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'keyword' => ['nullable', 'string', 'max:120'],
        ]);
        $data['created_by'] = $request->user('admin')?->id;
        MarketingContent::query()->create($data);

        return back()->with('success', 'محتوا در تقویم تکنولوژی مارکتینگ ثبت شد.');
    }

    public function updateContent(Request $request, MarketingContent $marketingContent): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'scheduled', 'ready', 'published', 'paused'])],
            'publish_at' => ['nullable', 'date'],
            'hook' => ['nullable', 'string', 'max:2000'],
            'caption' => ['nullable', 'string', 'max:10000'],
            'keyword' => ['nullable', 'string', 'max:120'],
        ]);
        $marketingContent->update($data);

        return back()->with('success', 'محتوا به‌روزرسانی شد.');
    }

    public function queueContent(MarketingContent $marketingContent): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $run = MarketingOperationRun::query()->create([
            'run_uuid' => (string) Str::uuid(),
            'operation_type' => 'content.prepare',
            'status' => 'queued',
            'idempotency_key' => 'content-'.$marketingContent->id.'-'.Str::uuid(),
            'marketing_campaign_id' => $marketingContent->marketing_campaign_id,
            'marketing_content_id' => $marketingContent->id,
            'marketing_scenario_id' => $marketingContent->marketing_scenario_id,
        ]);
        $marketingContent->update(['status' => 'ready']);

        return back()->with('success', "محتوا وارد صف شد (اجرای شماره {$run->id}).");
    }

    public function scenarios(): View
    {
        $ready = $this->foundationReady();

        return view('admin.marketing-technology.scenarios', [
            'title' => 'سناریوهای کامنت و دایرکت',
            'ready' => $ready,
            'scenarios' => $ready ? MarketingScenario::with('versions')->latest()->paginate(10) : $this->emptyPaginator(),
        ]);
    }

    public function inbox(Request $request): View
    {
        $ready = Schema::hasTable('marketing_events');
        $query = $ready ? MarketingEvent::with(['campaign', 'content', 'scenario'])->latest('occurred_at') : null;
        if ($query && $request->filled('channel')) $query->where('channel', $request->string('channel')->toString());
        if ($query && $request->filled('processing_status')) $query->where('processing_status', $request->string('processing_status')->toString());
        if ($query && $request->filled('search')) $query->where(function ($builder) use ($request) { $term = '%'.$request->string('search')->toString().'%'; $builder->where('event_type', 'like', $term)->orWhere('external_id', 'like', $term)->orWhere('actor_ref', 'like', $term); });

        return view('admin.marketing-technology.inbox', [
            'title' => 'صندوق گفتگوها',
            'ready' => $ready,
            'events' => $query ? $query->paginate(15)->withQueryString() : $this->emptyPaginator(15),
        ]);
    }

    public function storeScenario(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady(), 503, 'مدل داده‌ی تکنولوژی مارکتینگ هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'alpha_dash:ascii', 'max:100', 'unique:marketing_scenarios,code'],
            'channel' => ['required', Rule::in(['instagram', 'telegram', 'youtube', 'other'])],
            'trigger_type' => ['required', Rule::in(['comment_keyword', 'direct_message', 'manual'])],
            'status' => ['required', Rule::in(['draft', 'active', 'paused'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_keyword' => ['nullable', 'string', 'max:120'],
            'public_reply' => ['nullable', 'string', 'max:2000'],
            'opening_message' => ['required', 'string', 'max:4000'],
            'opening_button_label' => ['nullable', 'string', 'max:120'],
            'followup_message' => ['nullable', 'string', 'max:4000'],
            'followup_button_label' => ['nullable', 'string', 'max:120'],
            'followup_url' => ['nullable', 'url:http,https', 'max:2000'],
        ]);

        DB::transaction(function () use ($data, $request): void {
            $scenario = MarketingScenario::query()->create([
                'name' => $data['name'], 'code' => $data['code'], 'channel' => $data['channel'],
                'trigger_type' => $data['trigger_type'], 'status' => $data['status'],
                'description' => $data['description'] ?? null, 'created_by' => $request->user('admin')?->id,
            ]);
            $scenario->versions()->create($this->versionPayload($data, 1, $request->user('admin')?->id));
        });

        return back()->with('success', 'سناریو و نسخه‌ی اول آن ساخته شد.');
    }

    public function updateScenario(Request $request, MarketingScenario $marketingScenario): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['draft', 'active', 'paused'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'trigger_keyword' => ['nullable', 'string', 'max:120'],
            'public_reply' => ['nullable', 'string', 'max:2000'],
            'opening_message' => ['required', 'string', 'max:4000'],
            'opening_button_label' => ['nullable', 'string', 'max:120'],
            'followup_message' => ['nullable', 'string', 'max:4000'],
            'followup_button_label' => ['nullable', 'string', 'max:120'],
            'followup_url' => ['nullable', 'url:http,https', 'max:2000'],
        ]);
        DB::transaction(function () use ($data, $request, $marketingScenario): void {
            $marketingScenario->update(['name' => $data['name'], 'status' => $data['status'], 'description' => $data['description'] ?? null]);
            $version = ((int) $marketingScenario->versions()->max('version')) + 1;
            $marketingScenario->versions()->create($this->versionPayload($data, $version, $request->user('admin')?->id));
            $marketingScenario->update(['active_version' => $version]);
        });

        return back()->with('success', 'نسخه‌ی جدید سناریو ذخیره شد.');
    }

    public function reports(Request $request, MarketingAnalyticsService $analytics): View
    {
        return view('admin.marketing-technology.reports', $analytics->report($request->only(['from', 'to', 'channel'])) + [
            'title' => 'گزارش و تحلیل',
        ]);
    }

    public function costs(Request $request, MarketingCostAnalysisService $costs): View
    {
        $report = $costs->report($request->only(['from', 'to', 'refresh_rate']));

        return view('admin.marketing-technology.costs', $report + [
            'title' => 'مرکز هزینه',
            'campaigns' => $this->foundationReady() ? MarketingCampaign::orderBy('name')->get() : collect(),
            'contents' => $this->foundationReady() ? MarketingContent::orderBy('title')->limit(200)->get(['id', 'title']) : collect(),
        ]);
    }

    public function storeCost(Request $request): RedirectResponse
    {
        abort_unless($this->foundationReady() && Schema::hasTable('marketing_cost_events'), 503, 'مدل هزینه هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:80'],
            'service' => ['required', 'string', 'max:100'],
            'units' => ['required', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:40'],
            'unit_cost_usd' => ['nullable', 'numeric', 'min:0'],
            'fx_rate_toman' => ['nullable', 'numeric', 'min:0'],
            'cost_toman' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['estimated', 'actual', 'needs_review'])],
            'marketing_campaign_id' => ['nullable', 'exists:marketing_campaigns,id'],
            'marketing_content_id' => ['nullable', 'exists:marketing_contents,id'],
            'incurred_at' => ['required', 'date'],
        ]);
        $calculated = (float) ($data['units'] ?? 0) * (float) ($data['unit_cost_usd'] ?? 0) * (float) ($data['fx_rate_toman'] ?? 0);
        $data['cost_toman'] = array_key_exists('cost_toman', $data) && $data['cost_toman'] !== null
            ? (int) $data['cost_toman']
            : (int) round($calculated);
        if ((int) $data['cost_toman'] <= 0 && $calculated <= 0) {
            $data['status'] = 'needs_review';
        }
        MarketingCostEvent::query()->create($data);

        return back()->with('success', 'رویداد هزینه ثبت شد و در گزارش‌ها محاسبه می‌شود.');
    }

    public function integrations(): View
    {
        return view('admin.marketing-technology.integrations', [
            'title' => 'اتصال‌ها و سلامت سرویس',
            'ready' => Schema::hasTable('marketing_integrations'),
            'integration' => Schema::hasTable('marketing_integrations') ? MarketingIntegration::query()->where('provider', 'meta')->first() : null,
            'webhookUrl' => route('webhooks.meta.verify'),
            'oauthReady' => filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret')),
            'oauthRedirectUri' => route('admin.marketing-technology.integrations.meta.oauth.callback'),
        ]);
    }

    public function startMetaOAuth(Request $request, MetaInstagramApiService $meta): RedirectResponse
    {
        abort_unless(filled(config('services.meta.app_id')) && filled(config('services.meta.app_secret')), 503, 'کلیدهای اپ `Meta` هنوز روی سرور تنظیم نشده‌اند.');

        $state = Str::random(64);
        $request->session()->put('meta_oauth_state', $state);

        return redirect()->away($meta->authorizationUrl(
            $state,
            route('admin.marketing-technology.integrations.meta.oauth.callback')
        ));
    }

    public function finishMetaOAuth(Request $request, MetaInstagramApiService $meta): RedirectResponse
    {
        $expectedState = (string) $request->session()->pull('meta_oauth_state');
        $state = (string) $request->string('state')->toString();
        $destination = route('admin.marketing-technology.integrations');

        if ($expectedState === '' || $state === '' || ! hash_equals($expectedState, $state)) {
            return redirect($destination)->with('error', 'اعتبارسنجی بازگشت از `Meta` ناموفق بود؛ اتصال را دوباره شروع کن.');
        }

        if ($request->filled('error')) {
            return redirect($destination)->with('error', 'اتصال به `Meta` توسط کاربر لغو شد یا مجوز لازم صادر نشد.');
        }

        $code = (string) $request->string('code')->toString();
        if ($code === '') {
            return redirect($destination)->with('error', 'کد بازگشت `Meta` دریافت نشد.');
        }

        $result = $meta->completeOAuth($code, route('admin.marketing-technology.integrations.meta.oauth.callback'));
        if (! $result['ok']) {
            return redirect($destination)->with('error', $result['message']);
        }

        $page = collect($result['pages'])->first(fn (array $item) => filled(data_get($item, 'access_token')) && filled(data_get($item, 'instagram_business_account.id')));
        if (! $page) {
            return redirect($destination)->with('error', 'هیچ پیج متصل به اکانت حرفه‌ای `Instagram` در حساب `Meta` پیدا نشد.');
        }

        $integration = MarketingIntegration::query()->firstOrNew(['provider' => 'meta']);
        $integration->fill([
            'provider' => 'meta',
            'name' => (string) data_get($page, 'name', 'اینستاگرام وطن'),
            'status' => 'configured',
            'credentials' => [
                'instagram_user_id' => (string) data_get($page, 'instagram_business_account.id'),
                'page_id' => (string) data_get($page, 'id'),
                'page_name' => (string) data_get($page, 'name'),
                'access_token' => (string) data_get($page, 'access_token'),
                'graph_url' => (string) config('services.meta.facebook_graph_url', 'https://graph.facebook.com'),
            ],
            'last_error' => null,
            'created_by' => $request->user('admin')?->id,
        ]);
        $integration->save();

        return redirect($destination)->with('success', 'اتصال `Meta` با پیج متصل و اکانت حرفه‌ای `Instagram` ذخیره شد؛ اکنون تست خواندنی را اجرا کن.');
    }

    public function storeMetaIntegration(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('marketing_integrations'), 503, 'مدل اتصال هنوز روی این محیط اجرا نشده است.');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'instagram_user_id' => ['required', 'string', 'max:120'],
            'page_id' => ['nullable', 'string', 'max:120'],
            'access_token' => ['required', 'string', 'max:5000'],
        ]);
        $integration = MarketingIntegration::query()->firstOrNew(['provider' => 'meta']);
        $integration->fill([
            'provider' => 'meta',
            'name' => $data['name'],
            'status' => 'configured',
            'credentials' => [
                'instagram_user_id' => trim($data['instagram_user_id']),
                'page_id' => isset($data['page_id']) ? trim($data['page_id']) : null,
                'access_token' => trim($data['access_token']),
            ],
            'last_error' => null,
            'created_by' => $request->user('admin')?->id,
        ]);
        $integration->save();

        return back()->with('success', 'اتصال `Meta` به‌صورت امن ذخیره شد؛ اکنون تست اتصال را اجرا کن.');
    }

    public function testMetaIntegration(MarketingIntegration $marketingIntegration, MetaInstagramApiService $meta): RedirectResponse
    {
        abort_unless($marketingIntegration->provider === 'meta', 404);
        $result = $meta->testConnection($marketingIntegration);
        $marketingIntegration->forceFill([
            'status' => $result['ok'] ? 'healthy' : 'error',
            'last_checked_at' => now(),
            'last_success_at' => $result['ok'] ? now() : $marketingIntegration->last_success_at,
            'last_error' => $result['ok'] ? null : $result['message'],
        ])->save();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function logs(Request $request): View
    {
        $ready = Schema::hasTable('marketing_operation_runs');
        $query = $ready ? MarketingOperationRun::with(['content', 'scenario', 'campaign'])->latest() : null;
        if ($query && $request->filled('status')) $query->where('status', $request->string('status')->toString());

        return view('admin.marketing-technology.logs', [
            'title' => 'لاگ عملیات',
            'ready' => $ready,
            'operations' => $query ? $query->paginate(15)->withQueryString() : $this->emptyPaginator(15),
        ]);
    }

    public function retryOperation(MarketingOperationRun $marketingOperationRun): RedirectResponse
    {
        abort_unless(in_array($marketingOperationRun->status, ['failed', 'cancelled'], true), 422, 'فقط عملیات ناموفق یا لغوشده قابل تلاش مجدد است.');
        $marketingOperationRun->update([
            'status' => 'queued',
            'attempt' => ((int) $marketingOperationRun->attempt) + 1,
            'started_at' => null,
            'finished_at' => null,
            'error_code' => null,
            'error_message' => null,
            'response_payload' => null,
        ]);

        return back()->with('success', 'عملیات دوباره وارد صف شد.');
    }

    private function section(string $title, string $description): View
    {
        return view('admin.marketing-technology.section', [
            'title' => $title,
            'description' => $description,
            'modules' => $this->modules(),
        ]);
    }

    private function metrics(): array
    {
        $ready = Schema::hasTable('growth_contents') && Schema::hasTable('growth_links') && Schema::hasTable('growth_events');

        return [
            'contents' => $ready ? GrowthContent::count() : 0,
            'links' => $ready ? GrowthLink::where('is_active', true)->count() : 0,
            'clicks' => $ready ? GrowthEvent::where('event_type', GrowthEvent::TYPE_CLICK)->count() : 0,
            'opens' => $ready ? GrowthEvent::where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count() : 0,
        ];
    }

    private function modules(): array
    {
        return [
            ['key' => 'partners', 'title' => 'مسیر همکاران فروش', 'description' => 'مدیریت سرنخ‌ها، مراحل، تسک‌های روزانه و مسیر همکاری', 'icon' => 'fa-route', 'status' => 'فاز ۱'],
            ['key' => 'content-calendar', 'title' => 'تقویم و صف محتوا', 'description' => 'برنامه‌ریزی، تولید، تأیید و انتشار محتوا', 'icon' => 'fa-calendar-days', 'status' => 'فعال'],
            ['key' => 'scenarios', 'title' => 'سناریوهای کامنت و دایرکت', 'description' => 'مدیریت متن، دکمه، لینک و قواعد پاسخ', 'icon' => 'fa-comments', 'status' => 'فعال'],
            ['key' => 'inbox', 'title' => 'صندوق گفتگوها', 'description' => 'یک نمای واحد از تعاملات ورودی و خروجی', 'icon' => 'fa-inbox', 'status' => 'فعال'],
            ['key' => 'reports', 'title' => 'گزارش و تحلیل', 'description' => 'قیف تبدیل، عملکرد محتوا و بینش مدیریتی', 'icon' => 'fa-chart-line', 'status' => 'فعال'],
            ['key' => 'costs', 'title' => 'مرکز هزینه', 'description' => 'هزینه‌ی هر عملیات، محتوا و کمپین', 'icon' => 'fa-coins', 'status' => 'فعال'],
            ['key' => 'integrations', 'title' => 'اتصال‌ها و سلامت سرویس', 'description' => 'کنترل اتصال‌ها و آخرین وضعیت دریافت داده', 'icon' => 'fa-plug', 'status' => 'کلید لازم'],
            ['key' => 'logs', 'title' => 'لاگ عملیات', 'description' => 'خطاها، تلاش مجدد و وضعیت اجرای عملیات', 'icon' => 'fa-list-check', 'status' => 'فعال'],
        ];
    }

    private function partnerStages(): array
    {
        if (Schema::hasTable('sales_partner_stages')) {
            $stages = SalesPartnerStage::query()->where('is_active', true)->orderBy('stage')->get();
            if ($stages->isNotEmpty()) {
                return $stages->map(fn (SalesPartnerStage $stage): array => $this->stageToArray($stage))->all();
            }
        }

        return collect(SalesPartnerStage::defaultDefinitions())->map(static function (array $definition): array {
            return $definition + ['key' => $definition['stage']];
        })->all();
    }

    private function partnerAcquisitionSourceLabels(): array
    {
        return [
            'instagram' => 'اینستاگرام',
            'telegram' => 'تلگرام',
            'google' => 'گوگل',
            'website' => 'سایت وطن',
            'referral' => 'معرفی و رفرال',
            'manual' => 'پیدا شده دستی',
            'other' => 'سایر',
        ];
    }

    private function stageToArray(SalesPartnerStage $stage): array
    {
        return [
            'key' => $stage->stage,
            'title' => $stage->title,
            'description' => $stage->description,
            'icon' => $stage->icon,
            'goal' => $stage->goal,
            'task' => $stage->task,
            'script' => $stage->script,
            'follow_up' => $stage->follow_up,
            'default_follow_up_hours' => $stage->default_follow_up_hours,
            'max_follow_ups' => $stage->max_follow_ups,
            'message_type' => $stage->message_type,
            'advance_when' => $stage->advance_when,
            'stop_when' => $stage->stop_when,
        ];
    }

    private function defaultStageFollowUpAt(int $stage): ?\Carbon\Carbon
    {
        if (! Schema::hasTable('sales_partner_stages')) {
            return $stage === 0 ? null : now()->addDay();
        }

        $hours = SalesPartnerStage::query()->where('stage', $stage)->value('default_follow_up_hours');
        return $hours === null ? null : now()->addHours((int) $hours);
    }

    private function pipeline(): array
    {
        return [
            ['title' => 'محصول', 'description' => 'انتخاب محصول و دارایی', 'icon' => 'fa-box-open'],
            ['title' => 'محتوا', 'description' => 'هوک، کپشن و رسانه', 'icon' => 'fa-photo-film'],
            ['title' => 'تأیید', 'description' => 'صف تلگرام و کنترل کیفیت', 'icon' => 'fa-circle-check'],
            ['title' => 'انتشار', 'description' => 'اتصال بعد از آماده‌شدن کلید', 'icon' => 'fa-paper-plane'],
            ['title' => 'اندازه‌گیری', 'description' => 'کلیک، گفتگو، ساخت و خرید', 'icon' => 'fa-chart-pie'],
        ];
    }

    private function versionPayload(array $data, int $version, ?int $adminId): array
    {
        return [
            'version' => $version,
            'status' => ($data['status'] ?? 'draft') === 'active' ? 'published' : 'draft',
            'trigger_keyword' => $data['trigger_keyword'] ?? null,
            'public_reply' => $data['public_reply'] ?? null,
            'opening_message' => $data['opening_message'],
            'opening_button_label' => $data['opening_button_label'] ?? null,
            'followup_message' => $data['followup_message'] ?? null,
            'followup_button_label' => $data['followup_button_label'] ?? null,
            'followup_url' => $data['followup_url'] ?? null,
            'created_by' => $adminId,
            'published_at' => ($data['status'] ?? 'draft') === 'active' ? now() : null,
        ];
    }

    private function foundationReady(): bool
    {
        return Schema::hasTable('marketing_campaigns')
            && Schema::hasTable('marketing_contents')
            && Schema::hasTable('marketing_scenarios')
            && Schema::hasTable('marketing_operation_runs');
    }

    private function emptyPaginator(int $perPage = 12): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $perPage, 1, ['path' => request()->url()]);
    }
}
