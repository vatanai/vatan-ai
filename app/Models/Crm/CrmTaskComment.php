<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTaskComment extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_task_comments';

    protected $fillable = [
        'task_id',
        'author_name',
        'body',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(CrmTask::class, 'task_id');
    }
}
