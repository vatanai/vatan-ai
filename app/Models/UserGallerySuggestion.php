<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGallerySuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'user_gallery_item_id',
        'product_id',
        'suggestion_type',
        'status',
        'title',
        'body',
        'preview_payload',
        'scheduled_at',
        'expires_at',
        'viewed_at',
        'clicked_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'preview_payload' => 'array',
            'scheduled_at' => 'datetime',
            'expires_at' => 'datetime',
            'viewed_at' => 'datetime',
            'clicked_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function galleryItem(): BelongsTo { return $this->belongsTo(UserGalleryItem::class, 'user_gallery_item_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function notifications(): \Illuminate\Database\Eloquent\Relations\HasMany { return $this->hasMany(UserGalleryNotification::class); }
}
