<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingOperationRun extends Model
{
    protected $fillable = ['run_uuid', 'operation_type', 'status', 'attempt', 'idempotency_key', 'external_execution_id', 'marketing_campaign_id', 'marketing_content_id', 'marketing_scenario_id', 'started_at', 'finished_at', 'request_payload', 'response_payload', 'error_code', 'error_message'];

    protected function casts(): array { return ['attempt' => 'integer', 'started_at' => 'datetime', 'finished_at' => 'datetime', 'request_payload' => 'array', 'response_payload' => 'array']; }

    public function campaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id'); }
    public function content(): BelongsTo { return $this->belongsTo(MarketingContent::class, 'marketing_content_id'); }
    public function scenario(): BelongsTo { return $this->belongsTo(MarketingScenario::class, 'marketing_scenario_id'); }
    public function events(): HasMany { return $this->hasMany(MarketingEvent::class); }
    public function costEvents(): HasMany { return $this->hasMany(MarketingCostEvent::class); }
}
