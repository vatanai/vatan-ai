<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabExperimentImage extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];
    public function experiment(): BelongsTo { return $this->belongsTo(LabExperiment::class, 'lab_experiment_id'); }
    public function getUrlAttribute(): string { return asset('storage/' . ltrim($this->image_path, '/')); }
}
