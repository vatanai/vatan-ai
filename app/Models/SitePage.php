<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SitePage extends Model
{
    public const STATUSES = ['draft', 'published', 'scheduled', 'archived'];

    protected $fillable = [
        'key', 'name_fa', 'name_en', 'status', 'title', 'subtitle', 'meta_title',
        'meta_description', 'meta_keywords', 'og_image', 'canonical_url', 'is_indexable',
        'requires_auth', 'maintenance_mode', 'maintenance_message', 'display_settings',
        'content_settings', 'scheduled_at', 'published_at', 'version', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'meta_keywords' => 'array',
            'display_settings' => 'array',
            'content_settings' => 'array',
            'is_indexable' => 'boolean',
            'requires_auth' => 'boolean',
            'maintenance_mode' => 'boolean',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(SitePageRevision::class)->latest('version');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function isAvailable(): bool
    {
        return $this->status === 'published'
            || ($this->status === 'scheduled' && $this->scheduled_at?->isPast());
    }

    public function display(string $key, mixed $default = null): mixed
    {
        return data_get($this->display_settings, $key, $default);
    }

    public function content(string $key, mixed $default = null): mixed
    {
        return data_get($this->content_settings, $key, $default);
    }

    public function snapshot(): array
    {
        return collect($this->attributesToArray())->except(['id', 'key', 'created_at', 'updated_at'])->all();
    }
}
