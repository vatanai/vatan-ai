<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramDeepLink extends Model
{
    protected $fillable = [
        'token', 'product_id', 'source', 'source_channel', 'source_campaign', 'message_id',
        'click_count', 'last_clicked_at', 'is_active', 'metadata',
    ];

    protected $casts = [
        'click_count' => 'integer',
        'last_clicked_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
