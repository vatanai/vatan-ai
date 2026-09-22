<?php

namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * کیف پول یکپارچهٔ اعتبار کاربر.
 *
 * ستون users.tokens همچنان موجودی واحدی است که رابط کاربر نمایش می‌دهد؛
 * promotional_tokens فقط سهمِ هدیه از همین موجودی را برای گزارش مالی، انقضا
 * و بازگرداندن اعتبار به منبع درست نگه می‌دارد و محدودیت مصرف ایجاد نمی‌کند.
 */
class CreditWalletService
{
    /**
     * رزرو اتمیک اعتبار و ثبت سهم هدیه/پرداختی برای تسویه یا بازگشت بعدی.
     *
     * @return array{total:int,promotional:int,paid:int,ledger_key?:string|null}
     */
    public function reserve(User $user, int $amount, ?Order $order = null): array
    {
        $amount = max(0, $amount);

        return DB::transaction(function () use ($user, $amount, $order) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            app(TokenGrantService::class)->expireLocked($lockedUser);
            $promotionalAvailable = $lockedUser->promotionalTokenBalance();
            $balanceBefore = (int) $lockedUser->tokens;
            $allocation = $this->allocationForReservation(
                (int) $lockedUser->tokens,
                $promotionalAvailable,
                $amount,
            );

            $lockedUser->tokens = (int) $lockedUser->tokens - $amount;
            $lockedUser->promotional_tokens = $promotionalAvailable - $allocation['promotional'];
            $grantAllocations = [];
            if ($allocation['promotional'] > 0) {
                $grantAllocations = app(TokenGrantService::class)->consumeLocked($lockedUser, $allocation['promotional']);
            }
            $allocation['grant_allocations'] = $grantAllocations;
            $lockedUser->save();

            $allocation['ledger_key'] = null;
            if ($order) {
                try {
                    $allocation['ledger_key'] = app(FinanceCaseLedgerService::class)->reserveForOrder(
                        $lockedUser,
                        $order,
                        $balanceBefore,
                        $promotionalAvailable,
                        $allocation['promotional'],
                        $allocation['paid'],
                    );
                } catch (\Throwable $exception) {
                    report($exception);
                }
            }

            return $allocation;
        });
    }

    /**
     * محاسبه‌ی خالص سهم هر کیف پول؛ مستقل از دیتابیس تا سیاست مالی قابل آزمون باشد.
     *
     * @return array{total:int,promotional:int,paid:int}
     */
    public function allocationForReservation(
        int $balance,
        int $promotionalBalance,
        int $amount,
    ): array {
        if ($amount < 1) {
            return ['total' => 0, 'promotional' => 0, 'paid' => 0];
        }

        $balance = max(0, $balance);
        $promotionalAvailable = max(0, min($promotionalBalance, $balance));
        $promotional = min($promotionalAvailable, $amount);
        $paid = $amount - $promotional;

        if ($balance < $amount) {
            throw ValidationException::withMessages([
                'tokens' => 'موجودی اعتبار شما کافی نیست.',
            ]);
        }

        return ['total' => $amount, 'promotional' => $promotional, 'paid' => $paid];
    }

    /**
     * رزرو را با تعداد خروجی موفق تسویه می‌کند و مازاد هر منبع را به همان منبع بازمی‌گرداند.
     *
     * @param array{total:int,promotional:int,paid:int,ledger_key?:string|null} $reservation
     * @return array{total:int,promotional:int,paid:int,ledger_key?:string|null}
     */
    public function settle(User $user, array $reservation, int $actualAmount): array
    {
        $actualAmount = max(0, $actualAmount);
        $reservedPromotional = max(0, (int) ($reservation['promotional'] ?? 0));
        $reservedPaid = max(0, (int) ($reservation['paid'] ?? 0));
        $reservedTotal = $reservedPromotional + $reservedPaid;
        $reservedUsage = min($actualAmount, $reservedTotal);
        $promotionalUsed = min($reservedPromotional, $reservedUsage);
        $paidUsed = min($reservedPaid, max(0, $reservedUsage - $promotionalUsed));
        $promotionalRefund = $reservedPromotional - $promotionalUsed;
        $paidRefund = $reservedPaid - $paidUsed;
        $overage = max(0, $actualAmount - $reservedTotal);

        $grantAllocations = (array) ($reservation['grant_allocations'] ?? []);
        $overageAllocation = DB::transaction(function () use ($user, $actualAmount, $promotionalRefund, $paidRefund, $overage, $grantAllocations): array {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            app(TokenGrantService::class)->expireLocked($lockedUser);
            $promotionalAvailable = $lockedUser->promotionalTokenBalance();
            $allocation = $this->allocationForReservation(
                (int) $lockedUser->tokens,
                $promotionalAvailable,
                $overage,
            );

            $lockedUser->tokens = (int) $lockedUser->tokens + $promotionalRefund + $paidRefund - $overage;
            $lockedUser->promotional_tokens = $promotionalAvailable + $promotionalRefund - $allocation['promotional'];
            $lockedUser->tokens_used = (int) $lockedUser->tokens_used + $actualAmount;
            $lockedUser->save();
            if ($allocation['promotional'] > 0) {
                app(TokenGrantService::class)->consumeLocked($lockedUser, $allocation['promotional']);
            }
            if ($promotionalRefund > 0 && $grantAllocations) {
                $remainingRefund = $promotionalRefund;
                foreach ($grantAllocations as $grantId => $used) {
                    $restoreAmount = (int) min($used, $remainingRefund);
                    \App\Models\UserTokenGrant::query()->whereKey($grantId)->where('user_id', $lockedUser->id)->increment('remaining_amount', $restoreAmount);
                    $remainingRefund -= $restoreAmount;
                    if ($remainingRefund < 1) break;
                }
            }

            return $allocation;
        });

        $promotionalUsed += $overageAllocation['promotional'];
        $paidUsed += $overageAllocation['paid'];

        try {
            DB::transaction(function () use ($reservation, $overageAllocation, $actualAmount): void {
                $ledger = app(FinanceCaseLedgerService::class);
                $ledger->extendReservation(
                    $reservation['ledger_key'] ?? null,
                    $overageAllocation['promotional'],
                    $overageAllocation['paid'],
                );
                $ledger->settleReservation($reservation['ledger_key'] ?? null, $actualAmount);
            });
        } catch (\Throwable $exception) {
            report($exception);
        }

        return [
            'total' => $actualAmount,
            'promotional' => $promotionalUsed,
            'paid' => $paidUsed,
            'ledger_key' => $reservation['ledger_key'] ?? null,
        ];
    }

    /** بازگرداندن اعتبار به همان منبعی که از آن مصرف شده است. */
    public function restore(User $user, int $promotional, int $paid, bool $reverseUsage = false, ?string $ledgerReservationKey = null, array $grantAllocations = []): User
    {
        $promotional = max(0, $promotional);
        $paid = max(0, $paid);

        $restored = DB::transaction(function () use ($user, $promotional, $paid, $reverseUsage, $grantAllocations) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $total = $promotional + $paid;
            $lockedUser->tokens = (int) $lockedUser->tokens + $total;
            $lockedUser->promotional_tokens = $lockedUser->promotionalTokenBalance() + $promotional;
            if ($reverseUsage) {
                $lockedUser->tokens_used = max(0, (int) $lockedUser->tokens_used - $total);
            }
            $lockedUser->save();
            if ($promotional > 0 && $grantAllocations) {
                foreach ($grantAllocations as $grantId => $used) {
                    $restoreAmount = min((int) $used, $promotional);
                    \App\Models\UserTokenGrant::query()->whereKey($grantId)->where('user_id', $lockedUser->id)->increment('remaining_amount', $restoreAmount);
                    $promotional -= $restoreAmount;
                    if ($promotional < 1) break;
                }
            }

            return $lockedUser;
        });

        try {
            app(FinanceCaseLedgerService::class)->restoreReservation($ledgerReservationKey, $reverseUsage);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $restored;
    }
}
