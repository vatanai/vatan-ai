<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleRevision extends Model
{
    protected $fillable = ['article_id', 'admin_id', 'version', 'snapshot', 'action', 'change_note'];

    protected function casts(): array
    {
        return ['snapshot' => 'array'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
