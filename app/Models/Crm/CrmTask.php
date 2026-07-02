<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTask extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'done',
        'deadline',
        'start_date',
        'assignee_id',
        'completed_at',
    ];

    protected $casts = [
        'done'         => 'boolean',
        'completed_at' => 'datetime',
    ];

    // ─── Relations ───────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(CrmProject::class, 'project_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(CrmPersonnel::class, 'assignee_id');
    }

    public function microtasks(): HasMany
    {
        return $this->hasMany(CrmMicrotask::class, 'task_id')->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CrmTaskComment::class, 'task_id')->latest();
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function getProgressAttribute(): int
    {
        $microtasks = $this->microtasks;
        if ($microtasks->isEmpty()) return $this->done ? 100 : 0;
        return (int) round($microtasks->where('done', true)->count() / $microtasks->count() * 100);
    }
}
