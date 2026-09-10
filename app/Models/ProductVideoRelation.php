<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideoRelation extends Model
{
    protected $fillable = [
        'video_product_id',
        'photo_product_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function videoProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'video_product_id');
    }

    public function photoProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'photo_product_id');
    }
}
