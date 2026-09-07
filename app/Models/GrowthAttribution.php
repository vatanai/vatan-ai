<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthAttribution extends Model
{
    protected $fillable = [
        'growth_link_id', 'click_event_uuid', 'visitor_id', 'user_id',
        'generation_id', 'order_id', 'plan_purchase_id', 'stage',
        'attribution_model', 'is_repeat', 'metadata', 'attributed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_repeat' => 'boolean',
            'metadata' => 'array',
            'attributed_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(GrowthLink::class, 'growth_link_id');
    }
}
