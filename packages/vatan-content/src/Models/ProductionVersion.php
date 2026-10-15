<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionVersion extends Model
{
    protected $table = 'cf_production_versions';
    protected $guarded = ['id'];
    protected $casts = ['stories' => 'array', 'slides' => 'array', 'qc' => 'array'];

    public function production() { return $this->belongsTo(Production::class, 'production_id'); }
}
