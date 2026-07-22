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

    public function debit(User $user, int $amount): User
    {
        if ($amount < 1) {
            return $user->fresh();
        }

        return DB::transaction(function () use ($user, $amount) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if ($this->balance($lockedUser) < $amount) {
                throw ValidationException::withMessages([
                    'tokens' => 'موجودی توکن شما کافی نیست.',
                ]);
            }

            $lockedUser->tokens = $this->balance($lockedUser) - $amount;
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
            }

            $lockedUser->save();

            return $lockedUser;
        });
    }
}
