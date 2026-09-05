<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoStudioHookColorPreference extends Model
{
    protected $fillable = ['admin_id', 'target', 'color_key', 'is_hidden'];
}
