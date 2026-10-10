<?php

namespace Vatan\Seo\Models;

class AiCall extends SeoModel
{
    protected $table = 'seo_ai_calls';

    protected function casts(): array
    {
        return ['cost_usd' => 'float', 'web_search' => 'boolean', 'ok' => 'boolean', 'cost_estimated' => 'boolean'];
    }
}
