<?php

namespace App\Services\Finance;

use App\Models\FinanceOrderSnapshot;
use App\Models\FinancePlanSnapshot;
use App\Models\FinanceTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class FinanceReportService
{
    public function summary(string $dimension, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return match ($dimension) {
            'plans' => $this->plans($from, $to),
            'products' => $this->ordersBy('product_name', $from, $to),
            'models' => $this->ordersBy('actual_model', $from, $to),
            'providers' => $this->ordersBy('provider', $from, $to),
            'channels' => $this->ordersBy('acquisition_channel', $from, $to),
            'users' => $this->ordersBy('user_id', $from, $to),
            'monthly' => $this->periods('Y-m', $from, $to),
            default => $this->periods('Y-m-d', $from, $to),
        };
    }

    public function plans(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $sales = FinancePlanSnapshot::query()
            ->whereBetween('purchased_at', [$from, $to])
            ->get()
            ->groupBy(fn ($row) => $row->plan_id ?: $row->plan_name);
        $orders = FinanceOrderSnapshot::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->get()
            ->groupBy(fn ($row) => $row->plan_id ?: $row->plan_name);

        return $sales->keys()->merge($orders->keys())->unique()->map(function ($key) use ($sales, $orders): array {
            $purchases = $sales->get($key, collect());
            $executions = $orders->get($key, collect());
            $revenue = (float) $purchases->sum('gross_sales_toman');
            $estimated = (float) $purchases->sum('estimated_model_cost_toman');
            $direct = (float) $executions->sum('direct_cost_toman');
            $allocated = (float) $purchases->sum('allocated_infrastructure_toman')
                + (float) $purchases->sum('allocated_workforce_toman');
            $profit = $revenue - $direct - (float) $purchases->sum('gateway_fee_toman') - $allocated;

            return [
                'plan_id' => $purchases->first()?->plan_id ?: $executions->first()?->plan_id,
                'label' => $purchases->first()?->plan_name ?: ($executions->first()?->plan_name ?: 'بدون پلن'),
                'count' => $purchases->count(),
                'credits' => (int) $purchases->sum('granted_credits'),
                'revenue' => round($revenue, 2),
                'estimated_cost' => round($estimated, 2),
                'direct_cost' => round($direct, 2),
                'allocated_cost' => round($allocated, 2),
                'profit' => round($profit, 2),
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
            ];
        })->sortByDesc('revenue')->values();
    }

    private function ordersBy(string $column, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return FinanceOrderSnapshot::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->get()
            ->groupBy(fn ($row) => $row->{$column} ?: 'نامشخص')
            ->map(function (Collection $items, string|int $label): array {
                $revenue = (float) $items->sum('allocated_revenue_toman');
                $profit = (float) $items->sum('net_profit_toman');

                return [
                    'label' => (string) $label,
                    'count' => $items->count(),
                    'credits' => (int) $items->sum('credits_used'),
                    'revenue' => round($revenue, 2),
                    'estimated_cost' => round((float) $items->sum('estimated_cost_toman'), 2),
                    'direct_cost' => round((float) $items->sum('direct_cost_toman'), 2),
                    'allocated_cost' => round((float) $items->sum('allocated_infrastructure_toman') + (float) $items->sum('allocated_workforce_toman'), 2),
                    'profit' => round($profit, 2),
                    'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                ];
            })->sortByDesc('revenue')->values();
    }

    private function periods(string $format, CarbonInterface $from, CarbonInterface $to): Collection
    {
        $sales = FinancePlanSnapshot::query()->whereBetween('purchased_at', [$from, $to])->get();
        $orders = FinanceOrderSnapshot::query()->whereBetween('ordered_at', [$from, $to])->get();
        $manualExpenses = FinanceTransaction::query()
            ->where('direction', 'expense')
            ->where('source_type', 'manual')
            ->whereIn('status', ['paid', 'refunded'])
            ->whereBetween('occurred_at', [$from, $to])
            ->get();
        $manualIncome = FinanceTransaction::query()
            ->where('direction', 'income')
            ->where('source_type', 'manual')
            ->where('category', '!=', 'investment')
            ->where('status', 'paid')
            ->whereBetween('occurred_at', [$from, $to])
            ->get();
        $periods = collect()
            ->concat($sales->map(fn ($row) => $row->purchased_at->format($format)))
            ->concat($orders->map(fn ($row) => $row->ordered_at->format($format)))
            ->concat($manualExpenses->map(fn ($row) => $row->occurred_at->format($format)))
            ->concat($manualIncome->map(fn ($row) => $row->occurred_at->format($format)))
            ->unique();

        return $periods->map(function (string $period) use ($format, $sales, $orders, $manualExpenses, $manualIncome): array {
            $planItems = $sales->filter(fn ($row) => $row->purchased_at->format($format) === $period);
            $orderItems = $orders->filter(fn ($row) => $row->ordered_at->format($format) === $period);
            $expenseItems = $manualExpenses->filter(fn ($row) => $row->occurred_at->format($format) === $period);
            $incomeItems = $manualIncome->filter(fn ($row) => $row->occurred_at->format($format) === $period);
            $revenue = (float) $planItems->sum('received_toman') + (float) $incomeItems->sum('amount_toman');
            $direct = (float) $orderItems->sum('direct_cost_toman');
            $allocated = (float) $planItems->sum('gateway_fee_toman')
                + (float) $planItems->sum('allocated_infrastructure_toman')
                + (float) $planItems->sum('allocated_workforce_toman')
                + (float) $expenseItems->sum('amount_toman');
            $profit = $revenue - $direct - $allocated;

            return [
                'label' => $period,
                'count' => $planItems->count() + $orderItems->count() + $incomeItems->count() + $expenseItems->count(),
                'credits' => (int) $planItems->sum('granted_credits'),
                'revenue' => round($revenue, 2),
                'estimated_cost' => round((float) $planItems->sum('estimated_model_cost_toman'), 2),
                'direct_cost' => round($direct, 2),
                'allocated_cost' => round($allocated, 2),
                'profit' => round($profit, 2),
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
            ];
        })->sortByDesc('label')->values();
    }
}
