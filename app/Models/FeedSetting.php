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
        'randomness_level', 'campaign_ratio', 'product_ratio', 'is_active_version',
        'include_filters', 'exclude_filters',
    ];

    protected $casts = [
        'tile_weights' => 'array',
        'is_active_version' => 'boolean',
        'product_ratio' => 'integer',
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

    /** نقشهٔ خانه‌های اشغال‌شده در چرخهٔ دقیق الگوی اکسل برای هر اندازهٔ نمایشگر. */
    public const EXCEL_RESPONSIVE_GRIDS = [
        'mobile' => [
            'columns' => 3,
            'rows' => 10,
            'cells' => [
                ['a', 'b', 'b'],
                ['c', 'd', 'd'],
                ['c', 'd', 'd'],
                ['e', 'f', 'g'],
                ['h', 'i', 'g'],
                ['j', 'j', 'k'],
                ['l', 'l', 'm'],
                ['l', 'l', 'm'],
                ['n', 'o', 'p'],
                ['n', 'q', 'r'],
            ],
        ],
        'desktop' => [
            'columns' => 6,
            'rows' => 6,
            'cells' => [
                ['a', 'b', 'b', 'c', 'd', 'e'],
                ['f', 'g', 'g', 'h', 'd', 'i'],
                ['f', 'g', 'g', 'j', 'j', 'k'],
                ['l', 'm', 'n', 'o', 'p', 'p'],
                ['q', 'm', 'r', 's', 't', 't'],
                ['u', 'u', 'v', 's', 't', 't'],
            ],
        ],
    ];

    /** چهار معماری ذخیره‌شونده‌ی اکسپلور؛ الگوی اکسل روی موبایل و دسکتاپ نقشهٔ دقیق دارد. */
    public const DISPLAY_PATTERNS = [
        'excel_11' => [
            'label' => 'الگوی اکسل',
            'description' => 'موبایل: ۳ ستون × ۱۰ ردیف؛ تبلت و دسکتاپ: ۶ ستون × ۶ ردیف. هر چرخه دقیقاً تکرار می‌شود.',
            'responsive_grids' => self::EXCEL_RESPONSIVE_GRIDS,
            // نگه‌داری شکل‌های کارت برای مصرف‌کننده‌های قدیمی تنظیمات.
            'sequence' => ['size-1x1', 'size-wide', 'size-tall', 'size-big'],
            'anchors' => [
                ['size-1x1', 1, 1], ['size-wide', 1, 2], ['size-tall', 2, 1],
                ['size-big', 2, 2], ['size-1x1', 4, 1], ['size-1x1', 4, 2],
                ['size-tall', 4, 3], ['size-1x1', 5, 1], ['size-1x1', 5, 2],
                ['size-wide', 6, 1], ['size-1x1', 6, 3], ['size-big', 7, 1],
                ['size-tall', 7, 3], ['size-tall', 9, 1], ['size-1x1', 9, 2],
                ['size-1x1', 9, 3], ['size-1x1', 10, 2], ['size-1x1', 10, 3],
            ],
            'rows' => 10,
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
