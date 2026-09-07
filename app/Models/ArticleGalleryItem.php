<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleGalleryItem extends Model
{
    protected $fillable = ['article_gallery_id', 'product_id', 'generated_image_id', 'generation_id', 'media_type', 'media_path', 'title', 'description', 'alt_text', 'link_url', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(ArticleGallery::class, 'article_gallery_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function generatedImage(): BelongsTo
    {
        return $this->belongsTo(GeneratedImage::class);
    }

    public function mediaUrl(): string
    {
        if (str_starts_with($this->media_path, 'http://') || str_starts_with($this->media_path, 'https://')) {
            return $this->media_path;
        }

        return asset(ltrim($this->media_path, '/'));
    }
}
