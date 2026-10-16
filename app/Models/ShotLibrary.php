<?php

namespace App\Models;

use App\Services\ProductShots\ShotGrammar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * یک «شات» در کتابخانه‌ی مشترک: ترکیبی از توکن‌های زبان شات.
 */
class ShotLibrary extends Model
{
    protected $table = 'shot_library';

    protected $fillable = [
        'product_id', 'key', 'name_fa', 'name_en', 'description_fa', 'category', 'niche_tags', 'tokens',
        'prompt_template', 'default_credits', 'aspect_ratio_default', 'sample_image', 'is_active', 'sort',
    ];

    protected $casts = [
        'niche_tags' => 'array',
        'tokens' => 'array',
        'default_credits' => 'integer',
        'is_active' => 'boolean',
        'sort' => 'integer',
        'product_id' => 'integer',
    ];

    public function productShots(): HasMany
    {
        return $this->hasMany(ProductShot::class, 'shot_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** فقط کتابخانه‌ی عمومی (بدون اسلایدهای اختصاصی محصولات). */
    public function scopeLibrary(Builder $query): Builder
    {
        return self::supportsOwnedSlides() ? $query->whereNull('product_id') : $query;
    }

    private static ?bool $ownedSlidesColumn = null;

    /** ستون product_id (مایگریشن ۲۰۲۶_۱۰_۱۶) هنوز روی سرور اجرا نشده باشد، رفتار قبلی حفظ می‌شود. */
    public static function supportsOwnedSlides(): bool
    {
        return self::$ownedSlidesColumn ??= \Illuminate\Support\Facades\Schema::hasColumn('shot_library', 'product_id');
    }

    public static function flushSchemaCache(): void
    {
        self::$ownedSlidesColumn = null;
    }

    /** اسلاید اختصاصی یک محصول (عکس نمونه + پرامپت مخصوص همان محصول). */
    public function isCustom(): bool
    {
        return ! empty($this->attributes['product_id']);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort')->orderBy('id');
    }

    public function normalizedTokens(): array
    {
        return ShotGrammar::normalize((array) $this->tokens);
    }

    public function tokenLabels(): array
    {
        return ShotGrammar::labels((array) $this->tokens);
    }

    public function sampleImageUrl(): ?string
    {
        return self::publicUrl($this->sample_image);
    }

    public static function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . ltrim($path, '/'));
        }
        if (is_file(public_path(ltrim($path, '/')))) {
            return asset(ltrim($path, '/'));
        }

        return null;
    }
}
