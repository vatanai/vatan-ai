<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGalleryCostEvent extends Model
{
    protected $fillable = [
        'user_id',
        'user_gallery_item_id',
        'user_gallery_recreation_id',
        'user_gallery_notification_id',
        'cost_type',
        'status',
        'units',
        'unit',
        'unit_cost_toman',
        'cost_toman',
        'metadata',
        'incurred_at',
    ];

    protected function casts(): array
    {
        return [
            'units' => 'decimal:6',
            'unit_cost_toman' => 'integer',
            'cost_toman' => 'integer',
            'metadata' => 'array',
            'incurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function galleryItem(): BelongsTo { return $this->belongsTo(UserGalleryItem::class, 'user_gallery_item_id'); }
    public function recreation(): BelongsTo { return $this->belongsTo(UserGalleryRecreation::class, 'user_gallery_recreation_id'); }
    public function notification(): BelongsTo { return $this->belongsTo(UserGalleryNotification::class, 'user_gallery_notification_id'); }
}
