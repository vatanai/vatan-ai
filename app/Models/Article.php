<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'in_review', 'changes_requested', 'scheduled', 'published', 'archived'];

    public const CONTENT_TYPES = ['guide', 'prompt_pack', 'comparison', 'case_study', 'news', 'ideas', 'troubleshooting'];

    protected $fillable = [
        'article_category_id', 'article_author_id', 'reviewer_id', 'created_by', 'updated_by',
        'title', 'slug', 'excerpt', 'content_blocks', 'content_type', 'status', 'featured_image',
        'featured_image_alt', 'og_image', 'meta_title', 'meta_description', 'seo_keywords',
        'hashtags', 'seo_auto_fill', 'is_indexable', 'canonical_url', 'is_featured',
        'allow_comments', 'reading_minutes', 'sort_order', 'scheduled_at', 'published_at',
        'archived_at', 'last_viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'seo_keywords' => 'array',
            'hashtags' => 'array',
            'seo_auto_fill' => 'boolean',
            'is_indexable' => 'boolean',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'reading_minutes' => 'integer',
            'view_count' => 'integer',
            'unique_view_count' => 'integer',
            'share_count' => 'integer',
            'cta_click_count' => 'integer',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_viewed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(ArticleAuthor::class, 'article_author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ArticleTag::class, 'article_article_tag');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'article_product')
            ->withPivot(['placement', 'headline', 'description', 'cta_label', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(ArticleGallery::class)->orderBy('sort_order');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class)->whereNull('parent_id')->latest();
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(ArticleComment::class)
            ->whereNull('parent_id')
            ->where('status', 'approved')
            ->oldest();
    }

    public function events(): HasMany
    {
        return $this->hasMany(ArticleEvent::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ArticleRevision::class)->latest('version');
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(ArticleRedirect::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where(function (Builder $visible) {
            $visible->where('status', 'published')
                ->orWhere(function (Builder $scheduled) {
                    $scheduled->where('status', 'scheduled')->where('scheduled_at', '<=', now());
                });
        })->whereNotNull('published_at');
    }

    public function scopeIndexable(Builder $query): Builder
    {
        return $query->published()->where('is_indexable', true);
    }

    public function isPubliclyVisible(): bool
    {
        return ($this->status === 'published' || ($this->status === 'scheduled' && $this->scheduled_at?->isPast()))
            && $this->published_at !== null;
    }

    public function publicUrl(): string
    {
        return route('articles.show', $this->slug);
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_url ?: $this->publicUrl();
    }

    public function imageUrl(?string $path = null): ?string
    {
        $path ??= $this->featured_image;
        if (! $path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        if (str_starts_with($path, 'storage/')) return asset($path);

        return asset(ltrim($path, '/'));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'published' => 'منتشرشده',
            'scheduled' => 'زمان‌بندی‌شده',
            'in_review' => 'در انتظار بررسی',
            'changes_requested' => 'نیازمند اصلاح',
            'archived' => 'آرشیوشده',
            default => 'پیش‌نویس',
        };
    }

    public function contentTypeLabel(): string
    {
        return match ($this->content_type) {
            'prompt_pack' => 'بسته پرامپت',
            'comparison' => 'مقایسه و بررسی',
            'case_study' => 'مطالعه موردی',
            'news' => 'خبر و به‌روزرسانی',
            'ideas' => 'فهرست ایده‌ها',
            'troubleshooting' => 'رفع مشکل',
            default => 'راهنمای گام‌به‌گام',
        };
    }
}
