<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleGallery extends Model
{
    protected $fillable = ['article_id', 'product_category_id', 'title', 'description', 'source_type', 'display_style', 'settings', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArticleGalleryItem::class)->where('is_active', true)->orderBy('sort_order');
    }
}
