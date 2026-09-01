<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramCampaignLog extends Model
{
    protected $fillable = [
        'campaign_id', 'telegram_user_id', 'sent_at', 'delivery_status', 'provider_message_id',
        'error_message', 'metadata',
    ];

    protected $casts = ['sent_at' => 'datetime', 'metadata' => 'array'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(TelegramCampaign::class, 'campaign_id');
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }
}
