<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductMetricEvent extends Model
{
    protected $fillable = [
        'product_id',
        'event_type',
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
