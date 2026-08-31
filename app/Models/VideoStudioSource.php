<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoStudioSource extends Model
{
    protected $fillable = ['name', 'type', 'source_url', 'is_active', 'used_count', 'last_used_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
