<?php

namespace App\Services\HomeBuilder;

use App\Models\Category;
use App\Models\HomeSection;

class HomeSectionLinkService
{
    /** مقصد دکمه «مشاهده همه»؛ لینک دستی بر حالت خودکار اولویت دارد. */
    public function viewAllUrl(HomeSection $section): ?string
    {
        if (! $section->setting('show_view_all', true)) {
            return null;
        }

        if ($section->setting('view_all_link_mode', 'auto') === 'manual') {
            return $this->normalizeManualUrl((string) $section->setting('view_all_link'));
        }

        if ($section->type === 'category_slider') {
            return route('products.index');
        }

        return match ((string) $section->setting('source', 'latest')) {
            'trending' => route('app.trends'),
            'featured' => route('products.index', ['featured' => 1]),
            'video' => route('products.index', ['video' => 1]),
            'manual' => $this->manualProductsUrl($section),
            'category' => $this->categoryUrl($section) ?? $this->legacyCategoryUrl($section),
            default => route('products.index'),
        };
    }

    private function categoryUrl(HomeSection $section): ?string
    {
        $categoryId = (int) $section->setting('category_id');
        $category = $categoryId ? Category::active()->find($categoryId) : null;

        return $category?->url();
    }

    private function manualProductsUrl(HomeSection $section): string
    {
        $ids = collect((array) $section->setting('product_ids', []))
            ->map(fn ($item) => (int) (is_array($item) ? ($item['id'] ?? 0) : $item))
            ->filter()
            ->unique()
            ->values()
            ->implode(',');

        return route('products.index', array_filter(['product_ids' => $ids]));
    }

    private function legacyCategoryUrl(HomeSection $section): string
    {
        return route('products.index', array_filter([
            'legacy_category' => $section->setting('category_value'),
            'legacy_subcategory' => $section->setting('subcategory_value'),
        ]));
    }

    private function normalizeManualUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (preg_match('#^/products(?=\?|$)#', $url) === 1) {
            $query = parse_url($url, PHP_URL_QUERY);

            return route('products.index') . ($query ? '?' . $query : '');
        }

        if (str_starts_with($url, '/')) {
            return url($url);
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
