<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** اتصال یک شات کتابخانه به یک محصول پروداکتی (فعال، پیش‌فرض، قیمت، ترتیب). */
class ProductShot extends Model
{
    protected $fillable = ['product_id', 'shot_id', 'enabled', 'is_default', 'credits_override', 'sample_image', 'sort'];

    protected $casts = [
        'enabled' => 'boolean',
        'is_default' => 'boolean',
        'credits_override' => 'integer',
        'sort' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function shot(): BelongsTo
    {
        return $this->belongsTo(ShotLibrary::class, 'shot_id');
    }

    public function credits(): int
    {
        return max(0, (int) ($this->credits_override ?? $this->shot?->default_credits ?? 0));
    }

    public function sampleImageUrl(): ?string
    {
        return ShotLibrary::publicUrl($this->sample_image) ?? $this->shot?->sampleImageUrl();
    }
}
