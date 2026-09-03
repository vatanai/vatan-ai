<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserTokenGrant;
use Illuminate\Support\Facades\DB;

/** مدیریت اعتبارهای هدیه‌ی زمان‌دار و مصرف آن‌ها به ترتیب انقضا. */
class TokenGrantService
{
    /** اعتبارهای منقضی را از موجودی تجمیعی کاربر خارج می‌کند. */
    public function expireForUser(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
            return $this->expireLocked($locked);
        });
    }

    /** این متد داخل تراکنشی صدا زده می‌شود که قبلاً ردیف کاربر قفل شده است. */
    public function expireLocked(User $lockedUser): int
    {
        if (! DB::getSchemaBuilder()->hasTable('user_token_grants')) {
            return 0;
        }

        $expired = UserTokenGrant::query()
            ->where('user_id', $lockedUser->getKey())
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->lockForUpdate()
            ->get();
        $amount = (int) $expired->sum('remaining_amount');
        if ($amount < 1) {
            return 0;
        }

        $expired->each->update(['remaining_amount' => 0]);
        $lockedUser->tokens = max(0, (int) $lockedUser->tokens - $amount);
        $lockedUser->promotional_tokens = max(0, (int) $lockedUser->promotionalTokenBalance() - $amount);
        $lockedUser->save();
        return $amount;
    }

    /** بخشی از اعتبار هدیه‌ی فعال را از قدیمی‌ترین دسته‌ها مصرف می‌کند. */
    public function consumeLocked(User $lockedUser, int $amount): array
    {
        if ($amount < 1 || ! DB::getSchemaBuilder()->hasTable('user_token_grants')) {
            return [];
        }

        $left = $amount;
        $usedByGrant = [];
        $grants = UserTokenGrant::query()
            ->where('user_id', $lockedUser->getKey())
            ->where('remaining_amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('expires_at IS NULL')
            ->orderBy('expires_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($grants as $grant) {
            if ($left < 1) break;
            $used = min($left, (int) $grant->remaining_amount);
            $grant->decrement('remaining_amount', $used);
            $usedByGrant[$grant->id] = $used;
            $left -= $used;
        }

        return $usedByGrant;
    }

    public function create(User $user, int $amount, $expiresAt, ?int $adminId, ?int $tokenLogId = null, string $source = 'manual_credit'): ?UserTokenGrant
    {
        if ($amount < 1 || ! DB::getSchemaBuilder()->hasTable('user_token_grants')) {
            return null;
        }

        return UserTokenGrant::create([
            'user_id' => $user->getKey(),
            'token_log_id' => $tokenLogId,
            'admin_id' => $adminId,
            'amount' => $amount,
            'remaining_amount' => $amount,
            'expires_at' => $expiresAt,
            'source' => $source,
        ]);
    }
}
