<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmProject extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_projects';

    protected $fillable = [
        'name',
        'emoji',
        'status',
        'description',
        'deadline',
        'start_date',
        'end_date',
        'manager_id',
        'archived',
    ];

    protected $casts = [
        'archived' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────

    public function manager(): BelongsTo
    {
        return $this->belongsTo(CrmPersonnel::class, 'manager_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmPersonnel::class,
            'crm_project_personnel',
            'project_id',
            'personnel_id'
        );
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'project_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CrmActivityLog::class, 'project_id');
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function getProgressAttribute(): int
    {
        $tasks = $this->tasks()->withTrashed(false)->get();
        if ($tasks->isEmpty()) return 0;

        $total = $tasks->sum(function ($task) {
            $microtasks = $task->microtasks;
            if ($microtasks->isEmpty()) return $task->done ? 100 : 0;
            return $microtasks->count()
                ? (int) round($microtasks->where('done', true)->count() / $microtasks->count() * 100)
                : ($task->done ? 100 : 0);
        });

        return (int) round($total / $tasks->count());
    }
}
