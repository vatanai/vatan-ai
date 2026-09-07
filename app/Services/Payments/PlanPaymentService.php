<?php

namespace App\Services\Payments;

use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\ReferralProgramService;
use App\Services\SmsEventService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PlanPaymentService
{
    public function __construct(private readonly ZarinpalGateway $gateway)
    {
    }

    /** @param array{name:string,email:?string,phone:?string} $billing */
    public function initiate(User $user, Plan $plan, array $billing, string $callbackUrl, ?string $referralCode = null): PlanPurchase
    {
        $offer = app(ReferralProgramService::class)->purchaseOffer($user, $plan, $plan->offerFor($user));
        $this->ensurePurchasable($user, $plan, $offer);

        $purchase = PlanPurchase::query()->create([
            'order_number' => $this->nextOrderNumber(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'plan_name' => $plan->name,
            'customer_segment' => $offer['segment'],
            'paid_amount' => (int) $offer['price'],
            'original_amount' => (int) ($offer['original_price'] ?? $offer['price']),
            'discount_amount' => (int) ($offer['discount_amount'] ?? 0),
            'referral_conversion_id' => $offer['referral_conversion_id'] ?? null,
            'referral_snapshot' => $offer['referral_conversion_id'] ? [
                'discount_percent' => $offer['referral_discount_percent'] ?? 0,
                'code' => $referralCode,
            ] : null,
            'granted_tokens' => (int) $offer['tokens'] + (int) $offer['bonus_tokens'],
            'plan_snapshot' => $this->snapshot($plan, $offer),
            'status' => PlanPurchase::PENDING,
            'gateway' => 'zarinpal',
            'billing_name' => $billing['name'],
            'billing_email' => $billing['email'] ?: null,
            'billing_phone' => $billing['phone'] ?: $user->phone,
            'initiated_at' => now(),
            // ستون قدیمی nullable نیست؛ پس تا زمان تأیید، زمان آغاز نگه‌داری می‌شود.
            'purchased_at' => now(),
        ]);

        app(ReferralProgramService::class)->attachPurchaseReferral($purchase, $referralCode);

        try {
            $request = $this->gateway->request(
                $purchase,
                $user,
                str_replace('__ORDER__', $purchase->order_number, $callbackUrl),
            );
        } catch (\Throwable $exception) {
            $purchase->update([
                'status' => PlanPurchase::FAILED,
                'failure_reason' => $exception->getMessage(),
                'failed_at' => now(),
            ]);

            throw $exception;
        }

        $purchase->update([
            'status' => PlanPurchase::REDIRECTED,
            'gateway_track_id' => $request['track_id'],
            'payment_reference' => $request['track_id'],
            'gateway_status' => (string) (data_get($request, 'response.data.message')
                ?: data_get($request, 'response.data.code', '')),
        ]);

        return $purchase->fresh();
    }

    /** @param array<string,mixed> $callbackPayload */
    public function complete(PlanPurchase $purchase, array $callbackPayload): PlanPurchase
    {
        $purchase->refresh();
        if ($purchase->isCompleted()) {
            return $purchase;
        }

        $trackId = (string) ($callbackPayload['Authority'] ?? $callbackPayload['authority'] ?? $purchase->gateway_track_id ?? '');
        if ($trackId === '') {
            return $this->fail($purchase, 'بازگشت از درگاه بدون کد رهگیری انجام شد.', $callbackPayload);
        }

        if ($purchase->gateway_track_id && ! hash_equals((string) $purchase->gateway_track_id, $trackId)) {
            return $this->fail($purchase, 'کد رهگیری بازگشتی با سفارش هم‌خوانی ندارد.', $callbackPayload);
        }

        $callbackStatus = strtoupper(trim((string) ($callbackPayload['Status'] ?? $callbackPayload['status'] ?? '')));
        if ($callbackStatus !== '' && $callbackStatus !== 'OK') {
            return $this->fail($purchase, 'پرداخت در درگاه تکمیل نشد یا توسط کاربر لغو شد.', $callbackPayload, $callbackStatus);
        }

        $purchase->update([
            'status' => PlanPurchase::VERIFYING,
            'callback_payload' => $callbackPayload,
        ]);

        try {
            $verification = $this->gateway->verify($purchase, $trackId);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->awaitVerification($purchase, $callbackPayload);
        }

        if (! $this->gateway->verifiedSuccessfully($verification)) {
            return $this->fail(
                $purchase,
                $this->gateway->message($verification, 'پرداخت توسط درگاه تأیید نشد.'),
                $callbackPayload + ['verification' => $verification],
                $this->gateway->status($verification),
            );
        }

        if (! $this->gateway->amountMatches($purchase, $verification)) {
            return $this->fail($purchase, 'مبلغ تأییدشده با مبلغ سفارش یکسان نیست.', $callbackPayload + ['verification' => $verification]);
        }

        [$completed, $newlyCompleted] = DB::transaction(function () use ($purchase, $callbackPayload, $verification, $trackId) {
            $lockedPurchase = PlanPurchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($lockedPurchase->isCompleted()) {
                return [$lockedPurchase, false];
            }

            $user = User::query()->lockForUpdate()->findOrFail($lockedPurchase->user_id);
            $before = (int) $user->tokens;
            $grantedTokens = (int) $lockedPurchase->granted_tokens;
            $user->update([
                'tokens' => $before + $grantedTokens,
                'tokens_purchased' => (int) $user->tokens_purchased + $grantedTokens,
                'plan_id' => $lockedPurchase->plan_id,
            ]);

            $reference = $this->gateway->reference($verification, $trackId);
            $lockedPurchase->update([
                'status' => PlanPurchase::COMPLETED,
                'gateway_track_id' => $trackId,
                'gateway_reference' => $reference,
                'gateway_status' => $this->gateway->message($verification, 'تأیید شد'),
                'payment_reference' => $reference,
                'callback_payload' => $callbackPayload + ['verification' => $verification],
                'failure_reason' => null,
                'verified_at' => now(),
                'purchased_at' => now(),
            ]);

            TokenLog::query()->firstOrCreate(
                ['event_key' => 'plan-purchase:' . $lockedPurchase->id],
                [
                    'user_id' => $user->id,
                    'action' => 'add',
                    'source' => 'plan_purchase',
                    'amount' => $grantedTokens,
                    'balance_before' => $before,
                    'balance_after' => $before + $grantedTokens,
                    'note' => 'خرید پلن ' . $lockedPurchase->plan_name,
                    'metadata' => ['plan_purchase_id' => $lockedPurchase->id, 'order_number' => $lockedPurchase->order_number],
                ],
            );

            return [$lockedPurchase->fresh(['user']), true];
        });

        if ($newlyCompleted) {
            try {
                app(ReferralProgramService::class)->handleFirstPurchase($completed->user);
                app(ReferralProgramService::class)->handleCompletedPurchase($completed);
            } catch (\Throwable $exception) {
                report($exception);
            }

            if ($completed->user?->phone) {
                app(SmsEventService::class)->send('plan_purchase_success', $completed->user->phone, [
                    'name' => $completed->user->name,
                    'plan_name' => $completed->plan_name,
                ]);
            }
        }

        return $completed;
    }

    /**
     * پرداخت‌هایی را که بازگشت مرورگرشان کامل نشده یا بررسی‌شان موقتاً قطع شده است بازیابی می‌کند.
     *
     * @return array{attempted:int,completed:int,failed:int,pending:int}
     */
    public function reconcileUnfinishedPurchases(int $staleMinutes = 45, int $limit = 100): array
    {
        $result = ['attempted' => 0, 'completed' => 0, 'failed' => 0, 'pending' => 0];

        PlanPurchase::query()
            ->where('gateway', 'zarinpal')
            ->whereNotNull('gateway_track_id')
            ->where(function ($query) use ($staleMinutes): void {
                $query->where('status', PlanPurchase::VERIFYING)
                    ->orWhere(function ($query) use ($staleMinutes): void {
                        $query->where('status', PlanPurchase::REDIRECTED)
                            ->where('initiated_at', '<=', now()->subMinutes($staleMinutes));
                    });
            })
            ->oldest('initiated_at')
            ->limit($limit)
            ->get()
            ->each(function (PlanPurchase $purchase) use (&$result): void {
                $result['attempted']++;
                $settled = $this->complete($purchase, is_array($purchase->callback_payload) ? $purchase->callback_payload : []);

                if ($settled->isCompleted()) {
                    $result['completed']++;
                } elseif ($settled->status === PlanPurchase::FAILED) {
                    $result['failed']++;
                } else {
                    $result['pending']++;
                }
            });

        return $result;
    }

    public function expireStalePurchases(int $minutes = 45): int
    {
        return PlanPurchase::query()
            ->whereIn('status', [PlanPurchase::PENDING, PlanPurchase::REDIRECTED])
            ->where('initiated_at', '<=', now()->subMinutes($minutes))
            ->update([
                'status' => PlanPurchase::EXPIRED,
                'failure_reason' => 'مهلت پرداخت به پایان رسید یا پرداخت در درگاه رها شد.',
                'expired_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /** @param array<string,mixed> $callbackPayload */
    private function fail(PlanPurchase $purchase, string $reason, array $callbackPayload, ?string $gatewayStatus = null): PlanPurchase
    {
        $purchase->update([
            'status' => PlanPurchase::FAILED,
            'failure_reason' => $reason,
            'gateway_status' => $gatewayStatus ?: $purchase->gateway_status,
            'callback_payload' => $callbackPayload,
            'failed_at' => now(),
        ]);

        return $purchase->fresh();
    }

    /** @param array<string,mixed> $callbackPayload */
    private function awaitVerification(PlanPurchase $purchase, array $callbackPayload): PlanPurchase
    {
        $purchase->update([
            'status' => PlanPurchase::VERIFYING,
            'failure_reason' => 'تأیید نهایی پرداخت در حال بررسی است. نتیجه پس از برقراری ارتباط با درگاه به‌روزرسانی می‌شود.',
            'gateway_status' => 'verification_pending',
            'callback_payload' => $callbackPayload,
            'failed_at' => null,
        ]);

        return $purchase->fresh();
    }

    /** @param array<string,mixed> $offer */
    private function ensurePurchasable(User $user, Plan $plan, array $offer): void
    {
        if (! $offer['visible'] || ! $offer['purchasable']) {
            throw new RuntimeException('این پلن در حال حاضر برای خرید در دسترس نیست.');
        }
        if ($plan->billing_type === 'custom') {
            throw new RuntimeException('این پلن با هماهنگی تیم فروش فعال می‌شود.');
        }
        if ((int) $offer['price'] <= 0) {
            throw new RuntimeException('اعتبار هدیه در زمان ثبت‌نام به حساب شما افزوده می‌شود و نیازی به پرداخت ندارد.');
        }
        if ($plan->purchase_limit) {
            $count = $user->planPurchases()->where('plan_id', $plan->id)->where('status', PlanPurchase::COMPLETED)->count();
            if ($count >= $plan->purchase_limit) {
                throw new RuntimeException('سقف خرید این پلن برای حساب شما تکمیل شده است.');
            }
        }
    }

    /** @param array<string,mixed> $offer
     * @return array<string,mixed>
     */
    private function snapshot(Plan $plan, array $offer): array
    {
        return [
            'version' => $plan->version,
            'price' => (int) $offer['price'],
            'tokens' => (int) $offer['tokens'],
            'bonus_tokens' => (int) $offer['bonus_tokens'],
            'billing_type' => $plan->billing_type,
            'model_tier_key' => $plan->model_tier_key,
            'model_tier_name' => \App\Services\ModelTierService::DEFINITIONS[$plan->model_tier_key]['name'] ?? 'رایگان',
            'features' => $plan->features,
        ];
    }

    private function nextOrderNumber(): string
    {
        do {
            $number = 'PLN-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));
        } while (PlanPurchase::query()->where('order_number', $number)->exists());

        return $number;
    }
}
