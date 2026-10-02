<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRun extends Model
{
    protected $table = 'instagram_automation_runs';

    protected $fillable = ['workspace_id', 'rule_id', 'rule_version', 'message_id', 'contact_id', 'mode', 'status', 'decisions', 'error'];

    protected function casts(): array
    {
        return ['decisions' => 'array'];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'rule_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }
}
