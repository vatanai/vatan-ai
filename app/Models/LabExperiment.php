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
        'estimated_cost_toman' => 'decimal:2',
        'actual_cost_toman' => 'decimal:2',
        'total_cost_usd' => 'decimal:6',
        'total_cost_toman' => 'decimal:2',
        'lab_cost_usd' => 'decimal:6',
        'lab_cost_toman' => 'decimal:2',
        'lab_enabled' => 'boolean',
        'input_image_size' => 'integer',
        'exchange_rate_usd' => 'decimal:2',
        'exchange_rate_eur' => 'decimal:2',
        'exchange_rate_usdt' => 'decimal:2',
        'tested_at' => 'datetime',
        'evaluated_at' => 'datetime',
        'overall_score' => 'decimal:2',
        'exchange_rate_irr' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'applied_at' => 'datetime',
    ];

    public function effectiveCostUsd(): float
    {
        if ($this->relationLoaded('runs') && $this->runs->isNotEmpty()) {
            return (float) $this->runs->sum(fn (LabRun $run) => (float) $run->actual_cost_usd > 0 ? (float) $run->actual_cost_usd : (float) $run->estimated_cost_usd);
        }

        return (float) $this->total_cost_usd > 0
            ? (float) $this->total_cost_usd
            : ((float) $this->actual_cost_usd > 0 ? (float) $this->actual_cost_usd : (float) $this->estimated_cost_usd);
    }

    public function effectiveCostToman(): float
    {
        if ($this->relationLoaded('runs') && $this->runs->isNotEmpty()) {
            return (float) $this->runs->sum(function (LabRun $run) {
                if ((float) $run->actual_cost_toman > 0) return (float) $run->actual_cost_toman;
                if ((float) $run->estimated_cost_toman > 0) return (float) $run->estimated_cost_toman;
                $usd = (float) $run->actual_cost_usd > 0 ? (float) $run->actual_cost_usd : (float) $run->estimated_cost_usd;
                return $usd * ((float) $run->exchange_rate_irr / 10);
            });
        }

        return (float) $this->total_cost_toman > 0
            ? (float) $this->total_cost_toman
            : ((float) $this->actual_cost_toman > 0 ? (float) $this->actual_cost_toman : (float) $this->estimated_cost_toman);
    }

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
            'failed' => 'ناموفق', 'partially_failed' => 'بخشی ناموفق', 'evaluated' => 'ارزیابی‌شده', 'finalized' => 'نهایی‌شده', 'cancelled' => 'لغوشده',
        ][$this->status] ?? $this->status;
    }

    public function getEstimatedCostIrrValueAttribute(): float
    {
        return (float) $this->estimated_cost_usd * (float) $this->exchange_rate_irr;
    }
}
