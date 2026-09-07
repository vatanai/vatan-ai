<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthEvent extends Model
{
    public const TYPE_CLICK = 'click';
    public const TYPE_PAGE_OPEN = 'page_open';

    protected $fillable = [
        'growth_link_id', 'event_uuid', 'parent_event_uuid', 'event_type',
        'visitor_id', 'session_id', 'page_url', 'referrer', 'source', 'medium',
        'campaign', 'device_type', 'operating_system', 'browser', 'ip_hash',
        'country', 'city', 'is_new_visitor', 'metadata', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_new_visitor' => 'boolean',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(GrowthLink::class, 'growth_link_id');
    }
}
