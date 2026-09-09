<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\User;
use InvalidArgumentException;

class PlanDiscountService
{
    /** @param array<string,mixed> $offer */
    public function apply(User $user, Plan $plan, array $offer, ?string $code): array
    {
        $code = strtoupper(trim((string) $code));
        $offer['discount_code'] = null;
        $offer['discount_id'] = null;
        $offer['discount_amount_code'] = 0;

        if ($code === '') {
            return $offer;
        }

        $discount = Discount::query()
            ->available()
            ->whereRaw('UPPER(code) = ?', [$code])
            ->first();

        if (! $discount) {
            throw new InvalidArgumentException('کد تخفیف معتبر یا فعال نیست.');
        }

        if ($discount->scope !== 'all') {
            throw new InvalidArgumentException('این کد تخفیف برای خرید پلن قابل استفاده نیست.');
        }

        if ($discount->first_order_only && PlanPurchase::query()
            ->where('user_id', $user->id)
            ->where('status', PlanPurchase::COMPLETED)
            ->exists()) {
            throw new InvalidArgumentException('این کد فقط برای اولین خرید پلن قابل استفاده است.');
        }

        if (PlanPurchase::query()
            ->where('user_id', $user->id)
            ->where('discount_id', $discount->id)
            ->whereIn('status', [PlanPurchase::PENDING, PlanPurchase::REDIRECTED, PlanPurchase::VERIFYING, PlanPurchase::COMPLETED])
            ->count() >= (int) $discount->usage_limit_per_user) {
            throw new InvalidArgumentException('سقف استفاده شما از این کد تخفیف تکمیل شده است.');
        }

        $basePrice = (int) ($offer['price'] ?? 0);
        $discountAmount = $discount->calculateCredits($basePrice);
        if ($discountAmount < 1) {
            throw new InvalidArgumentException('حداقل مبلغ لازم برای این کد تخفیف تأمین نشده است.');
        }

        $finalPrice = max(0, $basePrice - $discountAmount);
        if ($finalPrice < 1) {
            throw new InvalidArgumentException('این کد تخفیف مبلغ خرید پلن را به صفر می‌رساند و برای این پلن قابل استفاده نیست.');
        }

        $offer['price'] = $finalPrice;
        $offer['discount_amount'] = (int) ($offer['discount_amount'] ?? 0) + $discountAmount;
        $offer['discount_code'] = $discount->code;
        $offer['discount_id'] = $discount->id;
        $offer['discount_amount_code'] = $discountAmount;
        $offer['discount_name'] = $discount->name;
        $offer['discount_type'] = $discount->type;

        return $offer;
    }
}
