<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrowthDataSource extends Model
{
    protected $fillable = [
        'name', 'slug', 'source_type', 'channel', 'ingestion_method',
        'data_types', 'config', 'connection_status', 'health_status',
        'is_active', 'is_system', 'priority', 'last_synced_at',
        'last_received_at', 'last_error', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'data_types' => 'array',
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'last_synced_at' => 'datetime',
            'last_received_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(GrowthDataMapping::class);
    }

    public function rawRecords(): HasMany
    {
        return $this->hasMany(GrowthRawRecord::class);
    }

    public function dailyMetrics(): HasMany
    {
        return $this->hasMany(GrowthContentDailyMetric::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }
}
