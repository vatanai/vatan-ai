<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGalleryCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'user_gallery_item_id',
        'code',
        'campaign_type',
        'status',
        'title',
        'body',
        'consent_snapshot',
        'metadata',
        'scheduled_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'consent_snapshot' => 'array',
            'metadata' => 'array',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function galleryItem(): BelongsTo { return $this->belongsTo(UserGalleryItem::class, 'user_gallery_item_id'); }
    public function notifications(): HasMany { return $this->hasMany(UserGalleryNotification::class); }
}
