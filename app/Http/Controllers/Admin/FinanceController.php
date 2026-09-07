<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\FinanceAuditLog;
use App\Models\FinanceCostCenter;
use App\Models\FinanceExchangeRate;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinancePaymentMethod;
use App\Models\FinancePlanSnapshot;
use App\Models\FinanceSetting;
use App\Models\FinanceTransaction;
use App\Models\FinanceVendor;
use App\Models\Plan;
use App\Models\User;
use App\Services\Finance\FinanceAccessService;
use App\Services\Finance\FinanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function show(
        Request $request,
        FinanceAccessService $access,
        FinanceReportService $reports,
        string $section = 'overview',
    ) {
        abort_unless(array_key_exists($section, config('finance.sections')), 404);
        if ($section === 'cases') {
            return to_route('admin.finance.cases.index', $request->only(['user_id', 'q', 'status', 'is_test']));
        }
        [$from, $to] = $this->range($request);
        $data = [
            'section' => $section,
            'sections' => config('finance.sections'),
            'categories' => config('finance.categories'),
            'statuses' => config('finance.statuses'),
            'from' => $from,
            'to' => $to,
            'canWrite' => $access->canWrite($request->user('admin')),
            'canApprove' => $access->canApprove($request->user('admin')),
        ];

        $data += match ($section) {
            'transactions', 'expenses', 'income' => $this->transactionData($request, $section),
            'plans' => ['rows' => $reports->plans($from, $to)],
            'products' => ['rows' => $reports->summary('products', $from, $to)],
            'exchange-rates' => $this->rateData(),
            'cost-centers' => $this->referenceData(),
            'reports' => $this->reportData($request, $reports, $from, $to),
            'settings' => $this->settingsData(),
            default => $this->overviewData($from, $to, $reports),
        };

        return view('admin.finance.index', $data);
    }

    public function export(Request $request, FinanceReportService $reports)
    {
        $data = $request->validate([
            'report' => ['nullable', Rule::in(['daily', 'monthly', 'plans', 'products', 'models', 'providers', 'channels', 'users'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        [$from, $to] = $this->range($request);
        $report = $data['report'] ?? 'daily';
        $rows = $reports->summary($report, $from, $to);
        $filename = "finance-{$report}-{$from->format('Ymd')}-{$to->format('Ymd')}.csv";

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['عنوان', 'تعداد', 'اعتبار', 'درآمد تومان', 'هزینه تخمینی', 'هزینه مستقیم', 'هزینه تخصیصی', 'سود خالص', 'حاشیه سود درصد']);
            foreach ($rows as $row) {
                fputcsv($stream, [
                    $row['label'], $row['count'], $row['credits'], $row['revenue'],
                    $row['estimated_cost'], $row['direct_cost'], $row['allocated_cost'],
                    $row['profit'], $row['margin'],
                ]);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function plan(
        Request $request,
        Plan $plan,
        FinanceAccessService $access,
        FinanceReportService $reports,
    ) {
        [$from, $to] = $this->range($request);
        $rows = $reports->plans($from, $to);
        $planMetrics = $rows->firstWhere('plan_id', $plan->id) ?? [
            'plan_id' => $plan->id,
            'label' => $plan->name,
            'count' => 0,
            'credits' => 0,
            'revenue' => 0,
            'estimated_cost' => 0,
            'direct_cost' => 0,
            'allocated_cost' => 0,
            'profit' => 0,
            'margin' => 0,
        ];

        return view('admin.finance.index', [
            'section' => 'plans',
            'sections' => config('finance.sections'),
            'categories' => config('finance.categories'),
            'statuses' => config('finance.statuses'),
            'from' => $from,
            'to' => $to,
            'canWrite' => $access->canWrite($request->user('admin')),
            'canApprove' => $access->canApprove($request->user('admin')),
            'rows' => $rows,
            'planDetail' => $plan,
            'planMetrics' => $planMetrics,
            'planPurchases' => FinancePlanSnapshot::query()
                ->where('plan_id', $plan->id)
                ->whereBetween('purchased_at', [$from, $to])
                ->latest('purchased_at')
                ->limit(20)
                ->get(),
            'planOrders' => FinanceOrderSnapshot::query()
                ->where('plan_id', $plan->id)
                ->whereBetween('ordered_at', [$from, $to])
                ->latest('ordered_at')
                ->limit(20)
                ->get(),
        ]);
    }

    private function overviewData(Carbon $from, Carbon $to, FinanceReportService $reports): array
    {
        $plans = FinancePlanSnapshot::query()->whereBetween('purchased_at', [$from, $to]);
        $orders = FinanceOrderSnapshot::query()->whereBetween('ordered_at', [$from, $to]);
        $manualExpenses = FinanceTransaction::query()
            ->where('direction', 'expense')->where('source_type', 'manual')
            ->whereIn('status', ['paid', 'refunded'])->whereBetween('occurred_at', [$from, $to]);
        $paidIncome = FinanceTransaction::query()->where('direction', 'income')
            ->where('status', 'paid')->whereBetween('occurred_at', [$from, $to]);
        $received = (float) (clone $paidIncome)->sum('amount_toman');
        $operatingIncome = (float) (clone $paidIncome)->where('category', '!=', 'investment')->sum('amount_toman');
        $grossSales = (float) (clone $plans)->sum('gross_sales_toman');
        $direct = (float) (clone $orders)->sum('direct_cost_toman');
        $gateway = (float) (clone $plans)->sum('gateway_fee_toman');
        $infrastructure = (float) (clone $manualExpenses)->whereIn('category', ['hosting', 'server', 'domain'])->sum('amount_toman');
        $workforce = (float) (clone $manualExpenses)->where('category', 'salary')->sum('amount_toman');
        $otherExpenses = (float) (clone $manualExpenses)->sum('amount_toman');
        $grossProfit = $grossSales - $direct - $gateway;
        $netProfit = $operatingIncome - $direct - $gateway - $otherExpenses;
        $users = max(1, User::query()->count());
        $buyers = FinancePlanSnapshot::query()->whereBetween('purchased_at', [$from, $to])->distinct('user_id')->count('user_id');

        return [
            'metrics' => [
                'gross_sales' => $grossSales,
                'received' => $received,
                'direct_model_cost' => $direct,
                'infrastructure_cost' => $infrastructure,
                'workforce_cost' => $workforce,
                'gateway_cost' => $gateway,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'margin' => $grossSales > 0 ? round(($netProfit / $grossSales) * 100, 2) : 0,
                'unpaid' => (float) FinanceTransaction::query()->whereIn('status', ['pending', 'overdue'])->whereBetween('occurred_at', [$from, $to])->sum('amount_toman'),
                'conversion' => round(($buyers / $users) * 100, 2),
            ],
            'dailyRows' => $reports->summary('daily', $from, $to)->take(12),
            'alerts' => $this->alerts(),
        ];
    }

    private function transactionData(Request $request, string $section): array
    {
        $query = FinanceTransaction::query()->with(['costCenter', 'vendor', 'paymentMethod', 'creator', 'approver']);
        if ($section === 'expenses') $query->where('direction', 'expense');
        if ($section === 'income') $query->where('direction', 'income');
        $query->when($request->filled('q'), fn ($q) => $q->where(fn ($nested) => $nested
            ->where('title', 'like', '%' . trim((string) $request->q) . '%')
            ->orWhere('reference_code', 'like', '%' . trim((string) $request->q) . '%')));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));
        $query->when($request->filled('category'), fn ($q) => $q->where('category', $request->category));
        $query->when($request->filled('from'), fn ($q) => $q->whereDate('occurred_at', '>=', $request->from));
        $query->when($request->filled('to'), fn ($q) => $q->whereDate('occurred_at', '<=', $request->to));

        return $this->referenceData() + [
            'transactions' => $query->latest('occurred_at')->paginate(20)->withQueryString(),
            'editingTransaction' => $request->integer('edit')
                ? FinanceTransaction::query()->where('source_type', 'manual')->findOrFail($request->integer('edit'))
                : null,
        ];
    }

    private function rateData(): array
    {
        $rates = FinanceExchangeRate::query()->with('creator')->latest('rate_date')->paginate(30);
        $latest = $rates->first();
        $previous = $rates->skip(1)->first();
        $jump = $latest && $previous && (float) $previous->rate_to_toman > 0
            ? round((((float) $latest->rate_to_toman - (float) $previous->rate_to_toman) / (float) $previous->rate_to_toman) * 100, 2)
            : 0;

        return compact('rates', 'latest', 'previous', 'jump');
    }

    private function referenceData(): array
    {
        return [
            'costCenters' => FinanceCostCenter::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'vendors' => FinanceVendor::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'paymentMethods' => FinancePaymentMethod::query()->orderByDesc('is_active')->orderBy('name')->get(),
        ];
    }

    private function reportData(Request $request, FinanceReportService $reports, Carbon $from, Carbon $to): array
    {
        $report = in_array($request->report, ['daily', 'monthly', 'plans', 'products', 'models', 'providers', 'channels', 'users'], true)
            ? $request->report : 'daily';

        return ['report' => $report, 'rows' => $reports->summary($report, $from, $to)];
    }

    private function settingsData(): array
    {
        return [
            'settings' => collect(config('finance.setting_defaults'))->mapWithKeys(
                fn ($default, $key) => [$key => FinanceSetting::valueOf($key, $default)]
            ),
            'auditLogs' => FinanceAuditLog::query()->with('admin')->latest()->paginate(30),
        ];
    }

    private function alerts(): Collection
    {
        $threshold = (float) FinanceSetting::valueOf('exchange_jump_alert_percent', 5);
        $rates = FinanceExchangeRate::query()->latest('rate_date')->limit(2)->get();
        $jump = $rates->count() === 2 && (float) $rates[1]->rate_to_toman > 0
            ? abs((((float) $rates[0]->rate_to_toman - (float) $rates[1]->rate_to_toman) / (float) $rates[1]->rate_to_toman) * 100)
            : 0;
        $costPerCredit = (float) FinanceSetting::valueOf('estimated_model_cost_per_credit_toman', 100);
        $gateway = (float) FinanceSetting::valueOf('gateway_fee_percent', 1);
        $infra = (float) FinanceSetting::valueOf('infrastructure_allocation_percent', 8);
        $workforce = (float) FinanceSetting::valueOf('workforce_allocation_percent', 12);
        $losingPlans = Plan::query()->get()->filter(function (Plan $plan) use ($costPerCredit, $gateway, $infra, $workforce): bool {
            $cost = ((int) $plan->tokens * $costPerCredit) + ((float) $plan->price * ($gateway + $infra + $workforce) / 100);
            return $cost > (float) $plan->price;
        })->count();

        return collect([
            ['level' => 'danger', 'title' => 'حاشیه سود منفی', 'count' => FinanceOrderSnapshot::query()->where('margin_percent', '<', 0)->count()],
            ['level' => 'warning', 'title' => 'مدل فعال بدون قیمت', 'count' => AiModel::query()->where('is_active', true)->where(fn ($q) => $q->whereNull('cost_per_generation_usd')->orWhere('cost_per_generation_usd', '<=', 0))->count()],
            ['level' => 'warning', 'title' => 'جهش نرخ دلار', 'count' => $jump >= $threshold ? 1 : 0],
            ['level' => 'danger', 'title' => 'پلن زیان‌ده با قیمت فعلی', 'count' => $losingPlans],
        ])->filter(fn ($alert) => $alert['count'] > 0)->values();
    }

    private function range(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }
}
