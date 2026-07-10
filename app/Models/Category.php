<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'name_fa',
        'name_en',
        'slug',
        'path',
        'icon',
        'color',
        'image',
        'sort_order',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
    ];

    protected $casts = [
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'sort_order'  => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | روابط درختی
    |--------------------------------------------------------------------------
    */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** بارگذاری بازگشتی کل زیرشاخه‌ها (چند سطح) */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /*
    |--------------------------------------------------------------------------
    | اسکوپ‌ها
    |--------------------------------------------------------------------------
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /*
    |--------------------------------------------------------------------------
    | کمک‌متدهای درخت
    |--------------------------------------------------------------------------
    */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function isLeaf(): bool
    {
        return $this->children()->doesntExist();
    }

    /** زنجیره‌ی اجداد از ریشه تا والد مستقیم (بدون خودِ رکورد) */
    public function ancestors(): Collection
    {
        $chain = collect();
        $node  = $this->parent;
        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }
        return $chain;
    }

    /** خروجی آماده برای Breadcrumb: [ ['name'=>.., 'url'=>..], ... ] */
    public function breadcrumb(): array
    {
        return $this->ancestors()
            ->push($this)
            ->map(fn (Category $c) => [
                'name' => $c->name_fa,
                'url'  => $c->url(),
            ])->all();
    }

    /*
    |--------------------------------------------------------------------------
    | URL و سئو (مقدار پیش‌فرض هوشمند در صورت خالی بودن فیلد)
    |--------------------------------------------------------------------------
    */
    public function url(): string
    {
        return url('/category/' . ($this->path ?: $this->slug));
    }

    public function metaTitle(): string
    {
        return $this->meta_title ?: ($this->name_fa . ' | وطن');
    }

    public function metaDescription(): string
    {
        return $this->meta_description
            ?: ('مجموعه محصولات ' . $this->name_fa . ' در پلتفرم هوش مصنوعی وطن — انتخاب محصول، آپلود اطلاعات و دریافت خروجی نهایی.');
    }

    public function canonicalUrl(): string
    {
        return $this->canonical_url ?: $this->url();
    }

    public function ogTitle(): string
    {
        return $this->og_title ?: $this->metaTitle();
    }

    public function ogDescription(): string
    {
        return $this->og_description ?: $this->metaDescription();
    }
}
