<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * موتور فید — تنظیمات نسخه‌دار هر بستر: سبک چیدمان، وزن اندازه‌ی کاشی‌ها، سطح تصادفی بودن.
 */
class FeedSetting extends Model
{
    protected $fillable = [
        'feed_surface_id', 'layout_style', 'tile_weights',
        'randomness_level', 'campaign_ratio', 'is_active_version',
    ];

    protected $casts = [
        'tile_weights' => 'array',
        'is_active_version' => 'boolean',
    ];

    // سبک‌های آماده‌ی چیدمان — قابل انتخاب از داشبورد
    public const LAYOUT_PRESETS = [
        'classic'  => ['size-1x1' => 64, 'size-wide' => 14, 'size-tall' => 14, 'size-big' => 8],
        'dense'    => ['size-1x1' => 85, 'size-wide' => 7,  'size-tall' => 7,  'size-big' => 1],
        'magazine' => ['size-1x1' => 40, 'size-wide' => 20, 'size-tall' => 20, 'size-big' => 20],
    ];

    public function surface(): BelongsTo
    {
        return $this->belongsTo(FeedSurface::class, 'feed_surface_id');
    }
}
