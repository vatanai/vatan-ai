<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutomationRule extends Model
{
    protected $table = 'instagram_automation_rules';

    protected $fillable = [
        'workspace_id', 'channel_id', 'name', 'trigger', 'scope_ref', 'keywords', 'match_mode', 'conditions', 'actions',
        'guards', 'status', 'version', 'priority', 'runs_count', 'success_count', 'failure_count', 'last_run_at',
        'last_error', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'conditions' => 'array',
            'actions' => 'array',
            'guards' => 'array',
            'last_run_at' => 'datetime',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(AutomationRun::class, 'rule_id');
    }

    public function successRate(): ?int
    {
        return $this->runs_count > 0 ? (int) round($this->success_count * 100 / $this->runs_count) : null;
    }
}
