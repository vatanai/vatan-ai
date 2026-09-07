<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class GrowthLink extends Model
{
    protected $fillable = [
        'title', 'slug', 'destination_url', 'channel', 'content_type',
        'campaign', 'is_active', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(GrowthEvent::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(GrowthContent::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(GrowthLinkSnapshot::class);
    }

    public function getShortUrlAttribute(): string
    {
        return route('growth.redirect', $this);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
