<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCreditPreset extends Model
{
    protected $fillable = [
        'preset_key',
        'name',
        'standard_credit_cost',
        'professional_credit_cost',
        'best_credit_cost',
        'is_default_for_product_creation',
    ];

    protected $casts = [
        'standard_credit_cost' => 'integer',
        'professional_credit_cost' => 'integer',
        'best_credit_cost' => 'integer',
        'is_default_for_product_creation' => 'boolean',
    ];

    public function costs(): array
    {
        return [
            'standard' => max(1, (int) ($this->standard_credit_cost ?: Product::DEFAULT_QUALITY_CREDIT_COSTS['standard'])),
            'professional' => max(1, (int) ($this->professional_credit_cost ?: Product::DEFAULT_QUALITY_CREDIT_COSTS['professional'])),
            'best' => max(1, (int) ($this->best_credit_cost ?: Product::DEFAULT_QUALITY_CREDIT_COSTS['best'])),
        ];
    }

    public static function defaultKey(): string
    {
        return (string) (static::query()
            ->where('is_default_for_product_creation', true)
            ->value('preset_key')
            ?: static::query()->orderBy('id')->value('preset_key')
            ?: 'preset_1');
    }

    public static function availableKeys(): array
    {
        return static::query()->pluck('preset_key')->filter()->values()->all();
    }
}
