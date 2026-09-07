<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleCategory extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image', 'meta_title', 'meta_description', 'is_active', 'is_indexable', 'sort_order'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_indexable' => 'boolean', 'sort_order' => 'integer'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->latest('published_at');
    }

    public function publicUrl(): string
    {
        return route('articles.category', $this->slug);
    }
}
