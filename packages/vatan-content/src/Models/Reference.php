<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    protected $table = 'cf_references';
    protected $guarded = ['id'];
    protected $casts = ['colors' => 'array', 'meta' => 'array'];

    public function style() { return $this->belongsTo(Style::class, 'style_id'); }
}
