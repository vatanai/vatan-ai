<?php

namespace App\Models\SmartInstagram;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'instagram_tags';

    protected $fillable = ['workspace_id', 'name', 'tone'];
}
