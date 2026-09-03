<?php

namespace App\Services\Finance;

use App\Models\AiProviderRequest;
use App\Models\FinanceCase;
use App\Models\FinanceCaseEvent;
use App\Models\FinanceCreditAllocation;
use App\Models\FinanceCreditLot;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FinanceCaseLedgerService
{
    public function __construct(private readonly FinanceExchangeRateSnapshotService $rates)
    {
    }

    public function recordPurchase(PlanPurchase $purchase): ?FinanceCase
    {
        if ($purchase->status !== PlanPurchase::COMPLETED || ! Schema::hasTable('finance_cases')) {
            return null;
        }

        return DB::transaction(function () use ($purchase): FinanceCase {
            $purchase->loadMissing('user');
            $purchaseAt = $purchase->purchased_at ?: $purchase->created_at;
            FinanceCase::query()
                ->where('user_id', $purchase->user_id)
                ->where('status', 'open')
                ->where('anchor_plan_purchase_id', '!=', $purchase->id)
                ->where('started_at', '<=', $purchaseAt)
                ->update([
                    'status' => 'closed',
                    'ended_at' => $purchaseAt,
                    'closed_at' => now(),
                ]);

            $case = FinanceCase::query()->firstOrCreate(
                ['anchor_plan_purchase_id' => $purchase->id],
                [
                    'case_number' => $this->caseNumber($purchase),
                    'user_id' => $purchase->user_id,
                    'title' => 'پرونده خرید ' . $purchase->plan_name,
                    'status' => 'open',
                    'opening_balance' => max(0, (int) ($purchase->user?->tokens ?? 0) - (int) $purchase->granted_tokens),
                    'started_at' => $purchaseAt,
                    'metadata' => ['plan_snapshot' => $purchase->plan_snapshot],
                ],
            );

            FinanceCreditLot::query()->firstOrCreate(
                ['source_key' => 'plan-purchase:' . $purchase->id],
                [
                    'finance_case_id' => $case->id,
                    'user_id' => $purchase->user_id,
                    'plan_purchase_id' => $purchase->id,
                    'source_type' => 'plan_purchase',
                    'source_label' => 'اعتبار خرید پلن ' . $purchase->plan_name,
                    'credits_granted' => (int) $purchase->granted_tokens,
                    'credits_remaining' => (int) $purchase->granted_tokens,
                    'revenue_toman' => (float) $purchase->paid_amount,
                    'is_promotional' => false,
                    'occurred_at' => $purchaseAt,
                    'metadata' => [
                        'plan_code' => $purchase->plan_code,
                        'base_credits' => (int) data_get($purchase->plan_snapshot, 'tokens', $purchase->granted_tokens),
                        'bonus_credits' => (int) data_get($purchase->plan_snapshot, 'bonus_tokens', 0),
                    ],
                ],
            );

            $this->event(
                $case,
                'purchase:' . $purchase->id,
                'plan_purchase',
                'plan_purchase',
                'خرید پلن با موفقیت ثبت شد',
                $purchase->plan_name . ' با ' . number_format((int) $purchase->granted_tokens) . ' اعتبار',
                (int) $purchase->granted_tokens,
                (int) ($purchase->user?->tokens ?? $purchase->granted_tokens),
                null,
                (float) $purchase->paid_amount,
                'actual',
                $purchaseAt,
                ['order_number' => $purchase->order_number, 'payment_reference' => $purchase->payment_reference],
                $purchase->id,
            );

            return $case;
        });
    }

    public function recordManualCredit(TokenLog $log): void
    {
        if (! Schema::hasTable('finance_cases')) {
            return;
        }

        DB::transaction(function () use ($log): void {
            $log->loadMissing('user');
            if (! $log->user) {
                return;
            }

            $case = $this->activeCaseForUser($log->user, $log->created_at);
            $delta = (int) $log->balance_after - (int) $log->balance_before;
            $kind = (string) data_get($log->metadata, 'credit_kind', $log->source ?: 'manual_adjustment');
            $isPromotional = (bool) data_get($log->metadata, 'is_promotional', in_array($kind, ['gift', 'plan_upgrade', 'registration_gift', 'referral'], true));

            if ($delta > 0) {
                FinanceCreditLot::query()->firstOrCreate(
                    ['source_key' => 'token-log:' . $log->id],
                    [
                        'finance_case_id' => $case->id,
                        'user_id' => $log->user_id,
                        'token_log_id' => $log->id,
                        'source_type' => $kind,
                        'source_label' => $this->creditKindLabel($kind),
                        'credits_granted' => $delta,
                        'credits_remaining' => $delta,
                        'revenue_toman' => 0,
                        'is_promotional' => $isPromotional,
                        'occurred_at' => $log->created_at,
                        'metadata' => ['note' => $log->note, 'admin_id' => $log->admin_id],
                    ],
                );
            } elseif ($delta < 0) {
                $this->reduceLots($log->user, abs($delta), false);
            }

            $this->event(
                $case,
                'token-log:' . $log->id,
                $delta >= 0 ? 'credit_granted' : 'credit_deducted',
                $kind,
                $delta >= 0 ? $this->creditKindLabel($kind) . ' ثبت شد' : 'اعتبار به‌صورت دستی کسر شد',
                $log->note,
                $delta,
                (int) $log->balance_after,
                null,
                0,
                'actual',
                $log->created_at,
                ['action' => $log->action, 'admin_id' => $log->admin_id],
                null,
                null,
                $log->id,
            );
        });
    }

    public function recordPlanChange(User $user, ?Plan $before, ?Plan $after, ?int $adminId = null): void
    {
        if (! Schema::hasTable('finance_cases')) {
            return;
        }

        $case = $this->activeCaseForUser($user, now());
        $from = $before?->name ?: 'رایگان';
        $to = $after?->name ?: 'رایگان';
        $this->event(
            $case,
            'plan-change:' . $user->id . ':' . Str::uuid(),
            'plan_changed',
            'manual_plan_change',
            'پلن کاربر تغییر کرد',
            "پلن از «{$from}» به «{$to}» تغییر کرد؛ موجودی اعتبار مستقل است.",
            0,
            (int) $user->tokens,
            null,
            null,
            'actual',
            now(),
            ['before_plan_id' => $before?->id, 'after_plan_id' => $after?->id, 'admin_id' => $adminId],
        );
    }

    /**
     * @return string|null کلید رزرو دفتر مالی که همراه رزرو کیف پول نگه‌داری می‌شود.
     */
    public function reserveForOrder(
        User $user,
        Order $order,
        int $balanceBefore,
        int $promotionalBalanceBefore,
        int $promotional,
        int $paid,
    ): ?string {
        if (! Schema::hasTable('finance_credit_lots') || ($promotional + $paid) < 1) {
            return null;
        }

        $this->reconcileTrackedBalance($user, $balanceBefore, $promotionalBalanceBefore);
        $reservationKey = (string) Str::uuid();

        $this->allocateLots($user, $order, $reservationKey, $promotional, true);
        $this->allocateLots($user, $order, $reservationKey, $paid, false);

        return $reservationKey;
    }

    public function settleReservation(?string $reservationKey, int $actualAmount): void
    {
        if (! $reservationKey || ! Schema::hasTable('finance_credit_allocations')) {
            return;
        }

        DB::transaction(function () use ($reservationKey, $actualAmount): void {
            $allocations = FinanceCreditAllocation::query()
                ->with(['lot', 'order.user'])
                ->where('reservation_key', $reservationKey)
                ->where('status', 'reserved')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $remaining = max(0, $actualAmount);
            $totalUsed = 0;
            $totalRevenue = 0.0;

            foreach ($allocations as $allocation) {
                $used = min((int) $allocation->credits_reserved, $remaining);
                $refunded = (int) $allocation->credits_reserved - $used;
                $remaining -= $used;
                $unitRevenue = (int) $allocation->lot->credits_granted > 0
                    ? (float) $allocation->lot->revenue_toman / (int) $allocation->lot->credits_granted
                    : 0;
                $revenue = round($unitRevenue * $used, 2);

                if ($refunded > 0) {
                    $allocation->lot->increment('credits_remaining', $refunded);
                }
                $allocation->update([
                    'credits_used' => $used,
                    'credits_refunded' => $refunded,
                    'revenue_toman' => $revenue,
                    'status' => $used > 0 ? 'settled' : 'released',
                    'settled_at' => now(),
                ]);
                $totalUsed += $used;
                $totalRevenue += $revenue;
            }

            $first = $allocations->first();
            if ($first?->order) {
                $case = $first->finance_case_id ? FinanceCase::query()->find($first->finance_case_id) : null;
                $this->event(
                    $case,
                    'order-credit-settle:' . $first->order_id . ':' . $reservationKey,
                    'credit_consumed',
                    'order',
                    'اعتبار سفارش مصرف شد',
                    $first->order->order_number,
                    -$totalUsed,
                    (int) ($first->order->user?->tokens ?? 0),
                    null,
                    $totalRevenue,
                    'actual',
                    $first->order->completed_at ?: now(),
                    ['reservation_key' => $reservationKey],
                    null,
                    $first->order_id,
                );
            }
        });
    }

    public function restoreReservation(?string $reservationKey, bool $reverseSettled = false): void
    {
        if (! $reservationKey || ! Schema::hasTable('finance_credit_allocations')) {
            return;
        }

        DB::transaction(function () use ($reservationKey, $reverseSettled): void {
            $allocations = FinanceCreditAllocation::query()
                ->with('lot')
                ->where('reservation_key', $reservationKey)
                ->whereIn('status', $reverseSettled ? ['reserved', 'settled'] : ['reserved'])
                ->lockForUpdate()
                ->get();

            foreach ($allocations as $allocation) {
                $restore = $allocation->status === 'settled'
                    ? max(0, (int) $allocation->credits_used - (int) $allocation->credits_refunded)
                    : max(0, (int) $allocation->credits_reserved - (int) $allocation->credits_refunded);
                if ($restore > 0) {
                    $allocation->lot->increment('credits_remaining', $restore);
                }
                $allocation->update([
                    'credits_refunded' => (int) $allocation->credits_refunded + $restore,
                    'revenue_toman' => 0,
                    'status' => 'reversed',
                    'settled_at' => now(),
                ]);
            }
        });
    }

    public function refundOrder(Order $order, int $credits): void
    {
        if ($credits < 1 || ! Schema::hasTable('finance_credit_allocations')) {
            return;
        }

        DB::transaction(function () use ($order, $credits): void {
            $allocations = FinanceCreditAllocation::query()
                ->with('lot')
                ->where('order_id', $order->id)
                ->where('status', 'settled')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            $remaining = $credits;
            $case = null;
            $revenueReversed = 0.0;

            foreach ($allocations as $allocation) {
                $available = max(0, (int) $allocation->credits_used - (int) $allocation->credits_refunded);
                $refund = min($available, $remaining);
                if ($refund < 1) {
                    continue;
                }
                $remaining -= $refund;
                $unitRevenue = (int) $allocation->lot->credits_granted > 0
                    ? (float) $allocation->lot->revenue_toman / (int) $allocation->lot->credits_granted
                    : 0;
                $revenueReversed += $unitRevenue * $refund;
                $allocation->lot->increment('credits_remaining', $refund);
                $netUsed = (int) $allocation->credits_used - ((int) $allocation->credits_refunded + $refund);
                $allocation->update([
                    'credits_refunded' => (int) $allocation->credits_refunded + $refund,
                    'revenue_toman' => round($unitRevenue * max(0, $netUsed), 2),
                    'status' => $netUsed > 0 ? 'settled' : 'refunded',
                ]);
                $case ??= $allocation->finance_case_id ? FinanceCase::query()->find($allocation->finance_case_id) : null;
                if ($remaining < 1) {
                    break;
                }
            }

            $this->event(
                $case,
                'order-refund:' . $order->id . ':' . ((int) $order->refunded_credits + $credits),
                'credit_refunded',
                'order_refund',
                'اعتبار سفارش بازگردانده شد',
                $order->order_number,
                $credits - max(0, $remaining),
                (int) ($order->user?->tokens ?? 0),
                null,
                -round($revenueReversed, 2),
                'actual',
                now(),
                ['requested_credits' => $credits, 'unmatched_credits' => $remaining],
                null,
                $order->id,
            );
        });
    }

    public function recordOrderStatus(Order $order): void
    {
        if (! $order->user_id || ! Schema::hasTable('finance_case_events')) {
            return;
        }

        $order->loadMissing('user');
        $case = FinanceCreditAllocation::query()->where('order_id', $order->id)->value('finance_case_id');
        $financeCase = $case ? FinanceCase::query()->find($case) : $this->activeCaseForUser($order->user, $order->created_at);
        $this->event(
            $financeCase,
            'order-status:' . $order->id . ':' . $order->status . ':' . $order->processing_status . ':' . $order->attempts,
            'order_status',
            'order',
            'وضعیت سفارش: ' . $this->orderStatusLabel((string) $order->processing_status),
            $order->order_number,
            0,
            (int) $order->user->tokens,
            null,
            null,
            'actual',
            $order->updated_at ?: now(),
            ['status' => $order->status, 'processing_status' => $order->processing_status, 'attempts' => (int) $order->attempts],
            null,
            $order->id,
        );
    }

    public function recordProviderRequest(AiProviderRequest $request): void
    {
        if (! $request->order_id || ! Schema::hasTable('finance_case_events')) {
            return;
        }

        $request->loadMissing('order.user', 'aiModel');
        if (! $request->order?->user) {
            return;
        }
        $caseId = FinanceCreditAllocation::query()->where('order_id', $request->order_id)->value('finance_case_id');
        $case = $caseId ? FinanceCase::query()->find($caseId) : $this->activeCaseForUser($request->order->user, $request->created_at);
        $hasActual = $request->actual_cost_usd !== null;
        $isSuccessful = in_array($request->status, ['completed', 'success'], true);
        $usd = $hasActual
            ? (float) $request->actual_cost_usd
            : ($isSuccessful ? (float) ($request->estimated_cost_usd ?? 0) : 0.0);
        $rateToman = $this->rates->rateToman('USD', $request->submitted_at ?: $request->created_at);

        $this->event(
            $case,
            'provider-request:' . $request->id . ':' . $request->status,
            'provider_request',
            (string) $request->provider,
            'اجرای مدل ' . ($request->aiModel?->name ?: $request->provider),
            $request->status,
            0,
            (int) $request->order->user->tokens,
            $usd,
            $usd > 0 && $rateToman > 0 ? round($usd * $rateToman, 2) : null,
            $hasActual ? 'actual' : ($usd > 0 ? 'estimated' : 'missing'),
            $request->completed_at ?: $request->submitted_at ?: $request->created_at,
            ['external_request_id' => $request->external_request_id, 'error_code' => $request->error_code],
            null,
            $request->order_id,
        );
    }

    public function syncExisting(): array
    {
        $counts = ['purchases' => 0, 'token_logs' => 0, 'orders' => 0, 'provider_requests' => 0];
        PlanPurchase::query()->where('status', PlanPurchase::COMPLETED)->orderBy('purchased_at')->each(function (PlanPurchase $purchase) use (&$counts): void {
            $this->recordPurchase($purchase);
            $counts['purchases']++;
        });
        TokenLog::query()->where(fn ($query) => $query->whereNull('source')->orWhere('source', '!=', 'plan_purchase'))->orderBy('created_at')->each(function (TokenLog $log) use (&$counts): void {
            $this->recordManualCredit($log);
            $counts['token_logs']++;
        });
        Order::query()->with('user')->orderBy('created_at')->each(function (Order $order) use (&$counts): void {
            $this->backfillOrder($order);
            $this->recordOrderStatus($order);
            $counts['orders']++;
        });
        AiProviderRequest::query()->orderBy('created_at')->each(function (AiProviderRequest $request) use (&$counts): void {
            $this->recordProviderRequest($request);
            $counts['provider_requests']++;
        });

        return $counts;
    }

    private function backfillOrder(Order $order): void
    {
        if (! $order->user || (int) $order->final_credits < 1 || FinanceCreditAllocation::query()->where('order_id', $order->id)->exists()) {
            return;
        }

        $promotional = max(0, (int) $order->promotional_credits_used);
        $paid = max(0, (int) $order->paid_credits_used);
        if (($promotional + $paid) < 1) {
            $paid = (int) $order->final_credits;
        }
        $key = 'legacy-' . $order->id;
        $this->ensurePool($order->user, $promotional, true, $order->created_at);
        $this->ensurePool($order->user, $paid, false, $order->created_at);
        $this->allocateLots($order->user, $order, $key, $promotional, true);
        $this->allocateLots($order->user, $order, $key, $paid, false);
        $this->settleReservation($key, (int) $order->final_credits);
        if ((int) $order->refunded_credits > 0) {
            $this->refundOrder($order, (int) $order->refunded_credits);
        }
    }

    private function activeCaseForUser(User $user, $at): FinanceCase
    {
        $case = FinanceCase::query()
            ->where('user_id', $user->id)
            ->where('started_at', '<=', $at ?: now())
            ->where(fn ($query) => $query->whereNull('ended_at')->orWhere('ended_at', '>=', $at ?: now()))
            ->latest('started_at')
            ->first();
        if ($case) {
            return $case;
        }

        return FinanceCase::query()->create([
            'case_number' => 'USR-' . $user->id . '-' . now()->format('ymdHis') . '-' . Str::upper(Str::random(3)),
            'user_id' => $user->id,
            'title' => 'پرونده مالی کاربر بدون خرید مبنا',
            'status' => 'open',
            'opening_balance' => (int) $user->tokens,
            'started_at' => $at ?: now(),
            'metadata' => ['standalone' => true],
        ]);
    }

    private function reconcileTrackedBalance(User $user, int $balance, int $promotionalBalance): void
    {
        $targetPromotional = max(0, min($promotionalBalance, $balance));
        $targetPaid = max(0, $balance - $targetPromotional);
        $this->reconcilePool($user, $targetPromotional, true);
        $this->reconcilePool($user, $targetPaid, false);
    }

    private function reconcilePool(User $user, int $target, bool $promotional): void
    {
        $lots = FinanceCreditLot::query()
            ->where('user_id', $user->id)
            ->where('is_promotional', $promotional)
            ->where('credits_remaining', '>', 0)
            ->orderBy('occurred_at')->orderBy('id')->lockForUpdate()->get();
        $tracked = (int) $lots->sum('credits_remaining');
        if ($tracked < $target) {
            $this->ensurePool($user, $target - $tracked, $promotional, now());
            return;
        }
        $excess = $tracked - $target;
        foreach ($lots->reverse() as $lot) {
            if ($excess < 1) break;
            $deduct = min($excess, (int) $lot->credits_remaining);
            $lot->decrement('credits_remaining', $deduct);
            $excess -= $deduct;
        }
    }

    private function ensurePool(User $user, int $credits, bool $promotional, $at): void
    {
        if ($credits < 1) return;
        $case = $this->activeCaseForUser($user, $at);
        FinanceCreditLot::query()->create([
            'finance_case_id' => $case->id,
            'user_id' => $user->id,
            'source_key' => 'opening-balance:' . $user->id . ':' . Str::uuid(),
            'source_type' => 'opening_balance',
            'source_label' => 'مانده تاریخی پیش از دفتر مالی',
            'credits_granted' => $credits,
            'credits_remaining' => $credits,
            'revenue_toman' => 0,
            'is_promotional' => $promotional,
            'occurred_at' => $at ?: now(),
            'metadata' => ['data_quality' => 'estimated'],
        ]);
    }

    private function allocateLots(User $user, Order $order, string $key, int $amount, bool $promotional): void
    {
        $remaining = max(0, $amount);
        if ($remaining < 1) return;
        $lots = FinanceCreditLot::query()
            ->where('user_id', $user->id)
            ->where('is_promotional', $promotional)
            ->where('credits_remaining', '>', 0)
            ->orderBy('occurred_at')->orderBy('id')->lockForUpdate()->get();

        foreach ($lots as $lot) {
            $take = min($remaining, (int) $lot->credits_remaining);
            if ($take < 1) continue;
            $lot->decrement('credits_remaining', $take);
            FinanceCreditAllocation::query()->create([
                'finance_credit_lot_id' => $lot->id,
                'finance_case_id' => $lot->finance_case_id,
                'user_id' => $user->id,
                'order_id' => $order->id,
                'reservation_key' => $key,
                'credits_reserved' => $take,
                'status' => 'reserved',
                'occurred_at' => now(),
            ]);
            $remaining -= $take;
            if ($remaining < 1) break;
        }

        if ($remaining > 0) {
            $this->ensurePool($user, $remaining, $promotional, $order->created_at);
            $this->allocateLots($user, $order, $key, $remaining, $promotional);
        }
    }

    private function reduceLots(User $user, int $amount, bool $promotionalFirst): void
    {
        foreach ($promotionalFirst ? [true, false] : [false, true] as $promotional) {
            $lots = FinanceCreditLot::query()->where('user_id', $user->id)
                ->where('is_promotional', $promotional)->where('credits_remaining', '>', 0)
                ->orderBy('occurred_at')->orderBy('id')->lockForUpdate()->get();
            foreach ($lots as $lot) {
                $deduct = min($amount, (int) $lot->credits_remaining);
                $lot->decrement('credits_remaining', $deduct);
                $amount -= $deduct;
                if ($amount < 1) return;
            }
        }
    }

    private function event(
        ?FinanceCase $case,
        string $sourceKey,
        string $eventType,
        ?string $sourceType,
        string $title,
        ?string $description,
        int $creditsDelta,
        ?int $balanceAfter,
        ?float $amountUsd,
        ?float $amountToman,
        string $quality,
        $occurredAt,
        array $metadata = [],
        ?int $purchaseId = null,
        ?int $orderId = null,
        ?int $tokenLogId = null,
    ): FinanceCaseEvent {
        return FinanceCaseEvent::query()->updateOrCreate(
            ['source_key' => $sourceKey],
            [
                'finance_case_id' => $case?->id,
                'user_id' => $case?->user_id,
                'plan_purchase_id' => $purchaseId ?: $case?->anchor_plan_purchase_id,
                'order_id' => $orderId,
                'token_log_id' => $tokenLogId,
                'event_type' => $eventType,
                'source_type' => $sourceType,
                'title' => $title,
                'description' => $description,
                'credits_delta' => $creditsDelta,
                'balance_after' => $balanceAfter,
                'amount_usd' => $amountUsd,
                'amount_toman' => $amountToman,
                'data_quality' => $quality,
                'occurred_at' => $occurredAt ?: now(),
                'metadata' => $metadata,
            ],
        );
    }

    private function caseNumber(PlanPurchase $purchase): string
    {
        return 'CASE-' . $purchase->id . '-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', (string) $purchase->order_number), -8));
    }

    private function creditKindLabel(string $kind): string
    {
        return match ($kind) {
            'gift', 'registration_gift' => 'اعتبار هدیه',
            'plan_upgrade' => 'اعتبار هدیه ارتقای پلن',
            'referral' => 'اعتبار دعوت دوستان',
            'paid_adjustment' => 'اصلاح اعتبار خریداری‌شده',
            default => 'اصلاح دستی اعتبار',
        };
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'تکمیل‌شده',
            'failed' => 'ناموفق',
            'retrying' => 'تلاش مجدد',
            'stopped' => 'متوقف‌شده',
            'queued' => 'در صف',
            default => 'در حال پردازش',
        };
    }
}
