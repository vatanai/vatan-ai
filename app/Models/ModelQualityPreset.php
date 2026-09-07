<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelQualityPreset extends Model
{
    public const KEYS = ['preset_1', 'preset_2', 'preset_3', 'preset_4'];

    protected $fillable = ['preset_key', 'name', 'configuration', 'is_default_for_product_creation'];

    protected $casts = [
        'configuration' => 'array',
        'is_default_for_product_creation' => 'boolean',
    ];

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
