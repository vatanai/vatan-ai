<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGallerySetting extends Model
{
    protected $fillable = [
        'user_id',
        'enabled',
        'consented_at',
        'consent_revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'consented_at' => 'datetime',
            'consent_revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(User $user): self
    {
        return static::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['enabled' => false],
        );
    }
}
