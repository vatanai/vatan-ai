<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'value', 'max_discount_credits',
        'min_order_credits', 'usage_limit', 'usage_limit_per_user', 'used_count',
        'scope', 'product_ids', 'category_ids', 'first_order_only', 'is_active',
        'starts_at', 'ends_at', 'description',
    ];

    protected $casts = [
        'product_ids' => 'array', 'category_ids' => 'array',
        'first_order_only' => 'boolean', 'is_active' => 'boolean',
        'starts_at' => 'datetime', 'ends_at' => 'datetime',
    ];

    public function orders(): HasMany { return $this->hasMany(Order::class); }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($q) => $q->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'));
    }

    public function calculateCredits(int $credits): int
    {
        if ($credits < $this->min_order_credits) return 0;
        $amount = match ($this->type) {
            'percent' => (int) floor($credits * min(100, $this->value) / 100),
            'fixed' => min($credits, $this->value),
            'free' => $credits,
            default => 0,
        };
        return min($credits, $this->max_discount_credits ? min($amount, $this->max_discount_credits) : $amount);
    }
}
