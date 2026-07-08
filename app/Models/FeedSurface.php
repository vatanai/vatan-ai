<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * موتور فید — بستر نمایش (Explore/Home/Trending/...).
 * جدا و مستقل از بقیه‌ی مدل‌های پروژه.
 */
class FeedSurface extends Model
{
    protected $fillable = ['key', 'title_fa', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function settings(): HasMany
    {
        return $this->hasMany(FeedSetting::class);
    }

    public function activeSetting(): ?FeedSetting
    {
        return $this->settings()->where('is_active_version', true)->latest('id')->first();
    }

    public function pinnedItems(): HasMany
    {
        return $this->hasMany(FeedPinnedItem::class);
    }
}
