<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleRedirect extends Model
{
    protected $fillable = ['article_id', 'old_slug', 'target_url', 'hit_count', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'hit_count' => 'integer'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
