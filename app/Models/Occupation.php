<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Occupation extends Model
{
    public const GROUPS = [
        'fashion' => 'پوشاک و مزون', 'bags-shoes' => 'کیف، کفش و چرم', 'beauty' => 'زیبایی و مراقبت',
        'jewelry' => 'طلا، جواهر و اکسسوری', 'food' => 'غذا و نوشیدنی', 'home' => 'خانه و دکوراسیون',
        'digital' => 'کالای دیجیتال', 'health' => 'سلامت و پزشکی', 'sports' => 'ورزش', 'kids' => 'کودک و نوزاد',
        'automotive' => 'خودرو و حمل‌ونقل', 'services' => 'خدمات و آموزش', 'other' => 'سایر',
    ];

    protected $fillable = ['name_fa', 'name_en', 'slug', 'group_key', 'description', 'is_active', 'sort'];
    protected $casts = ['is_active' => 'boolean', 'sort' => 'integer'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('group_key')->orderBy('sort')->orderBy('name_fa');
    }
}
