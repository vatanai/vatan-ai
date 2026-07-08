<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موتور فید — آیتم سنجاق‌شده در موقعیت ثابت یک بستر.
 */
class FeedPinnedItem extends Model
{
    protected $fillable = ['feed_surface_id', 'feed_content_item_id', 'position'];

    public function surface(): BelongsTo
    {
        return $this->belongsTo(FeedSurface::class, 'feed_surface_id');
    }

    public function contentItem(): BelongsTo
    {
        return $this->belongsTo(FeedContentItem::class, 'feed_content_item_id');
    }
}
