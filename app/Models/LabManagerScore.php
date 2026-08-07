<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabManagerScore extends Model
{
    protected $guarded = [];

    protected $casts = [
        'overall_score' => 'integer',
        'usage_priority' => 'integer',
        'rated_at' => 'datetime',
    ];

    public function output(): BelongsTo
    {
        return $this->belongsTo(LabRunOutput::class, 'lab_run_output_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
