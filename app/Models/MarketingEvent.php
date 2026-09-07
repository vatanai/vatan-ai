<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingEvent extends Model
{
    protected $fillable = ['event_uuid', 'parent_event_uuid', 'event_type', 'channel', 'processing_status', 'marketing_campaign_id', 'marketing_content_id', 'marketing_scenario_id', 'marketing_operation_run_id', 'marketing_link_id', 'external_id', 'actor_ref', 'visitor_ref', 'payload', 'error_message', 'occurred_at', 'processed_at'];

    protected function casts(): array { return ['payload' => 'array', 'occurred_at' => 'datetime', 'processed_at' => 'datetime']; }

    public function campaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id'); }
    public function content(): BelongsTo { return $this->belongsTo(MarketingContent::class, 'marketing_content_id'); }
    public function scenario(): BelongsTo { return $this->belongsTo(MarketingScenario::class, 'marketing_scenario_id'); }
    public function run(): BelongsTo { return $this->belongsTo(MarketingOperationRun::class, 'marketing_operation_run_id'); }
    public function link(): BelongsTo { return $this->belongsTo(MarketingLink::class, 'marketing_link_id'); }
}
