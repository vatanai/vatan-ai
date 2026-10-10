<?php

namespace Vatan\Seo\Models;

class Audit extends SeoModel
{
    protected $table = 'seo_audits';

    protected function casts(): array
    {
        return ['summary' => 'array', 'issues' => 'array', 'pages' => 'array'];
    }
}
