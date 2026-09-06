<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPurchase extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'plan_id', 'plan_code', 'plan_name',
        'customer_segment', 'paid_amount', 'granted_tokens', 'plan_snapshot',
        'status', 'gateway', 'gateway_track_id', 'gateway_reference',
        'gateway_status', 'billing_name', 'billing_email', 'billing_phone',
        'failure_reason', 'callback_payload', 'payment_reference', 'initiated_at',
        'verified_at', 'failed_at', 'expired_at', 'purchased_at',
    ];

    protected $casts = [
        'paid_amount' => 'integer',
        'granted_tokens' => 'integer',
        'plan_snapshot' => 'array',
        'callback_payload' => 'array',
        'initiated_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
        'expired_at' => 'datetime',
        'purchased_at' => 'datetime',
    ];

    public const COMPLETED = 'completed';

    public const PENDING = 'pending';

    public const REDIRECTED = 'redirected';

    public const VERIFYING = 'verifying';

    public const FAILED = 'failed';

    public const EXPIRED = 'expired';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function financeSnapshot(): HasOne
    {
        return $this->hasOne(FinancePlanSnapshot::class);
    }

    public function financeCase(): HasOne
    {
        return $this->hasOne(FinanceCase::class, 'anchor_plan_purchase_id');
    }

    public function creditLots(): HasMany
    {
        return $this->hasMany(FinanceCreditLot::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }

    public static function statusLabel(string $status): string
    {
        return [
            self::PENDING => 'آماده پرداخت',
            self::REDIRECTED => 'در انتظار پرداخت',
            self::VERIFYING => 'در حال بررسی',
            self::COMPLETED => 'موفق',
            self::FAILED => 'ناموفق',
            self::EXPIRED => 'منقضی',
            'cancelled' => 'لغوشده',
        ][$status] ?? $status;
    }
}
