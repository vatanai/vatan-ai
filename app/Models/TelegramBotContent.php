<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBotContent extends Model
{
    protected $fillable = [
        'content_key', 'title', 'body', 'media_type', 'media_file_id', 'buttons', 'is_active', 'metadata',
    ];

    protected $casts = [
        'buttons' => 'array',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];
}
