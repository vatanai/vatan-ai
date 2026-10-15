<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class Style extends Model
{
    protected $table = 'cf_styles';
    protected $guarded = ['id'];
    protected $casts = ['goals' => 'array', 'sectors' => 'array', 'meta' => 'array', 'last_used_at' => 'datetime', 'recipe' => 'array', 'palette' => 'array', 'fonts' => 'array'];

    public function references() { return $this->hasMany(Reference::class, 'style_id'); }
}
