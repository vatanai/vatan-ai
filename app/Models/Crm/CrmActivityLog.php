<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivityLog extends Model
{
    use HasUuids;

    protected $table = 'crm_activity_log';

    protected $fillable = [
        'type',
        'personnel_id',
        'personnel_name',
        'action',
        'project_id',
        'task_id',
    ];

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(CrmPersonnel::class, 'personnel_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(CrmProject::class, 'project_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(CrmTask::class, 'task_id');
    }

    // ─── Helper: ثبت لاگ استاتیک ─────────────────────────────

    public static function log(
        string $type,
        string $action,
        ?string $personnelId = null,
        ?string $personnelName = null,
        ?string $projectId = null,
        ?string $taskId = null
    ): void {
        static::create([
            'type'           => $type,
            'action'         => $action,
            'personnel_id'   => $personnelId,
            'personnel_name' => $personnelName,
            'project_id'     => $projectId,
            'task_id'        => $taskId,
        ]);
    }
}
