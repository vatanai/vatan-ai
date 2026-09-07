<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrowthDataMapping extends Model
{
    protected $fillable = [
        'growth_data_source_id', 'source_field', 'growth_metric', 'transform',
        'is_primary', 'fallback_order', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean', 'is_active' => 'boolean'];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(GrowthDataSource::class, 'growth_data_source_id');
    }
}
