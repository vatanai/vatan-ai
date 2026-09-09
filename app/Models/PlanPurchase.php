<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanPurchase extends Model
{
    public const PENDING = 'pending';
    public const REDIRECTED = 'redirected';
    public const VERIFYING = 'verifying';
    public const COMPLETED = 'completed';
    public const FAILED = 'failed';
    public const EXPIRED = 'expired';

    protected $fillable = [
        'user_id', 'plan_id', 'plan_code', 'plan_name', 'customer_segment',
        'paid_amount', 'original_amount', 'discount_amount', 'discount_id', 'discount_code', 'referral_conversion_id', 'referral_snapshot',
        'granted_tokens', 'plan_snapshot', 'status',
        'payment_reference', 'order_number', 'gateway', 'gateway_track_id', 'gateway_reference', 'gateway_status',
        'billing_name', 'billing_email', 'billing_phone', 'failure_reason', 'callback_payload',
        'initiated_at', 'verified_at', 'failed_at', 'expired_at', 'purchased_at',
    ];

    protected $casts = [
        'paid_amount' => 'integer',
        'original_amount' => 'integer',
        'discount_amount' => 'integer',
        'discount_id' => 'integer',
        'referral_snapshot' => 'array',
        'granted_tokens' => 'integer',
        'plan_snapshot' => 'array',
        'callback_payload' => 'array',
        'initiated_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
        'expired_at' => 'datetime',
        'purchased_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function referralConversion(): BelongsTo
    {
        return $this->belongsTo(ReferralConversion::class, 'referral_conversion_id');
    }

    /** پرونده مالی متصل به این خرید پلن، در صورت ثبت شدن. */
    public function financeCase(): HasOne
    {
        return $this->hasOne(FinanceCase::class, 'anchor_plan_purchase_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::COMPLETED => 'تکمیل‌شده',
            self::PENDING => 'در انتظار پرداخت',
            self::REDIRECTED => 'در انتظار بازگشت از درگاه',
            self::VERIFYING => 'در حال بررسی',
            self::FAILED => 'ناموفق',
            self::EXPIRED => 'منقضی‌شده',
            default => 'نامشخص',
        };
    }
}
