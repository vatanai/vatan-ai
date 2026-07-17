<?php

namespace App\Models;

use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedImage extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'image_path',
        'user_prompt',
        'cost',
        'size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * تاریخ و ساعت ساخت به شمسی — برای نمایش در مودال پیش‌نمایش عکس پروفایل
     */
    public function getJalaliCreatedAtAttribute(): string
    {
        return Jalali::format($this->created_at);
    }

    /**
     * لینک مستقیم به صفحه محصول اصلی که این عکس با اون ساخته شده (در صورت وجود محصول)
     */
    public function getProductUrlAttribute(): ?string
    {
        if (!$this->product || empty($this->product->slug)) {
            return null;
        }

        return route('app.product', $this->product->route_slug);
    }
}