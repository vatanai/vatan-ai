<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Insight extends Model
{
    protected $table = 'cf_insights';
    protected $guarded = ['id'];
    protected $casts = ['evidence' => 'array', 'decided_at' => 'datetime'];

}
