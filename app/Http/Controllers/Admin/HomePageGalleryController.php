<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePageGallery;
use App\Models\Product;
use App\Services\ProductImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomePageGalleryController extends Controller
{
    public function __construct(private readonly ProductImageOptimizer $imageOptimizer)
    {
    }

    public function index(): View
    {
        return $this->galleryView();
    }

    public function show(HomePageGallery $gallery): View
    {
        return $this->galleryView($gallery);
    }

    private function galleryView(?HomePageGallery $gallery = null): View
    {
        $galleries = HomePageGallery::query()->ordered()->get();

        return view('admin.home-builder.galleries.index', [
            'galleries' => $galleries,
            'gallery' => $gallery,
            'products' => Product::query()->where('status', 'active')->latest()->get(),
        ]);
    }

    public function update(Request $request, HomePageGallery $gallery): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['nullable', 'array', 'max:18'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'uploads' => ['nullable', 'array', 'max:12'],
            'uploads.*' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:12000'],
            'keep_uploads' => ['nullable', 'array'],
            'keep_uploads.*' => ['string', 'max:500'],
            'keep_static_media' => ['nullable', 'array'],
            'keep_static_media.*' => ['string', 'max:2000'],
            'item_settings' => ['nullable', 'array'],
            'item_settings.*.title' => ['nullable', 'string', 'max:120'],
            'item_settings.*.tag' => ['nullable', 'string', 'max:60'],
            'item_settings.*.display_text' => ['nullable', 'string', 'max:120'],
            'item_settings.*.link_url' => ['nullable', 'string', 'max:2048'],
            'item_settings.*.link_label' => ['nullable', 'string', 'max:60'],
            'item_settings.*.show_text' => ['nullable', 'boolean'],
            'item_settings.*.open_in_new_tab' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $items = [];
        foreach ($data['product_ids'] ?? [] as $productId) {
            $productId = (int) $productId;
            $items[] = array_merge(['type' => 'product', 'product_id' => $productId], $this->presentation(
                $data['item_settings'][$this->settingsKey('product', (string) $productId)] ?? []
            ));
        }

        foreach ($data['keep_uploads'] ?? [] as $path) {
            if (Storage::disk('public')->exists($path)) {
                $items[] = array_merge(['type' => 'upload', 'path' => $path, 'title' => 'تصویر کاور'], $this->presentation(
                    $data['item_settings'][$this->settingsKey('upload', $path)] ?? []
                ));
            }
        }

        foreach ($data['keep_static_media'] ?? [] as $encodedItem) {
            $item = json_decode(base64_decode($encodedItem, true) ?: '', true);
            $path = $item['path'] ?? null;

            if (!is_array($item) || !in_array($item['type'] ?? null, ['asset', 'video'], true)
                || !is_string($path) || !str_starts_with($path, 'assets/') || !is_file(public_path($path))) {
                continue;
            }

            $items[] = array_merge(array_filter([
                'type' => $item['type'],
                'path' => $path,
                'poster' => $item['poster'] ?? null,
                'title' => $item['title'] ?? 'نمونه وطن',
                'tag' => $item['tag'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''), $this->presentation(
                $data['item_settings'][$this->settingsKey($item['type'], $path)] ?? []
            ));
        }

        foreach ($request->file('uploads', []) as $index => $file) {
            if ($file) {
                $items[] = array_merge([
                    'type' => 'upload',
                    'path' => $this->imageOptimizer->store($file, 'home-page-galleries'),
                    'title' => 'تصویر کاور',
                ], $this->presentation($data['item_settings']['new-upload-' . $index] ?? []));
            }
        }

        $gallery->update([
            'items' => $items,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.home-builder.galleries.show', $gallery)
            ->with('success', 'گالری صفحه اصلی ذخیره و برای سایت منتشر شد.');
    }

    private function settingsKey(string $type, string $identity): string
    {
        return $type . '-' . md5($type . '|' . $identity);
    }

    private function presentation(array $settings): array
    {
        $link = trim((string) ($settings['link_url'] ?? ''));
        if ($link !== '' && !preg_match('~^(https?://|/|#)~u', $link)) {
            $link = '';
        }

        return array_filter([
            'title' => trim((string) ($settings['title'] ?? '')) ?: null,
            'tag' => trim((string) ($settings['tag'] ?? '')) ?: null,
            'display_text' => trim((string) ($settings['display_text'] ?? '')) ?: null,
            'link_url' => $link ?: null,
            'link_label' => trim((string) ($settings['link_label'] ?? '')) ?: null,
            'show_text' => !empty($settings['show_text']) ? true : null,
            'open_in_new_tab' => !empty($settings['open_in_new_tab']) ? true : null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
