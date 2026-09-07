<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingScenarioVersion extends Model
{
    protected $fillable = ['marketing_scenario_id', 'version', 'status', 'trigger_keyword', 'public_reply', 'opening_message', 'opening_button_label', 'followup_message', 'followup_button_label', 'followup_url', 'rules', 'created_by', 'published_at'];

    protected function casts(): array { return ['version' => 'integer', 'rules' => 'array', 'published_at' => 'datetime']; }

    public function scenario(): BelongsTo { return $this->belongsTo(MarketingScenario::class, 'marketing_scenario_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(Admin::class, 'created_by'); }
}
