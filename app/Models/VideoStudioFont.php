<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoStudioFont extends Model
{
    protected $fillable = ['name', 'slug', 'file_path', 'is_active', 'is_default'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];
}
