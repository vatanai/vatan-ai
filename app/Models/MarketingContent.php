<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingContent extends Model
{
    protected $fillable = ['marketing_campaign_id', 'marketing_scenario_id', 'product_id', 'growth_content_id', 'title', 'channel', 'content_type', 'status', 'external_id', 'external_url', 'publish_at', 'published_at', 'hook', 'caption', 'keyword', 'media_path', 'media_url', 'metadata', 'created_by'];

    protected function casts(): array { return ['publish_at' => 'datetime', 'published_at' => 'datetime', 'metadata' => 'array']; }

    public function campaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id'); }
    public function scenario(): BelongsTo { return $this->belongsTo(MarketingScenario::class, 'marketing_scenario_id'); }
    public function growthContent(): BelongsTo { return $this->belongsTo(GrowthContent::class, 'growth_content_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function links(): HasMany { return $this->hasMany(MarketingLink::class); }
    public function events(): HasMany { return $this->hasMany(MarketingEvent::class); }
    public function runs(): HasMany { return $this->hasMany(MarketingOperationRun::class); }
    public function costEvents(): HasMany { return $this->hasMany(MarketingCostEvent::class); }
}
