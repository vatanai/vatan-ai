<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPersonnel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'crm_personnel';

    protected $fillable = [
        'name',
        'mobile',
        'role',
        'email',
        'join_date',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // ─── Relations ───────────────────────────────────────────

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(
            CrmProject::class,
            'crm_project_personnel',
            'personnel_id',
            'project_id'
        );
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(CrmProject::class, 'manager_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'assignee_id');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CrmActivityLog::class, 'personnel_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(CrmAttendance::class, 'personnel_id');
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
