<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabAuditLog extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];
    public function experiment(): BelongsTo { return $this->belongsTo(LabExperiment::class, 'lab_experiment_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
}
