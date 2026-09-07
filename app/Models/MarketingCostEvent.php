<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCostEvent extends Model
{
    protected $fillable = ['marketing_operation_run_id', 'marketing_campaign_id', 'marketing_content_id', 'provider', 'service', 'units', 'unit', 'unit_cost_usd', 'fx_rate_toman', 'cost_toman', 'status', 'metadata', 'incurred_at'];

    protected function casts(): array { return ['units' => 'decimal:6', 'unit_cost_usd' => 'decimal:8', 'fx_rate_toman' => 'decimal:2', 'cost_toman' => 'integer', 'metadata' => 'array', 'incurred_at' => 'datetime']; }

    public function run(): BelongsTo { return $this->belongsTo(MarketingOperationRun::class, 'marketing_operation_run_id'); }
    public function campaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id'); }
    public function content(): BelongsTo { return $this->belongsTo(MarketingContent::class, 'marketing_content_id'); }
}
