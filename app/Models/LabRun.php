<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabRun extends Model
{
    protected $guarded = [];
    protected $casts = [
        'model_snapshot' => 'array', 'parameters' => 'array', 'provider_response' => 'array',
        'estimated_cost_usd' => 'decimal:6', 'actual_cost_usd' => 'decimal:6', 'final_score' => 'decimal:2', 'is_selected' => 'boolean', 'exchange_rate_irr' => 'decimal:2',
        'started_at' => 'datetime', 'completed_at' => 'datetime',
    ];
    public function experiment(): BelongsTo { return $this->belongsTo(LabExperiment::class, 'lab_experiment_id'); }
    public function aiModel(): BelongsTo { return $this->belongsTo(AiModel::class); }
    public function outputs(): HasMany { return $this->hasMany(LabRunOutput::class); }
    public function getStatusLabelAttribute(): string
    {
        return ['queued' => 'در صف', 'processing' => 'در حال اجرا', 'completed' => 'تکمیل‌شده', 'failed' => 'ناموفق', 'cancelled' => 'لغوشده'][$this->status] ?? $this->status;
    }
}
