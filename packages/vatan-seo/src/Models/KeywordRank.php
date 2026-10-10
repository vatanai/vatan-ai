<?php

namespace Vatan\Seo\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KeywordRank extends SeoModel
{
    protected $table = 'seo_keyword_ranks';

    protected function casts(): array
    {
        return ['date' => 'date', 'position' => 'float'];
    }

    public function keyword(): BelongsTo { return $this->belongsTo(Keyword::class); }
}
