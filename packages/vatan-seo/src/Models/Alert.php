<?php

namespace Vatan\Seo\Models;

class Alert extends SeoModel
{
    protected $table = 'seo_alerts';

    protected function casts(): array
    {
        return ['data' => 'array', 'telegram_sent_at' => 'datetime', 'read_at' => 'datetime'];
    }
}
