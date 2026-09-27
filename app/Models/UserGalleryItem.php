<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class UserGalleryItem extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'build_uuid',
        'source_type',
        'source_id',
        'original_path',
        'preview_path',
        'thumbnail_path',
        'disk',
        'mime_type',
        'preview_mime_type',
        'thumbnail_mime_type',
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

    protected static function booted(): void
    {
        static::saving(function (UserGalleryItem $item): void {
            if ($item->order_id && ($item->isDirty('order_id') || ! $item->build_uuid)) {
                $item->build_uuid = Order::query()->whereKey($item->order_id)->value('build_uuid');
            }
            $item->build_uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
