<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramEvent extends Model
{
    protected $fillable = [
        'update_id', 'telegram_user_id', 'event_type', 'chat_id', 'payload', 'occurred_at',
    ];

    protected $casts = [
        'update_id' => 'integer',
        'payload' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
