<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGalleryRecreation extends Model
{
    protected $fillable = [
        'user_id',
        'user_gallery_item_id',
        'product_id',
        'order_id',
        'pricing_mode',
        'list_credit_cost',
        'charged_credit_cost',
        'status',
        'started_at',
        'completed_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'list_credit_cost' => 'integer',
            'charged_credit_cost' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function galleryItem(): BelongsTo { return $this->belongsTo(UserGalleryItem::class, 'user_gallery_item_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function costEvents(): HasMany { return $this->hasMany(UserGalleryCostEvent::class); }
}
