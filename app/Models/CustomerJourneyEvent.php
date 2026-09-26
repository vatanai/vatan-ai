<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerJourneyEvent extends Model
{
    protected $fillable = [
        'customer_journey_id', 'user_id', 'event', 'point_before', 'point_after',
        'substage_before', 'substage_after', 'credit_before', 'credit_after',
        'actor_type', 'actor_id', 'reason', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }

    public function journey(): BelongsTo
    {
        return $this->belongsTo(CustomerJourney::class, 'customer_journey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
