<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerJourney extends Model
{
    protected $fillable = [
        'user_id', 'point', 'substage', 'status', 'source', 'entered_at',
        'last_activity_at', 'next_action_at', 'cycle_step', 'cycle_step_at',
        'readiness_score', 'last_reason', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'point' => 'integer',
            'entered_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'next_action_at' => 'datetime',
            'cycle_step' => 'integer',
            'cycle_step_at' => 'datetime',
            'readiness_score' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(CustomerJourneyEvent::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CustomerJourneyTask::class);
    }
}
