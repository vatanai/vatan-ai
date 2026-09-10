<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * منطق واحد جست‌وجوی محصولات برای هوم، اکسپلور، ترندز و کاتالوگ.
 * هر واژه باید در حداقل یکی از فیلدهای قابل جست‌وجو پیدا شود؛ بنابراین
 * محصول نامرتبط صرفاً برای پرکردن تعداد نتایج به خروجی اضافه نمی‌شود.
 */
class ProductSearchService
{
    public function apply(Builder $query, string $term): Builder
    {
        $terms = $this->terms($term);

        if ($terms === []) {
            return $query;
        }

        return $query->where(function (Builder $search) use ($terms) {
            foreach ($terms as $word) {
                $search->where(function (Builder $match) use ($word) {
                    $like = '%' . addcslashes($word, '%_\\') . '%';

                    $match->where('name_fa', 'like', $like)
                        ->orWhere('name_en', 'like', $like)
                        ->orWhere('description_fa', 'like', $like)
                        ->orWhere('description_en', 'like', $like)
                        ->orWhere('meta_title', 'like', $like)
                        ->orWhere('meta_description', 'like', $like)
                        ->orWhere('meta_keywords', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('subcategory', 'like', $like)
                        ->orWhere('tags', 'like', $like)
                        ->orWhereHas('categories', function (Builder $categories) use ($like) {
                            $categories->where('name_fa', 'like', $like)
                                ->orWhere('name_en', 'like', $like)
                                ->orWhere('name', 'like', $like)
                                ->orWhere('slug', 'like', $like)
                                ->orWhere('path', 'like', $like);
                        });
                });
            }
        });
    }

    /**
     * حروف عربی رایج و هشتگ را برای جست‌وجوی قابل پیش‌بینی یکدست می‌کند.
     * مترادف‌سازی عمداً انجام نمی‌شود تا نتیجه فقط به محتوای واقعی محصول وابسته باشد.
     *
     * @return array<int, string>
     */
    public function terms(string $term): array
    {
        $term = trim(str_replace(["\u{064A}", "\u{0649}", "\u{06CC}"], 'ی', $term));
        $term = str_replace(["\u{0643}", "\u{06A9}"], 'ک', $term);

        return collect(preg_split('/\s+/u', $term) ?: [])
            ->map(fn (string $word) => trim(ltrim($word, '#')))
            ->filter(fn (string $word) => $word !== '')
            ->unique()
            ->values()
            ->all();
    }
}
