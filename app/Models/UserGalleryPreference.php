<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGalleryPreference extends Model
{
    protected $fillable = [
        'user_id',
        'suggestions_enabled',
        'marketing_enabled',
        'occasion_enabled',
        'reminders_enabled',
        'consented_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'suggestions_enabled' => 'boolean',
            'marketing_enabled' => 'boolean',
            'occasion_enabled' => 'boolean',
            'reminders_enabled' => 'boolean',
            'consented_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function forUser(User $user): self
    {
        return static::query()->firstOrCreate(['user_id' => $user->id]);
    }
}
