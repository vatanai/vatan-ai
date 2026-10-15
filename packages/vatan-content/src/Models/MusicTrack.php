<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class MusicTrack extends Model
{
    protected $table = 'cf_music';
    protected $guarded = ['id'];
    protected $casts = ['sectors' => 'array', 'meta' => 'array', 'commercial_ok' => 'boolean', 'last_used_at' => 'datetime'];

}
