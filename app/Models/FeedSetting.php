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
        'include_filters', 'exclude_filters',
    ];

    protected $casts = [
        'tile_weights' => 'array',
        'is_active_version' => 'boolean',
        'include_filters' => 'array',
        'exclude_filters' => 'array',
    ];

    // وزن‌ها هنوز برای سازگاری با نسخه‌های قبلی موتور فید نگه‌داری می‌شوند.
    public const LAYOUT_PRESETS = [
        'classic'  => ['size-1x1' => 64, 'size-wide' => 14, 'size-tall' => 14, 'size-big' => 8],
        'dense'    => ['size-1x1' => 85, 'size-wide' => 7,  'size-tall' => 7,  'size-big' => 1],
        'magazine' => ['size-1x1' => 40, 'size-wide' => 20, 'size-tall' => 20, 'size-big' => 20],
        'excel_11' => ['size-1x1' => 68, 'size-wide' => 9,  'size-tall' => 14, 'size-big' => 9],
        'balanced' => ['size-1x1' => 62, 'size-wide' => 14, 'size-tall' => 10, 'size-big' => 14],
        'vertical' => ['size-1x1' => 58, 'size-wide' => 10, 'size-tall' => 20, 'size-big' => 12],
        'banner'   => ['size-1x1' => 58, 'size-wide' => 22, 'size-tall' => 8,  'size-big' => 12],
    ];

    /**
     * چهار معماری ذخیره‌شونده‌ی اکسپلور. مختصات بر مبنای گرید سه‌ستونه‌ی
     * موبایل است؛ در تبلت و دسکتاپ همین توالی با فاصله‌گذاری متناسب بازچینی می‌شود.
     */
    public const DISPLAY_PATTERNS = [
        'excel_11' => [
            'label' => 'الگوی اکسل ۱۱ ردیفی',
            'description' => 'همان معماری تأییدشده؛ چرخه‌ی ۱۱ ردیفی و ۲۲ کارت',
            'rows' => 11,
            'anchors' => [
                ['size-wide', 1, 2], ['size-tall', 2, 1], ['size-big', 4, 2],
                ['size-wide', 7, 1], ['size-tall', 8, 1], ['size-tall', 8, 3],
                ['size-big', 10, 1],
            ],
        ],
        'balanced' => [
            'label' => 'متعادل',
            'description' => 'توزیع متعادل مربع، افقی، عمودی و بزرگ',
            'rows' => 20,
            'anchors' => [
                ['size-big', 1, 1], ['size-wide', 4, 2], ['size-tall', 6, 1],
                ['size-big', 9, 2], ['size-wide', 12, 1], ['size-tall', 14, 3],
                ['size-big', 17, 1], ['size-wide', 20, 2],
            ],
        ],
        'vertical' => [
            'label' => 'عمودی',
            'description' => 'تأکید بیشتر روی قاب‌های عمودی و ویدیویی',
            'rows' => 20,
            'anchors' => [
                ['size-tall', 1, 3], ['size-big', 4, 1], ['size-wide', 7, 2],
                ['size-tall', 9, 1], ['size-big', 12, 2], ['size-wide', 15, 1],
                ['size-tall', 17, 3], ['size-wide', 20, 1],
            ],
        ],
        'banner' => [
            'label' => 'بنری',
            'description' => 'تأکید بیشتر روی قاب‌های افقی و کمپین‌ها',
            'rows' => 20,
            'anchors' => [
                ['size-wide', 1, 1], ['size-tall', 3, 3], ['size-big', 6, 1],
                ['size-wide', 9, 2], ['size-tall', 11, 1], ['size-big', 14, 2],
                ['size-wide', 17, 1], ['size-tall', 19, 3],
            ],
        ],
    ];

    public const LEGACY_LAYOUT_ALIASES = [
        'classic' => 'excel_11',
        'dense' => 'balanced',
        'magazine' => 'banner',
        'custom' => 'balanced',
    ];

    public static function effectiveLayoutStyle(?string $style): string
    {
        $resolved = self::LEGACY_LAYOUT_ALIASES[$style] ?? $style;

        return array_key_exists((string) $resolved, self::DISPLAY_PATTERNS)
            ? (string) $resolved
            : 'excel_11';
    }

    public function surface(): BelongsTo
    {
        return $this->belongsTo(FeedSurface::class, 'feed_surface_id');
    }
}
