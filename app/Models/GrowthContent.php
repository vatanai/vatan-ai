<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowthContent extends Model
{
    protected $fillable = [
        'growth_link_id', 'title', 'channel', 'content_type', 'external_id',
        'external_url', 'status', 'impressions', 'engagements', 'comments',
        'shares', 'likes', 'saves', 'published_at', 'metrics_updated_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'metrics_updated_at' => 'datetime'];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(GrowthLink::class, 'growth_link_id');
    }

    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(GrowthContentDailyMetric::class);
    }
}
