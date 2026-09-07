<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleAuthor extends Model
{
    protected $fillable = ['admin_id', 'name', 'slug', 'title', 'bio', 'avatar', 'same_as', 'is_active'];

    protected function casts(): array
    {
        return ['same_as' => 'array', 'is_active' => 'boolean'];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->latest('published_at');
    }

    public function publicUrl(): string
    {
        return route('articles.author', $this->slug);
    }
}
