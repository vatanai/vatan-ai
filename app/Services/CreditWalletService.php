<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * کیف پول اعتبار خریداری‌شده و هدیه.
 *
 * ستون users.tokens همچنان موجودی واحدی است که رابط کاربر نمایش می‌دهد؛
 * promotional_tokens فقط سهمِ هدیه از همین موجودی را نگه می‌دارد. این جداسازی
 * اجازه می‌دهد مدل‌های گرید ۱ و ۲ فقط از اعتبار خریداری‌شده استفاده کنند.
 */
class CreditWalletService
{
    /**
     * اعتبار هدیه تنها وقتی مجاز است که تمام مسیرهای قابل اجرای محصول (اصلی و
     * جایگزین) گرید ۳ یا ۴ باشند. مدل ناشناخته عمداً نامجاز است تا سیاست شکستِ امن داشته باشد.
     */
    public function productAllowsPromotionalCredits(Product $product): bool
    {
        $modelIds = array_values(array_filter(array_merge(
            [(string) $product->primary_model],
            (array) $product->fallback_models,
        )));
        $providers = array_values(array_merge(
            [(string) $product->ai_provider],
            (array) $product->fallback_model_providers,
        ));

        if (empty($modelIds) || count($modelIds) !== count($providers)) {
            return false;
        }

        foreach ($modelIds as $index => $modelId) {
            $model = AiModel::query()
                ->where('is_active', true)
                ->where('provider', $providers[$index])
                ->where('openrouter_model_id', $modelId)
                ->first();

            if (! $model || ! $model->allowsPromotionalCredits()) {
                return false;
            }
        }

        return true;
    }

    /**
     * رزرو اتمیک اعتبار و ثبت سهم هدیه/پرداختی برای تسویه یا بازگشت بعدی.
     *
     * @return array{total:int,promotional:int,paid:int,ledger_key?:string|null}
     */
    public function reserve(User $user, int $amount, bool $allowPromotionalCredits, ?Order $order = null): array
    {
        return DB::transaction(function () use ($user, $amount, $allowPromotionalCredits, $order) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            app(TokenGrantService::class)->expireLocked($lockedUser);
            $promotionalAvailable = $lockedUser->promotionalTokenBalance();
            $balanceBefore = (int) $lockedUser->tokens;
            $allocation = $this->allocationForReservation(
                (int) $lockedUser->tokens,
                $promotionalAvailable,
                $amount,
                $allowPromotionalCredits,
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
        bool $allowPromotionalCredits,
    ): array {
        if ($amount < 1) {
            return ['total' => 0, 'promotional' => 0, 'paid' => 0];
        }

        $balance = max(0, $balance);
        $promotionalAvailable = max(0, min($promotionalBalance, $balance));
        $paidAvailable = $balance - $promotionalAvailable;
        $promotional = $allowPromotionalCredits ? min($promotionalAvailable, $amount) : 0;
        $paid = $amount - $promotional;

        if ($paidAvailable < $paid) {
            throw ValidationException::withMessages([
                'tokens' => $allowPromotionalCredits
                    ? 'موجودی اعتبار شما کافی نیست.'
                    : 'این محصول با اعتبار هدیه قابل ساخت نیست؛ برای مدل‌های گرید ۱ و ۲ اعتبار خریداری‌شده لازم است.',
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
        $actualAmount = max(0, min((int) $reservation['total'], $actualAmount));
        $promotionalUsed = min((int) $reservation['promotional'], $actualAmount);
        $paidUsed = $actualAmount - $promotionalUsed;
        $promotionalRefund = (int) $reservation['promotional'] - $promotionalUsed;
        $paidRefund = (int) $reservation['paid'] - $paidUsed;

        $grantAllocations = (array) ($reservation['grant_allocations'] ?? []);
        DB::transaction(function () use ($user, $actualAmount, $promotionalRefund, $paidRefund, $grantAllocations) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->tokens = (int) $lockedUser->tokens + $promotionalRefund + $paidRefund;
            $lockedUser->promotional_tokens = $lockedUser->promotionalTokenBalance() + $promotionalRefund;
            $lockedUser->tokens_used = (int) $lockedUser->tokens_used + $actualAmount;
            $lockedUser->save();
            if ($promotionalRefund > 0 && $grantAllocations) {
                foreach ($grantAllocations as $grantId => $used) {
                    \App\Models\UserTokenGrant::query()->whereKey($grantId)->where('user_id', $lockedUser->id)->increment('remaining_amount', (int) min($used, $promotionalRefund));
                    $promotionalRefund -= (int) min($used, $promotionalRefund);
                    if ($promotionalRefund < 1) break;
                }
            }
        });

        try {
            app(FinanceCaseLedgerService::class)->settleReservation($reservation['ledger_key'] ?? null, $actualAmount);
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
