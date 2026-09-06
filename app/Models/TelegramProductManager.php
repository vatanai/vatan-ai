<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramProductManager extends Model
{
    protected $fillable = [
        'telegram_id',
        'name',
        'admin_id',
        'is_active',
        'metadata',
        'permissions',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'admin_id' => 'integer',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'permissions' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(TelegramProductDraft::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TelegramProductEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function can(string $permission): bool
    {
        if (! $this->is_active) return false;
        $defaults = self::defaultPermissions();
        return (bool) ($this->permissions[$permission] ?? $defaults[$permission] ?? false);
    }

    public static function defaultPermissions(): array
    {
        return ['create_product' => true, 'edit_product' => true, 'publish_product' => true, 'manage_prompts' => false, 'manage_bot_settings' => false, 'view_reports' => true];
    }
}
