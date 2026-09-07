<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralEvent extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'link_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PlanPurchase::class, 'plan_purchase_id');
    }
}
