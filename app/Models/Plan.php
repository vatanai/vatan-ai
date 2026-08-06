<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_code',
        'name',
        'slug',
        'price',
        'tokens',
        'image_path',
        'short_description',
        'description',
        'icon',
        'card_style',
        'badge_text',
        'tags',
        'features',
        'audience_overrides',
        'sort_order',
        'status',
        'version',
        'billing_type',
        'price_prefix',
        'compare_at_price',
        'token_label',
        'is_unlimited',
        'is_featured',
        'purchase_limit',
        'starts_at',
        'ends_at',
        'archived_at',
    ];

    protected $casts = [
        'price' => 'integer',
        'tokens' => 'integer',
        'compare_at_price' => 'integer',
        'tags' => 'array',
        'features' => 'array',
        'audience_overrides' => 'array',
        'is_unlimited' => 'boolean',
        'is_featured' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /**
     * متد هوشمند برای ساخت خودکار کد ۶ تا ۷ کاراکتری بر اساس اسلاگ دستی ادمین
     */
    protected static function booted()
    {
        static::creating(function ($plan) {
            if (empty($plan->plan_code)) {
                $englishSlug = Str::slug($plan->slug ?? $plan->name, '-');
                $cleanString = preg_replace('/[^A-Za-z0-9\-]/', '', $englishSlug);
                $parts = array_filter(explode('-', $cleanString));
                
                $shortPrefix = '';
                if (count($parts) >= 2) {
                    foreach ($parts as $part) {
                        $shortPrefix .= substr($part, 0, 2);
                    }
                } else {
                    $shortPrefix = substr(reset($parts) ?: 'GEN', 0, 4);
                }

                $shortPrefix = strtoupper(substr($shortPrefix, 0, 4));
                
                do {
                    $randomSuffix = strtoupper(Str::random(3));
                    $finalCode = 'PLN-' . $shortPrefix . $randomSuffix;
                } while (static::where('plan_code', $finalCode)->exists());

                $plan->plan_code = $finalCode;
            }
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PlanPurchase::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNull('archived_at')
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function offerFor(?User $user): array
    {
        $segment = $user?->customer_segment ?: 'regular';
        $override = ($this->audience_overrides ?? [])[$segment] ?? [];

        return [
            'segment' => $segment,
            'price' => array_key_exists('price', $override) && $override['price'] !== null
                ? (int) $override['price']
                : (int) $this->price,
            'tokens' => array_key_exists('tokens', $override) && $override['tokens'] !== null
                ? (int) $override['tokens']
                : (int) $this->tokens,
            'bonus_tokens' => (int) ($override['bonus_tokens'] ?? 0),
            'visible' => (bool) ($override['visible'] ?? true),
            'purchasable' => (bool) ($override['purchasable'] ?? true),
        ];
    }
}
