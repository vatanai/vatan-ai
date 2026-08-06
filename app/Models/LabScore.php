<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabScore extends Model
{
    protected $guarded = [];
    protected $casts = ['score' => 'integer'];
    public function output(): BelongsTo { return $this->belongsTo(LabRunOutput::class, 'lab_run_output_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class); }
}
