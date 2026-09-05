<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoStudioSocialPrompt extends Model
{
    protected $fillable = ['admin_id', 'platform', 'prompt'];
}
