<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthRawRecord extends Model
{
    protected $fillable = [
        'growth_data_source_id', 'external_id', 'record_type', 'payload',
        'normalized_data', 'normalization_status', 'error_message',
        'received_at', 'normalized_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'normalized_data' => 'array',
            'received_at' => 'datetime',
            'normalized_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(GrowthDataSource::class, 'growth_data_source_id');
    }
}
