<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingScenario extends Model
{
    protected $fillable = ['name', 'code', 'channel', 'trigger_type', 'status', 'active_version', 'description', 'settings', 'created_by'];

    protected function casts(): array { return ['active_version' => 'integer', 'settings' => 'array']; }

    public function creator(): BelongsTo { return $this->belongsTo(Admin::class, 'created_by'); }
    public function versions(): HasMany { return $this->hasMany(MarketingScenarioVersion::class); }
    public function contents(): HasMany { return $this->hasMany(MarketingContent::class); }
    public function events(): HasMany { return $this->hasMany(MarketingEvent::class); }
    public function runs(): HasMany { return $this->hasMany(MarketingOperationRun::class); }
}
