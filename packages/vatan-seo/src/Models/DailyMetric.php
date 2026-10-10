<?php

namespace Vatan\Seo\Models;

class DailyMetric extends SeoModel
{
    protected $table = 'seo_daily_metrics';

    protected function casts(): array
    {
        return ['date' => 'date', 'position_buckets' => 'array', 'ctr' => 'float', 'position' => 'float'];
    }
}
