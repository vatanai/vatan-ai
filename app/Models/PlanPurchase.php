<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPurchase extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'plan_code', 'plan_name', 'customer_segment',
        'paid_amount', 'granted_tokens', 'plan_snapshot', 'status',
        'payment_reference', 'purchased_at',
    ];

    protected $casts = [
        'paid_amount' => 'integer',
        'granted_tokens' => 'integer',
        'plan_snapshot' => 'array',
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
}
