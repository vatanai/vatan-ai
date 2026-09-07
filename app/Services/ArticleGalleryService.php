<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleGallery;
use App\Models\GeneratedImage;
use App\Models\Product;
use Illuminate\Support\Collection;

class ArticleGalleryService
{
    public function resolve(Article $article): Collection
    {
        $article->loadMissing(['galleries.items.product', 'products.categories']);

        return $article->galleries->where('is_active', true)->map(function (ArticleGallery $gallery) use ($article) {
            $limit = min(24, max(1, (int) data_get($gallery->settings, 'limit', 12)));
            $items = match ($gallery->source_type) {
                'products' => $this->productItems(
                    Product::query()->whereIn('id', data_get($gallery->settings, 'product_ids', $article->products->pluck('id')->all()))
                        ->where('status', 'active')->limit($limit)->get()
                ),
                'category' => $this->productItems(
                    Product::query()->with('categories')->where('status', 'active')
                        ->where(function ($query) use ($gallery) {
                            $query->where('category_id', $gallery->product_category_id)
                                ->orWhereHas('categories', fn ($categories) => $categories->whereKey($gallery->product_category_id));
                        })->latest()->limit($limit)->get()
                ),
                'generated' => $this->generatedItems(
                    GeneratedImage::query()->with('product')
                        ->whereIn('id', data_get($gallery->settings, 'generated_image_ids', []))
                        ->latest()->limit($limit)->get()
                ),
                default => $gallery->items->take($limit)->map(fn ($item) => [
                    'kind' => 'media',
                    'media_type' => $item->media_type,
                    'url' => $item->mediaUrl(),
                    'title' => $item->title,
                    'description' => $item->description,
                    'alt' => $item->alt_text ?: $item->title,
                    'link_url' => $item->link_url,
                ])->values(),
            };

            return ['model' => $gallery, 'items' => $items];
        })->filter(fn ($gallery) => $gallery['items']->isNotEmpty())->values();
    }

    private function productItems(Collection $products): Collection
    {
        return $products->map(fn (Product $product) => [
            'kind' => 'product',
            'media_type' => $product->media_type === 'video' ? 'video' : 'image',
            'url' => $this->assetUrl($product->thumbnail),
            'video_url' => $product->preview_video_url,
            'title' => $product->name_fa,
            'description' => $product->description_fa,
            'alt' => $product->name_fa,
            'link_url' => route('app.product', $product->route_slug),
            'product_id' => $product->id,
            'credit_cost' => $product->credit_cost,
        ])->filter(fn ($item) => $item['url'] || $item['video_url'])->values();
    }

    private function generatedItems(Collection $images): Collection
    {
        return $images->map(fn (GeneratedImage $image) => [
            'kind' => 'generated',
            'media_type' => 'image',
            'url' => $this->assetUrl($image->image_path),
            'title' => $image->product?->name_fa ?: 'خروجی ساخته‌شده در وطن',
            'description' => $image->user_prompt,
            'alt' => $image->product?->name_fa ?: 'تصویر ساخته‌شده با هوش مصنوعی در وطن',
            'link_url' => $image->product?->route_slug ? route('app.product', $image->product->route_slug) : null,
            'product_id' => $image->product_id,
        ])->values();
    }

    private function assetUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;

        $cleanPath = ltrim($path, '/');
        return asset(str_starts_with($cleanPath, 'storage/') ? $cleanPath : 'storage/' . $cleanPath);
    }
}
