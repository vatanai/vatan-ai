<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthContentDailyMetric extends Model
{
    protected $fillable = [
        'growth_content_id', 'growth_data_source_id', 'growth_raw_record_id',
        'metric_date', 'views', 'engagements', 'comments', 'shares',
        'likes', 'saves', 'entry_mode', 'created_by',
    ];

    protected function casts(): array
    {
        return ['metric_date' => 'date'];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(GrowthContent::class, 'growth_content_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(GrowthDataSource::class, 'growth_data_source_id');
    }

    public function rawRecord(): BelongsTo
    {
        return $this->belongsTo(GrowthRawRecord::class, 'growth_raw_record_id');
    }
}
