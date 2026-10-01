<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstagramPostProduct extends Model
{
    protected $table = 'instagram_post_products';

    protected $fillable = [
        'post_setting_id',
        'product_name',
        'description',
        'price',
        'image_url',
        'product_link',
        'sku',
        'inventory',
        'is_active',
        'custom_message',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
    ];

    public function postSetting(): BelongsTo
    {
        return $this->belongsTo(InstagramPostSetting::class, 'post_setting_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)->where('inventory', '>', 0);
    }

    public function isInStock(): bool
    {
        return $this->inventory > 0;
    }

    public function decrementInventory(int $quantity = 1): void
    {
        $this->decrement('inventory', $quantity);
    }

    public function incrementInventory(int $quantity = 1): void
    {
        $this->increment('inventory', $quantity);
    }
}
