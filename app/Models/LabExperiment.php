<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LabExperiment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'settings' => 'array',
        'estimated_cost_usd' => 'decimal:6',
        'actual_cost_usd' => 'decimal:6',
        'estimated_cost_irr' => 'decimal:2',
        'actual_cost_irr' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'exchange_rate_irr' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $experiment) {
            $experiment->uuid ??= (string) Str::uuid();
        });
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_experiment_id'); }
    public function images(): HasMany { return $this->hasMany(LabExperimentImage::class); }
    public function runs(): HasMany { return $this->hasMany(LabRun::class); }
    public function auditLogs(): HasMany { return $this->hasMany(LabAuditLog::class); }

    public function getStatusLabelAttribute(): string
    {
        return [
            'queued' => 'در صف', 'processing' => 'در حال اجرا', 'completed' => 'تکمیل‌شده',
            'failed' => 'ناموفق', 'cancelled' => 'لغوشده',
        ][$this->status] ?? $this->status;
    }

    public function getEstimatedCostIrrValueAttribute(): float
    {
        return (float) $this->estimated_cost_usd * (float) $this->exchange_rate_irr;
    }
}
