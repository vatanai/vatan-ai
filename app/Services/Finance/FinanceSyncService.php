<?php

namespace App\Services\Finance;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\FinanceCostCenter;
use App\Models\FinanceCreditAllocation;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinancePaymentMethod;
use App\Models\FinancePlanSnapshot;
use App\Models\FinanceSetting;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\PlanPurchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinanceSyncService
{
    public function __construct(
        private readonly FinanceProfitCalculator $calculator,
        private readonly FinanceExchangeRateSnapshotService $rates,
    ) {
    }

    public function syncPlanPurchase(PlanPurchase $purchase): void
    {
        if (! Schema::hasTable('finance_plan_snapshots')) {
            return;
        }

        $existing = FinancePlanSnapshot::query()->where('plan_purchase_id', $purchase->id)->first();
        $sales = (float) $purchase->paid_amount;
        $settings = $this->settings();
        $calculated = $existing && $existing->gross_sales_toman !== null ? [
            'gross_sales_toman' => $existing->gross_sales_toman,
            'gateway_fee_toman' => $existing->gateway_fee_toman,
            'estimated_model_cost_toman' => $existing->estimated_model_cost_toman,
            'allocated_infrastructure_toman' => $existing->allocated_infrastructure_toman,
            'allocated_workforce_toman' => $existing->allocated_workforce_toman,
            'gross_profit_toman' => $existing->gross_profit_toman,
            'net_profit_toman' => $existing->net_profit_toman,
            'margin_percent' => $existing->margin_percent,
        ] : $this->calculator->planToman(
            $sales,
            (int) $purchase->granted_tokens,
            $settings['model_cost_per_credit'],
            $settings['gateway_percent'],
            $settings['infrastructure_percent'],
            $settings['workforce_percent'],
        );
        $channel = $existing?->acquisition_channel ?: $this->channel('plan_purchase_id', $purchase->id);

        FinancePlanSnapshot::query()->updateOrCreate(
            ['plan_purchase_id' => $purchase->id],
            $calculated + [
                'plan_id' => $purchase->plan_id,
                'user_id' => $purchase->user_id,
                'plan_code' => $purchase->plan_code,
                'plan_name' => $purchase->plan_name,
                'model_tier_key' => data_get($purchase->plan_snapshot, 'model_tier_key'),
                'granted_credits' => (int) $purchase->granted_tokens,
                'received_toman' => $purchase->status === 'completed' ? $sales : 0,
                'acquisition_channel' => $channel,
                'formula_version' => FinanceProfitCalculator::FORMULA_VERSION,
                'source_snapshot' => $existing?->source_snapshot ?: [
                    'plan_purchase' => $purchase->toArray(),
                    'settings' => $settings,
                ],
                'purchased_at' => $purchase->purchased_at ?: $purchase->created_at,
                'captured_at' => $existing?->captured_at ?: now(),
            ],
        );

        $methodId = FinancePaymentMethod::query()->where('code', 'gateway')->value('id');
        $centerId = FinanceCostCenter::query()->where('code', 'sales-finance')->value('id');
        $transaction = FinanceTransaction::withTrashed()->firstOrNew(['source_key' => "plan-purchase:{$purchase->id}"]);
        $transaction->fill([
            'direction' => 'income',
            'category' => 'plan_sale',
            'title' => 'فروش پلن ' . $purchase->plan_name,
            'amount_original' => $sales,
            'currency' => 'IRT',
            'exchange_rate_irr' => 1,
            'exchange_rate_toman' => 1,
            'amount_irr' => $sales * 10,
            'amount_toman' => $sales,
            'status' => $purchase->status === 'completed' ? 'paid' : 'pending',
            'occurred_at' => $purchase->purchased_at ?: $purchase->created_at,
            'paid_at' => $purchase->status === 'completed' ? ($purchase->purchased_at ?: $purchase->created_at) : null,
            'cost_center_id' => $centerId,
            'payment_method_id' => $methodId,
            'source_type' => PlanPurchase::class,
            'source_id' => $purchase->id,
            'source_key' => "plan-purchase:{$purchase->id}",
            'user_id' => $purchase->user_id,
            'plan_id' => $purchase->plan_id,
            'plan_purchase_id' => $purchase->id,
            'acquisition_channel' => $channel,
            'metadata' => ['payment_reference' => $purchase->payment_reference],
        ]);
        $transaction->deleted_at = null;
        $transaction->save();
    }

    public function syncProviderRequest(AiProviderRequest $providerRequest): void
    {
        if (! Schema::hasTable('finance_transactions')) {
            return;
        }

        $hasActual = $providerRequest->actual_cost_usd !== null;
        $isSuccessful = in_array($providerRequest->status, ['completed', 'success'], true);
        $costUsd = $hasActual
            ? (float) $providerRequest->actual_cost_usd
            : ($isSuccessful ? (float) $providerRequest->estimated_cost_usd : 0.0);

        $sourceKey = "ai-request:{$providerRequest->id}";
        // برآورد رزرو یا اجرای ناموفق نباید به‌عنوان بدهی قطعی وارد دفتر مالی
        // شود. اگر نسخهٔ قبلی رکورد pending ساخته، آن را شفافاً ناموفق و صفر
        // می‌کنیم تا در گزارش هزینه و سود باقی نماند.
        if ($costUsd <= 0 && !$isSuccessful) {
            $existing = FinanceTransaction::withTrashed()->where('source_key', $sourceKey)->first();
            if ($existing) {
                $existing->fill([
                    'amount_original' => 0,
                    'amount_irr' => 0,
                    'amount_toman' => 0,
                    'status' => 'failed',
                    'paid_at' => null,
                    'metadata' => array_merge((array) $existing->metadata, [
                        'cost_type' => 'none',
                        'provider_status' => $providerRequest->status,
                    ]),
                ]);
                $existing->deleted_at = null;
                $existing->save();
            }
            return;
        }

        if ($costUsd <= 0) {
            return;
        }

        $transaction = FinanceTransaction::withTrashed()->firstOrNew([
            'source_key' => $sourceKey,
        ]);
        $exchangeRate = $transaction->exists && (float) $transaction->exchange_rate_toman > 0
            ? (float) $transaction->exchange_rate_toman
            : $this->rates->rateToman('USD', $providerRequest->submitted_at ?: $providerRequest->created_at);
        $model = $providerRequest->relationLoaded('aiModel')
            ? $providerRequest->aiModel
            : $providerRequest->aiModel()->first();

        $transaction->fill([
            'direction' => 'expense',
            'category' => 'ai_model',
            'title' => 'هزینه اجرای مدل ' . ($model?->name ?: $providerRequest->provider),
            'amount_original' => $costUsd,
            'currency' => 'USD',
            'exchange_rate_irr' => $exchangeRate * 10,
            'exchange_rate_toman' => $exchangeRate,
            'amount_irr' => round($costUsd * $exchangeRate * 10, 2),
            'amount_toman' => round($costUsd * $exchangeRate, 2),
            'status' => $hasActual ? 'paid' : 'pending',
            'occurred_at' => $providerRequest->submitted_at ?: $providerRequest->created_at,
            'paid_at' => $hasActual ? ($providerRequest->completed_at ?: now()) : null,
            'cost_center_id' => FinanceCostCenter::query()->where('code', 'ai')->value('id'),
            'source_type' => AiProviderRequest::class,
            'source_id' => $providerRequest->id,
            'source_key' => $sourceKey,
            'order_id' => $providerRequest->order_id,
            'ai_model_id' => $providerRequest->ai_model_id,
            'provider' => $providerRequest->provider,
            'metadata' => [
                'external_request_id' => $providerRequest->external_request_id,
                'cost_type' => $hasActual ? 'actual' : 'estimated',
            ],
        ]);
        $transaction->deleted_at = null;
        $transaction->save();

        if ($providerRequest->order_id) {
            $this->syncOrder(Order::query()->find($providerRequest->order_id));
        }
    }

    public function syncOrder(?Order $order): void
    {
        if (! $order || ! Schema::hasTable('finance_order_snapshots')) {
            return;
        }

        $order->loadMissing(['product', 'providerRequests.aiModel']);
        $existing = FinanceOrderSnapshot::query()->where('order_id', $order->id)->first();
        $exactAllocation = Schema::hasTable('finance_credit_allocations')
            ? FinanceCreditAllocation::query()->with('financeCase:id,anchor_plan_purchase_id')->where('order_id', $order->id)->oldest('id')->first()
            : null;
        $exactPurchaseId = $exactAllocation?->financeCase?->anchor_plan_purchase_id;
        $purchase = $exactPurchaseId
            ? PlanPurchase::query()->find($exactPurchaseId)
            : ($existing?->plan_purchase_id
                ? PlanPurchase::query()->find($existing->plan_purchase_id)
                : $this->purchaseForOrder($order));
        $rate = $existing && (float) $existing->exchange_rate_toman > 0
            ? (float) $existing->exchange_rate_toman
            : $this->rates->rateToman('USD', $order->created_at);
        $estimatedUsd = (float) $order->providerRequests->sum(fn ($request) =>
            $request->actual_cost_usd === null && in_array($request->status, ['completed', 'success'], true)
                ? (float) ($request->estimated_cost_usd ?? 0)
                : 0
        );
        $actualUsd = (float) $order->providerRequests->sum(fn ($request) => (float) ($request->actual_cost_usd ?? 0));
        $costUsd = (float) $order->providerRequests->sum(fn ($request) => $request->actual_cost_usd !== null
            ? (float) $request->actual_cost_usd
            : (in_array($request->status, ['completed', 'success'], true) ? (float) ($request->estimated_cost_usd ?? 0) : 0));
        $actualRequestCount = $order->providerRequests->whereNotNull('actual_cost_usd')->count();
        $estimatedRequestCount = $order->providerRequests->filter(fn ($request) => $request->actual_cost_usd === null && in_array($request->status, ['completed', 'success'], true) && $request->estimated_cost_usd !== null)->count();
        $missingRequestCount = $order->providerRequests->filter(fn ($request) => in_array($request->status, ['completed', 'success'], true) && $request->actual_cost_usd === null && $request->estimated_cost_usd === null)->count();

        if ($estimatedUsd <= 0 && $order->ai_model) {
            $estimatedUsd = (float) (AiModel::query()
                ->where('openrouter_model_id', $order->ai_model)
                ->orWhere('external_model_id', $order->ai_model)
                ->value('cost_per_generation_usd') ?? 0);
            if ($costUsd <= 0) {
                $costUsd = $estimatedUsd;
                $estimatedRequestCount = $estimatedUsd > 0 ? 1 : 0;
                $missingRequestCount = $estimatedUsd > 0 ? 0 : 1;
            }
        }

        $directCost = $costUsd * $rate;
        $revenue = $exactAllocation
            ? (float) FinanceCreditAllocation::query()->where('order_id', $order->id)->sum('revenue_toman')
            : ($existing
                ? (float) ($existing->allocated_revenue_toman ?? $existing->allocated_revenue_irr)
                : $this->allocatedRevenue($order, $purchase));
        $settings = $this->settings();
        $calculated = $this->calculator->orderToman(
            $revenue,
            $directCost,
            $exactAllocation ? $settings['infrastructure_percent'] : ($existing ? $this->percentFromAllocation($existing->allocated_infrastructure_toman ?? $existing->allocated_infrastructure_irr, $revenue) : $settings['infrastructure_percent']),
            $exactAllocation ? $settings['workforce_percent'] : ($existing ? $this->percentFromAllocation($existing->allocated_workforce_toman ?? $existing->allocated_workforce_irr, $revenue) : $settings['workforce_percent']),
        );
        $actualRequest = $order->providerRequests
            ->sortByDesc(fn ($request) => $request->actual_cost_usd !== null ? 1 : 0)
            ->first();
        $product = $order->product;

        FinanceOrderSnapshot::query()->updateOrCreate(
            ['order_id' => $order->id],
            $calculated + [
                'plan_purchase_id' => $exactPurchaseId ?: ($existing?->plan_purchase_id ?: $purchase?->id),
                'plan_id' => $existing?->plan_id ?: ($order->plan_id ?: $purchase?->plan_id),
                'product_id' => $existing?->product_id ?: $order->product_id,
                'user_id' => $existing?->user_id ?: $order->user_id,
                'order_number' => $existing?->order_number ?: $order->order_number,
                'plan_name' => $existing?->plan_name ?: ($order->plan_name ?: $purchase?->plan_name),
                'model_tier_key' => $existing?->model_tier_key ?: ($order->model_tier_key ?: data_get($purchase?->plan_snapshot, 'model_tier_key')),
                'product_name' => $existing?->product_name ?: $product?->name_fa,
                'primary_model' => $existing?->primary_model ?: ($product?->primary_model ?: $order->ai_model),
                'fallback_models' => $existing?->fallback_models ?: ($product?->fallback_models ?: []),
                'actual_model' => $actualRequest?->aiModel?->externalModelId() ?: $order->ai_model,
                'provider' => $actualRequest?->provider ?: $order->ai_provider,
                'credits_used' => (int) $order->final_credits,
                'estimated_cost_usd' => $estimatedUsd,
                'actual_cost_usd' => $actualUsd,
                'exchange_rate_irr' => $rate * 10,
                'estimated_cost_irr' => round($estimatedUsd * $rate * 10, 2),
                'actual_cost_irr' => round($actualUsd * $rate * 10, 2),
                'direct_cost_irr' => round($directCost * 10, 2),
                'exchange_rate_toman' => $rate,
                'estimated_cost_toman' => round($estimatedUsd * $rate, 2),
                'actual_cost_toman' => round($actualUsd * $rate, 2),
                'cost_quality' => $missingRequestCount > 0 ? 'missing' : ($actualRequestCount > 0 && $estimatedRequestCount > 0 ? 'mixed' : ($actualRequestCount > 0 ? 'actual' : 'estimated')),
                'actual_request_count' => $actualRequestCount,
                'estimated_request_count' => $estimatedRequestCount,
                'missing_cost_request_count' => $missingRequestCount,
                'acquisition_channel' => $existing?->acquisition_channel ?: $this->channel('order_id', $order->id),
                'formula_version' => FinanceProfitCalculator::FORMULA_VERSION,
                'source_snapshot' => $existing?->source_snapshot ?: [
                    'order' => $order->withoutRelations()->toArray(),
                    'product' => $product?->only(['id', 'name_fa', 'primary_model', 'fallback_models', 'credit_cost']),
                    'purchase' => $purchase?->toArray(),
                    'settings' => $settings,
                ],
                'ordered_at' => $existing?->ordered_at ?: $order->created_at,
                'completed_at' => $order->completed_at,
                'captured_at' => $existing?->captured_at ?: now(),
            ],
        );
    }

    public function syncExisting(): array
    {
        $counts = ['purchases' => 0, 'orders' => 0, 'provider_requests' => 0];

        PlanPurchase::query()->orderBy('id')->chunkById(100, function ($purchases) use (&$counts): void {
            foreach ($purchases as $purchase) {
                $this->syncPlanPurchase($purchase);
                $counts['purchases']++;
            }
        });
        Order::query()->orderBy('id')->chunkById(100, function ($orders) use (&$counts): void {
            foreach ($orders as $order) {
                $this->syncOrder($order);
                $counts['orders']++;
            }
        });
        AiProviderRequest::query()->orderBy('id')->chunkById(100, function ($requests) use (&$counts): void {
            foreach ($requests as $request) {
                $this->syncProviderRequest($request);
                $counts['provider_requests']++;
            }
        });

        return $counts;
    }

    private function settings(): array
    {
        return [
            'gateway_percent' => (float) FinanceSetting::valueOf('gateway_fee_percent', 1),
            'infrastructure_percent' => (float) FinanceSetting::valueOf('infrastructure_allocation_percent', 8),
            'workforce_percent' => (float) FinanceSetting::valueOf('workforce_allocation_percent', 12),
            'model_cost_per_credit' => (float) FinanceSetting::valueOf('estimated_model_cost_per_credit_toman', 100),
        ];
    }

    private function purchaseForOrder(Order $order): ?PlanPurchase
    {
        if (! $order->user_id) {
            return null;
        }

        return PlanPurchase::query()
            ->where('user_id', $order->user_id)
            ->where('status', 'completed')
            ->where('purchased_at', '<=', $order->created_at)
            ->when($order->plan_id, fn ($query) => $query->orderByRaw('plan_id = ? desc', [$order->plan_id]))
            ->latest('purchased_at')
            ->first();
    }

    private function allocatedRevenue(Order $order, ?PlanPurchase $purchase): float
    {
        if ($purchase && (int) $purchase->granted_tokens > 0) {
            return round(((float) $purchase->paid_amount / (int) $purchase->granted_tokens) * (int) $order->final_credits, 2);
        }

        $plan = $order->plan;
        if ($plan && (int) $plan->tokens > 0) {
            return round(((float) $plan->price / (int) $plan->tokens) * (int) $order->final_credits, 2);
        }

        return 0;
    }

    private function channel(string $column, int $id): ?string
    {
        try {
            if (! Schema::hasTable('growth_attributions') || ! Schema::hasTable('growth_links')) {
                return null;
            }

            return DB::table('growth_attributions')
                ->join('growth_links', 'growth_links.id', '=', 'growth_attributions.growth_link_id')
                ->where("growth_attributions.{$column}", $id)
                ->latest('growth_attributions.attributed_at')
                ->value('growth_links.channel');
        } catch (Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function percentFromAllocation(float|string|null $allocation, float $revenue): float
    {
        return $revenue > 0 ? ((float) $allocation / $revenue) * 100 : 0;
    }
}
