<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    protected $fillable = [
        'telegram_id', 'user_id', 'username', 'first_name', 'last_name', 'language_code',
        'phone', 'phone_verified_at', 'is_premium', 'is_blocked', 'blocked_at',
        'started_at', 'last_active_at', 'registration_completed_at', 'registration_state',
        'registration_payload', 'metadata',
    ];

    protected $casts = [
        'telegram_id' => 'integer',
        'phone_verified_at' => 'datetime',
        'phone' => 'encrypted',
        'is_premium' => 'boolean',
        'is_blocked' => 'boolean',
        'blocked_at' => 'datetime',
        'started_at' => 'datetime',
        'last_active_at' => 'datetime',
        'registration_completed_at' => 'datetime',
        'registration_payload' => 'array',
        'metadata' => 'array',
    ];

    protected $hidden = ['phone', 'registration_payload'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productClicks(): HasMany
    {
        return $this->hasMany(TelegramProductClick::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TelegramEvent::class);
    }
}
