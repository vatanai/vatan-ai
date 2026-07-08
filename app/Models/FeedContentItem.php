<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * موتور فید — نقطه‌ی اتصال عمومی به هر نوع محتوا (محصول/دسته/کمپین/...).
 * content_type از طریق morphMap در ExploreServiceProvider به کلاس واقعی نگاشت می‌شود.
 */
class FeedContentItem extends Model
{
    protected $fillable = ['content_type', 'content_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function scores(): HasMany
    {
        return $this->hasMany(FeedContentScore::class);
    }

    public function pins(): HasMany
    {
        return $this->hasMany(FeedPinnedItem::class);
    }
}
