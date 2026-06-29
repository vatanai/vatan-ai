<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMicrotask extends Model
{
    use HasUuids;

    protected $table = 'crm_microtasks';

    protected $fillable = [
        'task_id',
        'text',
        'done',
        'sort_order',
    ];

    protected $casts = [
        'done' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(CrmTask::class, 'task_id');
    }
}
