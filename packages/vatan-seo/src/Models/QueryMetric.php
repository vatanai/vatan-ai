<?php

namespace Vatan\Seo\Models;

class QueryMetric extends SeoModel
{
    protected $table = 'seo_query_metrics';
    public $timestamps = false;

    protected function casts(): array
    {
        return ['date' => 'date', 'ctr' => 'float', 'position' => 'float'];
    }
}
