<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingLink extends Model
{
    protected $fillable = ['marketing_campaign_id', 'marketing_content_id', 'code', 'title', 'destination_url', 'channel', 'status', 'utm', 'created_by'];

    protected function casts(): array { return ['utm' => 'array']; }

    public function campaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id'); }
    public function content(): BelongsTo { return $this->belongsTo(MarketingContent::class, 'marketing_content_id'); }
    public function events(): HasMany { return $this->hasMany(MarketingEvent::class); }
}
