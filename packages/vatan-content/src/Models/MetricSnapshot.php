<?php

namespace Vatan\Content\Models;

use Illuminate\Database\Eloquent\Model;

class MetricSnapshot extends Model
{
    protected $table = 'cf_metric_snapshots';
    protected $guarded = ['id'];
    protected $casts = ['metrics' => 'array', 'raw' => 'array', 'captured_at' => 'datetime'];

}
