<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramProductEvent extends Model
{
    protected $fillable = [
        'update_id',
        'telegram_product_manager_id',
        'draft_id',
        'event_type',
        'payload',
        'response',
        'processed_at',
    ];

    protected $casts = [
        'update_id' => 'integer',
        'payload' => 'array',
        'response' => 'array',
        'processed_at' => 'datetime',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(TelegramProductManager::class, 'telegram_product_manager_id');
    }

    public function draft(): BelongsTo
    {
        return $this->belongsTo(TelegramProductDraft::class, 'draft_id');
    }
}
