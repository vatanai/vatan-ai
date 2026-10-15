<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    protected $table = 'cf_ai_usage';
    protected $guarded = ['id'];
    protected $casts = ['meta' => 'array'];

}
