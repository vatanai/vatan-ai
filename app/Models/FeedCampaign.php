<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * موتور فید — کمپین/بنر تبلیغاتی، محتوای خودکفا بدون وابستگی به محصول.
 */
class FeedCampaign extends Model
{
    protected $fillable = [
        'title_fa', 'image', 'link', 'weight', 'start_at', 'end_at', 'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function scopeActiveNow($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', $now));
    }
}
