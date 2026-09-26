<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerJourneyTask extends Model
{
    protected $fillable = [
        'customer_journey_id', 'user_id', 'point', 'sequence', 'task_type',
        'status', 'title', 'body', 'due_at', 'completed_at', 'completed_by', 'metadata',
    ];

    protected function casts(): array
    {
        return ['point' => 'integer', 'sequence' => 'integer', 'due_at' => 'datetime', 'completed_at' => 'datetime', 'metadata' => 'array'];
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
