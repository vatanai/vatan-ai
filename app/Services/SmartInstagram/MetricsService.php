<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\AiRun;
use App\Models\SmartInstagram\AiSuggestion;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Deal;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\OperationLog;
use App\Models\SmartInstagram\OutboundMessage;
use App\Models\SmartInstagram\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * شاخص‌های داشبورد و گزارش (پروپوزال ۵.۱۰ و ۱۵). همه از داده‌ی واقعی خود ماژول محاسبه می‌شوند؛
 * کوئری‌ها تجمیعی و ایندکس‌دار هستند و نتیجه‌ی داشبورد ۶۰ ثانیه کش می‌شود.
 */
class MetricsService
{
    public function __construct(private readonly WorkspaceContext $context)
    {
    }

    public function dashboard(): array
    {
        $ws = $this->context->id();

        $cards = Cache::remember("smart-instagram:{$ws}:dashboard-cards", 60, function () use ($ws): array {
            $open = Conversation::query()->where('workspace_id', $ws);
            $today = now()->startOfDay();

            return [
                'unanswered' => (clone $open)->whereIn('status', ['new', 'unanswered'])->count(),
                'attention' => (clone $open)->where('needs_human', true)->where('status', '!=', 'closed')->count(),
                'conversations_today' => (clone $open)->where('last_inbound_at', '>=', $today)->count(),
                'hot_leads' => Contact::query()->where('workspace_id', $ws)->where('lead_status', 'hot')->count(),
                'tasks_today' => Task::query()->where('workspace_id', $ws)->where('status', 'open')->where('due_at', '<=', now()->endOfDay())->count(),
                'tasks_overdue' => Task::query()->where('workspace_id', $ws)->where('status', 'open')->where('due_at', '<', now())->count(),
                'won_value_30d' => (int) Deal::query()->where('workspace_id', $ws)->where('outcome', 'won')->where('closed_at', '>=', now()->subDays(30))->sum('value_toman'),
                'won_count_30d' => Deal::query()->where('workspace_id', $ws)->where('outcome', 'won')->where('closed_at', '>=', now()->subDays(30))->count(),
                'pending_suggestions' => AiSuggestion::query()->where('workspace_id', $ws)->where('status', 'pending')->count(),
                'automations_active' => AutomationRule::query()->where('workspace_id', $ws)->where('status', 'active')->count(),
                'automations_test' => AutomationRule::query()->where('workspace_id', $ws)->where('status', 'test')->count(),
                'automations_failing' => AutomationRule::query()->where('workspace_id', $ws)->whereIn('status', ['active', 'test'])->whereNotNull('last_error')->where('updated_at', '>=', now()->subDays(2))->count(),
                'outbound_failed' => OutboundMessage::query()->where('workspace_id', $ws)->where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count(),
                'median_first_response' => $this->medianFirstResponse($ws, now()->subDays(7)),
            ];
        });

        $channel = Channel::query()->where('workspace_id', $ws)->orderBy('id')->first();

        return [
            'cards' => $cards,
            'channel' => $channel,
            'needs_action' => Conversation::query()->where('workspace_id', $ws)
                ->where(fn ($q) => $q->whereIn('status', ['new', 'unanswered'])->orWhere('needs_human', true))
                ->where('status', '!=', 'closed')
                ->with(['contact:id,username,display_name,lead_status,lead_score', 'assignee:id,name'])
                ->orderByDesc('needs_human')->orderByRaw("CASE WHEN priority = 'high' THEN 0 ELSE 1 END")->orderBy('last_inbound_at')
                ->limit(8)->get(),
            'tasks' => Task::query()->where('workspace_id', $ws)->where('status', 'open')
                ->with('contact:id,username,display_name')
                ->orderByRaw('CASE WHEN due_at IS NULL THEN 1 ELSE 0 END')->orderBy('due_at')->limit(6)->get(),
            'at_risk' => Conversation::query()->where('workspace_id', $ws)
                ->where(fn ($q) => $q->where('stage', 'churn_risk')
                    ->orWhere(fn ($w) => $w->where('status', 'waiting_customer')->where('last_outbound_at', '<', now()->subDays(3))))
                ->whereHas('contact', fn ($q) => $q->whereIn('lead_status', ['qualified', 'hot']))
                ->with('contact:id,username,display_name,lead_score')
                ->orderByDesc('updated_at')->limit(5)->get(),
            'top_intents' => Cache::remember("smart-instagram:{$ws}:top-intents", 300, fn () => Conversation::query()
                ->where('workspace_id', $ws)->whereNotNull('intent')->where('last_inbound_at', '>=', now()->subDays(7))
                ->select('intent', DB::raw('COUNT(*) as total'))->groupBy('intent')->orderByDesc('total')->limit(6)->pluck('total', 'intent')->all()),
            'top_content' => $this->contentPerformance(now()->subDays(30), 5),
            'alerts' => OperationLog::query()->where('workspace_id', $ws)->whereIn('level', ['error', 'warning'])->latest('id')->limit(5)->get(),
        ];
    }

    public function report(int $days): array
    {
        $ws = $this->context->id();
        $from = now()->subDays($days)->startOfDay();

        return Cache::remember("smart-instagram:{$ws}:report:{$days}", 120, function () use ($ws, $from, $days): array {
            $conversations = Conversation::query()->where('workspace_id', $ws)->where('last_inbound_at', '>=', $from);
            $convCount = (clone $conversations)->count();
            $answered = (clone $conversations)->whereNotNull('first_response_at')->count();
            $handoff = (clone $conversations)->where('needs_human', true)->count();

            $reviewed = AiSuggestion::query()->where('workspace_id', $ws)->where('created_at', '>=', $from)
                ->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status');
            $accepted = (int) ($reviewed['accepted'] ?? 0);
            $edited = (int) ($reviewed['edited'] ?? 0);
            $rejected = (int) ($reviewed['rejected'] ?? 0);
            $reviewTotal = $accepted + $edited + $rejected;

            $deals = Deal::query()->where('workspace_id', $ws)->where('created_at', '>=', $from);
            $won = Deal::query()->where('workspace_id', $ws)->where('outcome', 'won')->where('closed_at', '>=', $from);
            $closed = Deal::query()->where('workspace_id', $ws)->whereIn('outcome', ['won', 'lost'])->where('closed_at', '>=', $from)->count();

            $commentContacts = Contact::query()->where('workspace_id', $ws)->where('first_source', 'comment')->where('created_at', '>=', $from);
            $commentTotal = (clone $commentContacts)->count();
            $commentToDm = (clone $commentContacts)->whereHas('conversations.messages', fn ($q) => $q->where('direction', 'in')->where('source_type', 'dm'))->count();

            $aiRuns = AiRun::query()->where('workspace_id', $ws)->where('created_at', '>=', $from)->where('purpose', 'reply');

            return [
                'days' => $days,
                'kpis' => [
                    'conversations' => $convCount,
                    'inbound' => Message::query()->where('workspace_id', $ws)->where('direction', 'in')->where('occurred_at', '>=', $from)->count(),
                    'outbound' => Message::query()->where('workspace_id', $ws)->where('direction', 'out')->where('occurred_at', '>=', $from)->count(),
                    'median_first_response' => $this->medianFirstResponse($ws, $from),
                    'unanswered_pct' => $convCount ? round(100 * ($convCount - $answered) / $convCount) : null,
                    'handoff_pct' => $convCount ? round(100 * $handoff / $convCount) : null,
                    'ai_acceptance_pct' => $reviewTotal ? round(100 * ($accepted + $edited) / $reviewTotal) : null,
                    'ai_edit_pct' => $reviewTotal ? round(100 * $edited / $reviewTotal) : null,
                    'deals_created' => (clone $deals)->count(),
                    'won_count' => (clone $won)->count(),
                    'won_value' => (int) (clone $won)->sum('value_toman'),
                    'win_rate' => $closed ? round(100 * (clone $won)->count() / $closed) : null,
                    'comment_to_dm_pct' => $commentTotal ? round(100 * $commentToDm / $commentTotal) : null,
                    'new_contacts' => Contact::query()->where('workspace_id', $ws)->where('created_at', '>=', $from)->count(),
                ],
                'by_source' => $this->bySource($ws, $from),
                'daily' => $this->daily($ws, $from, $days),
                'intents' => (clone $conversations)->whereNotNull('intent')->select('intent', DB::raw('COUNT(*) as total'))
                    ->groupBy('intent')->orderByDesc('total')->pluck('total', 'intent')->all(),
                'team' => $this->team($ws, $from),
                'automations' => AutomationRule::query()->where('workspace_id', $ws)->orderByDesc('runs_count')->limit(10)
                    ->get(['id', 'name', 'status', 'runs_count', 'success_count', 'failure_count'])
                    ->map(fn ($rule) => $rule->only(['id', 'name', 'status', 'runs_count', 'success_count', 'failure_count']))->all(),
                'ai' => [
                    'runs' => (clone $aiRuns)->count(),
                    'failed' => (clone $aiRuns)->where('status', 'failed')->count(),
                    'avg_confidence' => round((float) (clone $aiRuns)->where('status', 'success')->avg('confidence'), 2),
                    'tokens' => (int) (clone $aiRuns)->sum(DB::raw('COALESCE(prompt_tokens,0) + COALESCE(completion_tokens,0)')),
                    'cost' => round((float) (clone $aiRuns)->sum('cost_usd'), 4),
                    'accepted' => $accepted, 'edited' => $edited, 'rejected' => $rejected,
                ],
                'gaps' => $this->knowledgeGaps($ws, $from),
                'lost_reasons' => Deal::query()->where('workspace_id', $ws)->where('outcome', 'lost')->where('closed_at', '>=', $from)
                    ->whereNotNull('lost_reason')->select('lost_reason', DB::raw('COUNT(*) as total'))->groupBy('lost_reason')
                    ->orderByDesc('total')->limit(8)->pluck('total', 'lost_reason')->all(),
                // کش فقط داده‌ی ساده نگه می‌دارد (Laravel اجازه‌ی unserialize شیء را نمی‌دهد).
                'content' => $this->contentPerformance($from, 10)->all(),
            ];
        });
    }

    /** «کدام محتوا فروش ساخت؟» — بر اساس source_ref (شناسه‌ی پست/ریلز/استوری/تبلیغ). */
    public function contentPerformance(Carbon $from, int $limit = 20): Collection
    {
        $ws = $this->context->id();

        $rows = Message::query()->where('workspace_id', $ws)->where('direction', 'in')->whereNotNull('source_ref')
            ->where('occurred_at', '>=', $from)
            ->select('source_ref', DB::raw('MIN(source_type) as source_type'), DB::raw('COUNT(*) as interactions'), DB::raw('COUNT(DISTINCT conversation_id) as conversations'), DB::raw('MAX(occurred_at) as last_at'))
            ->groupBy('source_ref')->orderByDesc('interactions')->limit($limit)->get();

        $deals = Deal::query()->where('workspace_id', $ws)->whereIn('source_ref', $rows->pluck('source_ref'))
            ->select('source_ref', DB::raw('COUNT(*) as deals'), DB::raw("SUM(CASE WHEN outcome = 'won' THEN 1 ELSE 0 END) as won"), DB::raw("SUM(CASE WHEN outcome = 'won' THEN value_toman ELSE 0 END) as value"))
            ->groupBy('source_ref')->get()->keyBy('source_ref');

        return $rows->map(fn ($row) => [
            'ref' => $row->source_ref,
            'type' => $row->source_type,
            'interactions' => (int) $row->interactions,
            'conversations' => (int) $row->conversations,
            'deals' => (int) ($deals[$row->source_ref]->deals ?? 0),
            'won' => (int) ($deals[$row->source_ref]->won ?? 0),
            'value' => (int) ($deals[$row->source_ref]->value ?? 0),
            'last_at' => $row->last_at,
        ]);
    }

    private function medianFirstResponse(int $ws, Carbon $from): ?int
    {
        $values = Conversation::query()->where('workspace_id', $ws)->whereNotNull('first_response_seconds')
            ->where('first_response_at', '>=', $from)->orderBy('first_response_seconds')->limit(5000)->pluck('first_response_seconds')->all();
        $count = count($values);
        if ($count === 0) {
            return null;
        }
        $mid = intdiv($count, 2);

        return (int) ($count % 2 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2);
    }

    private function bySource(int $ws, Carbon $from): array
    {
        $messages = Message::query()->where('workspace_id', $ws)->where('direction', 'in')->where('occurred_at', '>=', $from)
            ->select('source_type', DB::raw('COUNT(*) as messages'), DB::raw('COUNT(DISTINCT conversation_id) as conversations'))
            ->groupBy('source_type')->get()->keyBy('source_type');
        $deals = Deal::query()->where('workspace_id', $ws)->where('created_at', '>=', $from)
            ->select('source_type', DB::raw('COUNT(*) as deals'), DB::raw("SUM(CASE WHEN outcome = 'won' THEN 1 ELSE 0 END) as won"), DB::raw("SUM(CASE WHEN outcome = 'won' THEN value_toman ELSE 0 END) as value"))
            ->groupBy('source_type')->get()->keyBy('source_type');

        $rows = [];
        foreach (config('smart_instagram.sources') as $key => $label) {
            $rows[] = [
                'key' => $key, 'label' => $label,
                'messages' => (int) ($messages[$key]->messages ?? 0),
                'conversations' => (int) ($messages[$key]->conversations ?? 0),
                'deals' => (int) ($deals[$key]->deals ?? 0),
                'won' => (int) ($deals[$key]->won ?? 0),
                'value' => (int) ($deals[$key]->value ?? 0),
            ];
        }

        return $rows;
    }

    private function daily(int $ws, Carbon $from, int $days): array
    {
        $rows = Message::query()->where('workspace_id', $ws)->where('occurred_at', '>=', $from)
            ->select(DB::raw('DATE(occurred_at) as day'), 'direction', DB::raw('COUNT(*) as total'))
            ->groupBy('day', 'direction')->get();
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->day][$row->direction] = (int) $row->total;
        }

        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $series[] = ['day' => $day, 'in' => $map[$day]['in'] ?? 0, 'out' => $map[$day]['out'] ?? 0];
        }

        return $series;
    }

    private function team(int $ws, Carbon $from): array
    {
        $replies = Message::query()->where('workspace_id', $ws)->where('direction', 'out')->whereNotNull('admin_id')->where('occurred_at', '>=', $from)
            ->select('admin_id', DB::raw('COUNT(*) as replies'))->groupBy('admin_id')->pluck('replies', 'admin_id');
        $won = Deal::query()->where('workspace_id', $ws)->where('outcome', 'won')->where('closed_at', '>=', $from)->whereNotNull('owner_admin_id')
            ->select('owner_admin_id', DB::raw('COUNT(*) as won'), DB::raw('SUM(value_toman) as value'))->groupBy('owner_admin_id')->get()->keyBy('owner_admin_id');
        $ids = $replies->keys()->merge($won->keys())->unique();
        $names = \App\Models\Admin::query()->whereIn('id', $ids)->pluck('name', 'id');

        return $ids->map(fn ($id) => [
            'name' => $names[$id] ?? ('ادمین #'.$id),
            'replies' => (int) ($replies[$id] ?? 0),
            'won' => (int) ($won[$id]->won ?? 0),
            'value' => (int) ($won[$id]->value ?? 0),
        ])->sortByDesc('replies')->values()->all();
    }

    /** سؤال‌هایی که دانش برای پاسخشان نبود (missing_info خروجی AI) — ورودی چرخه‌ی یادگیری. */
    public function knowledgeGaps(int $ws, Carbon $from, int $limit = 10): array
    {
        $counts = [];
        AiRun::query()->where('workspace_id', $ws)->where('purpose', 'reply')->where('status', 'success')
            ->where('created_at', '>=', $from)->latest('id')->limit(400)->pluck('output')
            ->each(function ($output) use (&$counts): void {
                foreach ((array) data_get($output, 'missing_info', []) as $gap) {
                    $key = PersianText::normalize((string) $gap);
                    if ($key === '') {
                        continue;
                    }
                    $counts[$key] = ['label' => (string) $gap, 'total' => ($counts[$key]['total'] ?? 0) + 1];
                }
            });
        usort($counts, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_slice($counts, 0, $limit);
    }

    public function forget(): void
    {
        $ws = $this->context->id();
        Cache::forget("smart-instagram:{$ws}:dashboard-cards");
        Cache::forget("smart-instagram:{$ws}:top-intents");
        foreach ([7, 30, 90] as $days) {
            Cache::forget("smart-instagram:{$ws}:report:{$days}");
        }
    }
}
