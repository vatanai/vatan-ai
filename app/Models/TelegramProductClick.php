<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramProductClick extends Model
{
    protected $fillable = [
        'launch_token', 'telegram_user_id', 'product_id', 'product_key', 'source',
        'source_channel', 'source_campaign', 'channel_id', 'channel_username',
        'message_id', 'start_payload', 'metadata', 'clicked_at', 'opened_at', 'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'clicked_at' => 'datetime',
        'opened_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
