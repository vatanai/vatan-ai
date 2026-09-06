<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تنها درگاه خواندن و تغییر موجودی توکن کاربران.
 *
 * موجودی رسمی در users.tokens نگه‌داری می‌شود؛ شماره موبایل فقط هویت کاربر
 * را مشخص می‌کند و هیچ موجودی جداگانه‌ای در session یا جدول wallets نداریم.
 */
class TokenBalanceService
{
    public function balance(User $user): int
    {
        return (int) $user->getAttribute('tokens');
    }

    public function debit(User $user, int $amount, bool $allowPromotionalCredits = true): User
    {
        if ($amount < 1) {
            return $user->fresh();
        }

        return DB::transaction(function () use ($user, $amount, $allowPromotionalCredits) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            app(TokenGrantService::class)->expireLocked($lockedUser);
            $promotionalAvailable = $lockedUser->promotionalTokenBalance();

            $available = $allowPromotionalCredits
                ? $this->balance($lockedUser)
                : $lockedUser->paidTokenBalance();
            if ($available < $amount) {
                throw ValidationException::withMessages([
                    'tokens' => $allowPromotionalCredits
                        ? 'موجودی اعتبار شما کافی نیست.'
                        : 'این محصول فقط با اعتبار خریداری‌شده قابل ساخت است.',
                ]);
            }

            $lockedUser->tokens = $this->balance($lockedUser) - $amount;
            if ($allowPromotionalCredits) {
                $promotionalDebit = min($promotionalAvailable, $amount);
                $lockedUser->promotional_tokens = $promotionalAvailable - $promotionalDebit;
                if ($promotionalDebit > 0) {
                    app(TokenGrantService::class)->consumeLocked($lockedUser, $promotionalDebit);
                }
            }
            $lockedUser->tokens_used = (int) $lockedUser->tokens_used + $amount;
            $lockedUser->save();

            return $lockedUser;
        });
    }

    public function credit(User $user, int $amount, bool $purchased = false): User
    {
        if ($amount < 1) {
            return $user->fresh();
        }

        return DB::transaction(function () use ($user, $amount, $purchased) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $lockedUser->tokens = $this->balance($lockedUser) + $amount;

            if ($purchased) {
                $lockedUser->tokens_purchased = (int) $lockedUser->tokens_purchased + $amount;
            } else {
                $lockedUser->promotional_tokens = $lockedUser->promotionalTokenBalance() + $amount;
            }

            $lockedUser->save();

            return $lockedUser;
        });
    }
}
