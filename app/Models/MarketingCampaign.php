<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    protected $fillable = ['name', 'code', 'objective', 'status', 'starts_at', 'ends_at', 'budget_toman', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'budget_toman' => 'integer'];
    }

    public function creator(): BelongsTo { return $this->belongsTo(Admin::class, 'created_by'); }
    public function contents(): HasMany { return $this->hasMany(MarketingContent::class); }
    public function links(): HasMany { return $this->hasMany(MarketingLink::class); }
    public function events(): HasMany { return $this->hasMany(MarketingEvent::class); }
    public function runs(): HasMany { return $this->hasMany(MarketingOperationRun::class); }
    public function costEvents(): HasMany { return $this->hasMany(MarketingCostEvent::class); }
}
