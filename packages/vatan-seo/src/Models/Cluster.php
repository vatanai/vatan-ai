<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends SeoModel
{
    protected $table = 'seo_keyword_clusters';

    public function keywords(): HasMany { return $this->hasMany(Keyword::class, 'cluster_id'); }
}
