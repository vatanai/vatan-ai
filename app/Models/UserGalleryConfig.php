<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class UserGalleryConfig extends Model
{
    protected $fillable = [
        'enabled',
        'suggestions_enabled',
        'retention_days',
        'max_items_per_user',
        'max_storage_mb',
        'free_recreations_per_month',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'suggestions_enabled' => 'boolean',
            'retention_days' => 'integer',
            'max_items_per_user' => 'integer',
            'max_storage_mb' => 'integer',
            'free_recreations_per_month' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'enabled' => true,
                'suggestions_enabled' => true,
                'retention_days' => 60,
                'max_items_per_user' => 50,
                'max_storage_mb' => 100,
                'free_recreations_per_month' => 1,
            ],
        );
    }

    public static function isEnabled(): bool
    {
        return Schema::hasTable('user_gallery_configs') && static::current()->enabled;
    }

    public static function retentionDays(): int
    {
        return Schema::hasTable('user_gallery_configs')
            ? (int) static::current()->retention_days
            : 60;
    }
}
