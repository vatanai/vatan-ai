<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موتور فید — امتیاز دستی ادمین (Boost) روی یک آیتم، per surface.
 */
class FeedContentScore extends Model
{
    protected $fillable = ['feed_content_item_id', 'feed_surface_id', 'manual_boost', 'computed_at'];

    protected $casts = [
        'computed_at' => 'datetime',
    ];

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(FeedContentItem::class, 'feed_content_item_id');
    }
}
