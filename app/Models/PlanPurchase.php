<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'paid_amount', 'original_amount', 'discount_amount', 'referral_conversion_id', 'referral_snapshot',
        'granted_tokens', 'plan_snapshot', 'status',
        'payment_reference', 'order_number', 'gateway', 'gateway_track_id', 'gateway_reference', 'gateway_status',
        'billing_name', 'billing_email', 'billing_phone', 'failure_reason', 'callback_payload',
        'initiated_at', 'verified_at', 'failed_at', 'expired_at', 'purchased_at',
    ];

    protected $casts = [
        'paid_amount' => 'integer',
        'original_amount' => 'integer',
        'discount_amount' => 'integer',
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

    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }
}
