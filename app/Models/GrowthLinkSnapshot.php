<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthLinkSnapshot extends Model
{
    protected $table = 'growth_link_24h_snapshots';

    protected $fillable = [
        'growth_link_id', 'window_start', 'window_end', 'total_clicks',
        'unique_clicks', 'page_opens', 'failed_opens', 'success_rate',
        'mobile_clicks', 'desktop_clicks', 'new_visitors', 'repeat_visitors',
        'breakdowns',
    ];

    protected function casts(): array
    {
        return [
            'window_start' => 'datetime',
            'window_end' => 'datetime',
            'success_rate' => 'float',
            'breakdowns' => 'array',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(GrowthLink::class, 'growth_link_id');
    }
}
