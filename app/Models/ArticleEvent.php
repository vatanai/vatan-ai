<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['article_id', 'user_id', 'session_hash', 'event_type', 'metadata', 'ip_hash', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'datetime'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
