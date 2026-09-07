<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGalleryItem extends Model
{
    protected $fillable = [
        'user_id',
        'source_type',
        'source_id',
        'original_path',
        'preview_path',
        'disk',
        'mime_type',
        'preview_mime_type',
        'size',
        'expires_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'source_id' => 'integer',
            'size' => 'integer',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(UserGallerySuggestion::class);
    }

    public function recreations(): HasMany
    {
        return $this->hasMany(UserGalleryRecreation::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(UserGalleryCampaign::class);
    }

    public function costEvents(): HasMany
    {
        return $this->hasMany(UserGalleryCostEvent::class);
    }
}
