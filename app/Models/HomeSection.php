<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * یک بلوک/Section مستقل در صفحه Home (فیچر Home Builder).
 * هیچ ارتباط مستقیمی با بخش‌های دیگر پروژه (محصولات/اکسپلور/CRM) ندارد؛
 * فقط در settings به id محصول/دسته‌بندی ارجاع می‌دهد که در HomeSectionRenderService حل می‌شود.
 */
class HomeSection extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED, self::STATUS_HIDDEN];

    public const TYPES = [
        'hero',
        'product_slider',
        'product_grid',
        'category_slider',
        'banner',
        'collection',
        'text',
        'spacer',
    ];

    public const DEFAULT_PAGE_KEY = 'app_home';

    protected $fillable = [
        'page_key',
        'type',
        'layout',
        'title_fa',
        'subtitle_fa',
        'settings',
        'responsive',
        'status',
        'position',
        'published_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'responsive' => 'array',
        'position' => 'integer',
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'page_key' => self::DEFAULT_PAGE_KEY,
        'layout' => 'default',
        'status' => self::STATUS_DRAFT,
        'position' => 0,
    ];

    // ── Scopes ──────────────────────────────────────────────

    public function scopeForPage(Builder $query, string $pageKey = self::DEFAULT_PAGE_KEY): Builder
    {
        return $query->where('page_key', $pageKey);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    // ── Helpers ─────────────────────────────────────────────

    /** تنظیمات ریسپانسیو پیش‌فرض وقتی هنوز چیزی ذخیره نشده (نمایش در همه دستگاه‌ها). */
    public function responsiveSettings(): array
    {
        return array_merge([
            'desktop' => true,
            'tablet' => true,
            'mobile' => true,
            'mobile_layout' => null,
        ], $this->responsive ?? []);
    }

    public function isVisibleOn(string $device): bool
    {
        return (bool) ($this->responsiveSettings()[$device] ?? true);
    }

    public function effectiveLayoutFor(string $device): string
    {
        $r = $this->responsiveSettings();
        if ($device === 'mobile' && ! empty($r['mobile_layout'])) {
            return $r['mobile_layout'];
        }

        return $this->layout ?: 'default';
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** یک مقدار از settings را با fallback امن برمی‌گرداند (بدون نیاز به بررسی null هر بار). */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }
}
