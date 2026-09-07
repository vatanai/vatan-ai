<?php

namespace App\Services\Finance;

use App\Models\AiProviderRequest;
use App\Models\FinanceCase;
use App\Models\FinanceCreditAllocation;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinanceTransaction;
use App\Models\GeneratedImage;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FinanceCaseAnalysisService
{
    public function __construct(private readonly FinanceExchangeRateSnapshotService $rates)
    {
    }

    public function analyze(FinanceCase $case): array
    {
        $case->loadMissing(['user.plan', 'purchase.plan', 'lots', 'events']);
        if ($case->user) {
            $case->user->loadCount(['generatedImages', 'financeCases']);
            if (Schema::hasTable('auth_events')) {
                $case->user->loadMissing('lastSuccessfulLogin');
            }
        }
        $allocations = FinanceCreditAllocation::query()
            ->with(['lot', 'order.product'])
            ->where('finance_case_id', $case->id)
            ->get();
        $orderIds = $allocations->pluck('order_id')->filter()->unique()->values();
        $orders = Order::query()->with(['product', 'providerRequests.aiModel'])
            ->whereIn('id', $orderIds)->orderBy('created_at')->get();
        $snapshots = FinanceOrderSnapshot::query()->whereIn('order_id', $orderIds)->get()->keyBy('order_id');
        $providerRequests = AiProviderRequest::query()->with('aiModel')->whereIn('order_id', $orderIds)->get();
        $providerCosts = $this->providerCosts($providerRequests);

        $purchase = $case->purchase;
        $planCredits = (int) ($purchase?->granted_tokens ?? $case->lots->where('source_type', 'plan_purchase')->sum('credits_granted'));
        $giftCredits = (int) $case->lots->where('source_type', '!=', 'plan_purchase')->sum('credits_granted');
        $creditsGranted = (int) $case->lots->sum('credits_granted');
        $creditsRemaining = (int) $case->lots->sum('credits_remaining');
        $creditsUsed = (int) $allocations->sum(fn ($row) => max(0, (int) $row->credits_used - (int) $row->credits_refunded));
        $creditsRefunded = (int) $allocations->sum('credits_refunded');
        $revenue = (float) ($purchase?->paid_amount ?? 0);
        $recognizedRevenue = (float) $allocations->sum('revenue_toman');
        $unallocatedRevenue = max(0, $revenue - $recognizedRevenue);

        $snapshot = $purchase?->financeSnapshot;
        $gateway = (float) ($snapshot?->gateway_fee_toman ?? ($revenue * 0.01));
        $infrastructure = (float) ($snapshot?->allocated_infrastructure_toman ?? ($revenue * 0.08));
        $workforce = (float) ($snapshot?->allocated_workforce_toman ?? ($revenue * 0.12));
        $directUsd = (float) $providerCosts->sum('cost_usd');
        $directToman = (float) $providerCosts->sum('cost_toman');
        $planUtilization = $planCredits > 0
            ? min(1, (float) $allocations->where('lot.source_type', 'plan_purchase')->sum(fn ($row) => max(0, (int) $row->credits_used - (int) $row->credits_refunded)) / $planCredits)
            : 0;
        $recognizedOverhead = ($gateway + $infrastructure + $workforce) * $planUtilization;
        $realizedProfit = $recognizedRevenue - $directToman - $recognizedOverhead;
        $costPerUsedCredit = $creditsUsed > 0 ? $directToman / $creditsUsed : 0;
        $projectedDirect = $creditsUsed > 0
            ? $directToman + ($costPerUsedCredit * $creditsRemaining)
            : (float) ($snapshot?->estimated_model_cost_toman ?? 0);
        $projectedUsd = $creditsUsed > 0
            ? $directUsd + (($directUsd / $creditsUsed) * $creditsRemaining)
            : 0;
        $projectedProfit = $revenue - $projectedDirect - $gateway - $infrastructure - $workforce;
        $projectedMargin = $revenue > 0 ? ($projectedProfit / $revenue) * 100 : 0;
        $variableCostRate = $revenue > 0 ? ($gateway + $infrastructure + $workforce) / $revenue : 0.21;
        $recommendedPrice = $projectedDirect > 0
            ? $projectedDirect / max(0.05, 0.70 - $variableCostRate)
            : 0;
        $breakEvenRate = $projectedUsd > 0
            ? max(0, ($revenue - $gateway - $infrastructure - $workforce) / $projectedUsd)
            : 0;

        $gatewayRate = $revenue > 0 ? $gateway / $revenue : 0.01;
        $products = $this->products($orders, $allocations, $providerCosts, $snapshots, $gatewayRate);
        $daily = $this->daily($orders, $allocations, $providerCosts, $snapshots, $gatewayRate);
        $sources = $case->lots->groupBy('source_type')->map(function (Collection $lots, string $type): array {
            return [
                'key' => $type,
                'label' => $this->sourceLabel($type),
                'credits' => (int) $lots->sum('credits_granted'),
                'remaining' => (int) $lots->sum('credits_remaining'),
            ];
        })->values();
        $providers = $providerCosts->groupBy('provider')->map(function (Collection $items, string $provider): array {
            return [
                'provider' => $provider ?: 'نامشخص',
                'attempts' => $items->count(),
                'cost_usd' => round((float) $items->sum('cost_usd'), 6),
                'cost_toman' => round((float) $items->sum('cost_toman'), 2),
                'actual' => $items->where('quality', 'actual')->count(),
                'estimated' => $items->where('quality', 'estimated')->count(),
                'missing' => $items->where('quality', 'missing')->count(),
            ];
        })->sortByDesc('cost_toman')->values();
        $quality = $this->quality($providerCosts);

        return [
            'case' => $case,
            'purchase' => $purchase,
            'orders' => $orders,
            'allocations' => $allocations,
            'events' => $case->events()->with(['order.product', 'tokenLog.admin'])->limit(150)->get(),
            'products' => $products,
            'providers' => $providers,
            'sources' => $sources,
            'daily' => $daily,
            'quality' => $quality,
            'metrics' => [
                'revenue' => round($revenue, 2),
                'recognized_revenue' => round($recognizedRevenue, 2),
                'unallocated_revenue' => round($unallocatedRevenue, 2),
                'direct_cost_usd' => round($directUsd, 6),
                'direct_cost_toman' => round($directToman, 2),
                'gateway_cost' => round($gateway, 2),
                'infrastructure_cost' => round($infrastructure, 2),
                'workforce_cost' => round($workforce, 2),
                'realized_profit' => round($realizedProfit, 2),
                'realized_margin' => $recognizedRevenue > 0 ? round(($realizedProfit / $recognizedRevenue) * 100, 2) : 0,
                'projected_direct_cost' => round($projectedDirect, 2),
                'projected_profit' => round($projectedProfit, 2),
                'projected_margin' => round($projectedMargin, 2),
                'recommended_price' => round($recommendedPrice, 2),
                'break_even_usd_rate' => round($breakEvenRate, 2),
                'credits_plan' => $planCredits,
                'credits_gift' => $giftCredits,
                'credits_granted' => $creditsGranted,
                'credits_used' => $creditsUsed,
                'credits_refunded' => $creditsRefunded,
                'credits_remaining' => $creditsRemaining,
                'utilization' => $creditsGranted > 0 ? round(($creditsUsed / $creditsGranted) * 100, 2) : 0,
                'user_cost_per_credit' => $planCredits > 0 ? round($revenue / $planCredits, 2) : 0,
                'vatan_cost_per_credit' => round($costPerUsedCredit, 2),
                'orders_count' => $orders->count(),
                'outputs_count' => GeneratedImage::query()->whereIn('order_id', $orderIds)->count(),
                'failed_orders_count' => $orders->whereIn('processing_status', ['failed', 'stopped', 'expired'])->count(),
                'retry_count' => (int) $orders->sum(fn ($order) => max(0, (int) $order->attempts - 1)),
            ],
        ];
    }

    private function providerCosts(Collection $requests): Collection
    {
        $transactions = FinanceTransaction::query()
            ->where('source_type', AiProviderRequest::class)
            ->whereIn('source_id', $requests->pluck('id'))
            ->get()->keyBy('source_id');

        return $requests->map(function (AiProviderRequest $request) use ($transactions): array {
            $transaction = $transactions->get($request->id);
            $isSuccessful = in_array($request->status, ['completed', 'success'], true);
            $quality = $request->actual_cost_usd !== null
                ? 'actual'
                : ($isSuccessful && $request->estimated_cost_usd !== null ? 'estimated' : 'missing');
            $usd = $request->actual_cost_usd !== null
                ? (float) $request->actual_cost_usd
                : ($isSuccessful ? (float) ($request->estimated_cost_usd ?? 0) : 0.0);
            $rate = (float) ($transaction?->exchange_rate_toman ?? 0);
            if ($rate <= 0) {
                $rate = $this->rates->rateToman('USD', $request->submitted_at ?: $request->created_at);
            }

            return [
                'request_id' => $request->id,
                'order_id' => $request->order_id,
                'provider' => (string) $request->provider,
                'model' => $request->aiModel?->name ?: $request->aiModel?->externalModelId() ?: 'مدل نامشخص',
                'status' => $request->status,
                'quality' => $quality,
                'cost_usd' => round($usd, 6),
                'rate_toman' => round($rate, 2),
                'cost_toman' => round($usd * $rate, 2),
                'occurred_at' => $request->completed_at ?: $request->submitted_at ?: $request->created_at,
            ];
        });
    }

    private function products(Collection $orders, Collection $allocations, Collection $costs, Collection $snapshots, float $gatewayRate): Collection
    {
        return $orders->groupBy(fn (Order $order) => $order->product_id ?: 'deleted')->map(function (Collection $items) use ($allocations, $costs, $snapshots, $gatewayRate): array {
            $orderIds = $items->pluck('id');
            $rows = $allocations->whereIn('order_id', $orderIds);
            $productCosts = $costs->whereIn('order_id', $orderIds);
            $credits = (int) $rows->sum(fn ($row) => max(0, (int) $row->credits_used - (int) $row->credits_refunded));
            $revenue = (float) $rows->sum('revenue_toman');
            $direct = (float) $productCosts->sum('cost_toman');
            $allocated = (float) $items->sum(function (Order $order) use ($snapshots): float {
                $snapshot = $snapshots->get($order->id);
                return (float) ($snapshot?->allocated_infrastructure_toman ?? 0) + (float) ($snapshot?->allocated_workforce_toman ?? 0);
            });
            $gateway = $revenue * $gatewayRate;
            $indirect = $allocated + $gateway;
            $vatanCost = $direct + $indirect;
            $profit = $revenue - $vatanCost;

            return [
                'product_id' => $items->first()?->product_id,
                'name' => $items->first()?->product?->name_fa ?: 'محصول حذف‌شده',
                'orders' => $items->count(),
                'outputs' => GeneratedImage::query()->whereIn('order_id', $orderIds)->count(),
                'credits' => $credits,
                'revenue' => round($revenue, 2),
                'cost_usd' => round((float) $productCosts->sum('cost_usd'), 6),
                'direct_cost' => round($direct, 2),
                'allocated_cost' => round($indirect, 2),
                'vatan_cost' => round($vatanCost, 2),
                'profit' => round($profit, 2),
                'margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0,
                'success_rate' => $items->count() ? round($items->where('processing_status', 'completed')->count() * 100 / $items->count(), 1) : 0,
                'avg_duration' => (int) round($items->whereNotNull('processing_duration_ms')->avg('processing_duration_ms') ?? 0),
                'quality' => $this->quality($productCosts),
            ];
        })->sortByDesc('direct_cost')->values();
    }

    private function daily(Collection $orders, Collection $allocations, Collection $costs, Collection $snapshots, float $gatewayRate): Collection
    {
        return $orders->groupBy(fn (Order $order) => $order->created_at->format('Y-m-d'))->map(function (Collection $items, string $date) use ($allocations, $costs, $snapshots, $gatewayRate): array {
            $orderIds = $items->pluck('id');
            $revenue = (float) $allocations->whereIn('order_id', $orderIds)->sum('revenue_toman');
            $direct = (float) $costs->whereIn('order_id', $orderIds)->sum('cost_toman');
            $indirect = (float) $items->sum(function (Order $order) use ($snapshots): float {
                $snapshot = $snapshots->get($order->id);
                return (float) ($snapshot?->allocated_infrastructure_toman ?? 0) + (float) ($snapshot?->allocated_workforce_toman ?? 0);
            });
            $cost = $direct + $indirect + ($revenue * $gatewayRate);
            return [
                'date' => $date,
                'label' => substr($date, 5),
                'revenue' => round($revenue, 2),
                'cost' => round($cost, 2),
                'profit' => round($revenue - $cost, 2),
                'credits' => (int) $allocations->whereIn('order_id', $orderIds)->sum(fn ($row) => max(0, (int) $row->credits_used - (int) $row->credits_refunded)),
            ];
        })->sortBy('date')->values();
    }

    private function quality(Collection $costs): array
    {
        $actual = $costs->where('quality', 'actual')->count();
        $estimated = $costs->where('quality', 'estimated')->count();
        $missing = $costs->where('quality', 'missing')->count();
        $total = $costs->count();
        $key = $missing > 0 ? 'missing' : ($actual > 0 && $estimated > 0 ? 'mixed' : ($actual > 0 ? 'actual' : ($estimated > 0 ? 'estimated' : 'missing')));

        return [
            'key' => $key,
            'label' => ['actual' => 'قطعی', 'mixed' => 'ترکیبی', 'estimated' => 'تخمینی', 'missing' => 'ناقص'][$key],
            'actual' => $actual,
            'estimated' => $estimated,
            'missing' => $missing,
            'coverage' => $total > 0 ? round(($actual / $total) * 100, 1) : 0,
        ];
    }

    private function sourceLabel(string $type): string
    {
        return match ($type) {
            'plan_purchase' => 'خرید پلن',
            'gift', 'registration_gift' => 'هدیه دستی یا ثبت‌نام',
            'plan_upgrade' => 'هدیه ارتقای پلن',
            'referral' => 'دعوت دوستان',
            'paid_adjustment' => 'اصلاح اعتبار خریداری‌شده',
            'opening_balance' => 'مانده تاریخی',
            default => 'اصلاح دستی',
        };
    }
}
