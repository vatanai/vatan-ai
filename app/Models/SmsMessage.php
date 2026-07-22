<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $fillable = [
        'type', 'direction', 'recipient', 'sender', 'body', 'provider_id',
        'status', 'provider_status', 'metadata', 'scheduled_at', 'sent_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
