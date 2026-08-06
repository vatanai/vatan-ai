<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabRunOutput extends Model
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array', 'is_winner' => 'boolean', 'manual_score' => 'decimal:2'];
    public function run(): BelongsTo { return $this->belongsTo(LabRun::class, 'lab_run_id'); }
    public function scores(): HasMany { return $this->hasMany(LabScore::class); }
    public function getUrlAttribute(): ?string { return $this->output_path ? asset('storage/' . ltrim($this->output_path, '/')) : $this->remote_url; }
}
