<?php

namespace App\Services;

use App\Models\HomePageGallery;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class HomePageGalleryService
{
    /**
     * آیتم‌های قابل نمایش یک گالری را با URL امن آماده می‌کند. اگر هنوز در پنل
     * موردی برای این گالری ثبت نشده باشد، نمونه‌های فعلی صفحه حفظ می‌شوند.
     */
    public function visible(string $key, array $fallback): array
    {
        try {
            if (! Schema::hasTable('home_page_galleries')) {
                return $fallback;
            }

            $gallery = HomePageGallery::query()->where('key', $key)->where('is_active', true)->first();
            $items = is_array($gallery?->items) ? $gallery->items : [];

            $resolved = collect($items)->map(function (array $item) {
                if (($item['type'] ?? null) === 'product' && !empty($item['product_id'])) {
                    $product = Product::query()->where('status', 'active')->find($item['product_id']);

                    return $product ? [
                        'url' => $product->displayImageUrl(),
                        'title' => $item['title'] ?? $product->name_fa,
                        'tag' => $item['tag'] ?? null,
                        'display_text' => $item['display_text'] ?? null,
                        'link_url' => $item['link_url'] ?? null,
                        'link_label' => $item['link_label'] ?? null,
                        'show_text' => (bool) ($item['show_text'] ?? false),
                        'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                        'type' => 'product',
                        'media_type' => 'image',
                    ] : null;
                }

                $path = $item['path'] ?? null;
                if (in_array($item['type'] ?? null, ['asset', 'video'], true) && $path && is_file(public_path($path))) {
                    return [
                        'url' => asset($path),
                        'poster' => !empty($item['poster']) ? asset($item['poster']) : null,
                        'title' => $item['title'] ?? 'نمونه وطن',
                        'tag' => $item['tag'] ?? null,
                        'display_text' => $item['display_text'] ?? null,
                        'link_url' => $item['link_url'] ?? null,
                        'link_label' => $item['link_label'] ?? null,
                        'show_text' => (bool) ($item['show_text'] ?? false),
                        'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                        'type' => $item['type'],
                        'media_type' => ($item['type'] ?? null) === 'video' ? 'video' : 'image',
                    ];
                }

                if ($path && Storage::disk('public')->exists($path)) {
                    return [
                        'url' => asset('storage/' . $path),
                        'title' => $item['title'] ?? 'نمونه وطن',
                        'type' => 'upload',
                        'tag' => $item['tag'] ?? null,
                        'display_text' => $item['display_text'] ?? null,
                        'link_url' => $item['link_url'] ?? null,
                        'link_label' => $item['link_label'] ?? null,
                        'show_text' => (bool) ($item['show_text'] ?? false),
                        'open_in_new_tab' => (bool) ($item['open_in_new_tab'] ?? false),
                        'media_type' => 'image',
                    ];
                }

                return null;
            })->filter()->values()->all();

            return $resolved ?: $fallback;
        } catch (\Throwable $exception) {
            report($exception);

            return $fallback;
        }
    }
}
