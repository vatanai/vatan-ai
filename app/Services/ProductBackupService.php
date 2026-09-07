<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ProductBackupService
{
    private const DIRECTORY = 'backups/products';
    private const MEDIA_FIELDS = ['cover', 'thumbnail', 'og_image', 'preview_video_url', 'sample_outputs', 'before_images'];

    public function create(?array $productIds = null): string
    {
        $directory = storage_path('app/' . self::DIRECTORY);
        File::ensureDirectoryExists($directory);
        $filename = 'products-' . now()->format('Y-m-d-His') . '.zip';
        $zipPath = $directory . DIRECTORY_SEPARATOR . $filename;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ساخت فایل بک‌آپ ممکن نشد.');
        }

        $files = [];
        $productQuery = Product::withTrashed()->with('categories')->orderBy('id');
        if ($productIds !== null && $productIds !== []) {
            $productQuery->whereIn('id', array_values(array_unique(array_map('intval', $productIds))));
        }

        $products = $productQuery->get()->map(function (Product $product) use ($zip, &$files): array {
            $attributes = $product->getAttributes();
            foreach (self::MEDIA_FIELDS as $field) {
                $value = $product->getAttribute($field);
                foreach (is_array($value) ? $value : [$value] as $path) {
                    if (!is_string($path) || $path === '' || filter_var($path, FILTER_VALIDATE_URL) || !Storage::disk('public')->exists($path)) continue;
                    $archivePath = 'media/' . hash('sha256', $path) . '-' . basename($path);
                    if (!isset($files[$path])) {
                        $zip->addFromString($archivePath, Storage::disk('public')->get($path));
                        $files[$path] = ['original_path' => $path, 'archive_path' => $archivePath];
                    }
                }
            }
            return [
                'attributes' => $attributes,
                'categories' => $product->categories->map(fn ($category) => ['slug' => $category->slug, 'path' => $category->path])->values()->all(),
            ];
        })->values()->all();

        $zip->addFromString('manifest.json', json_encode([
            'format' => 'vatan-products-v1',
            'created_at' => now()->toIso8601String(),
            'products_count' => count($products),
            'selected_product_ids' => $productIds === null ? null : array_values(array_unique(array_map('intval', $productIds))),
            'files_count' => count($files),
            'files' => array_values($files),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $zip->addFromString('products.json', json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();
        return $zipPath;
    }

    public function restore(UploadedFile $upload): array
    {
        $temp = storage_path('app/' . self::DIRECTORY . '/restore-' . Str::random(16));
        File::ensureDirectoryExists($temp);
        $zip = new ZipArchive();
        try {
            if ($zip->open($upload->getRealPath()) !== true || $zip->locateName('manifest.json') === false || $zip->locateName('products.json') === false) {
                throw new RuntimeException('فایل بک‌آپ معتبر وطن نیست.');
            }
            $this->validateArchiveEntries($zip);
            $manifest = json_decode($zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            if (($manifest['format'] ?? '') !== 'vatan-products-v1') throw new RuntimeException('نسخه فایل بک‌آپ پشتیبانی نمی‌شود.');
            $products = json_decode($zip->getFromName('products.json'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($products)) throw new RuntimeException('فهرست محصولات داخل بک‌آپ معتبر نیست.');
            $zip->extractTo($temp);
            $restored = 0;
            DB::transaction(function () use ($products, $manifest, $temp, &$restored): void {
                foreach ($products as $row) {
                    $attributes = (array) ($row['attributes'] ?? []);
                    $slug = trim((string) ($attributes['slug'] ?? ''));
                    if ($slug === '') continue;
                    unset($attributes['id'], $attributes['created_at'], $attributes['updated_at'], $attributes['deleted_at']);
                    $product = Product::withTrashed()->where('slug', $slug)->first();
                    if ($product) {
                        $product->forceFill($attributes)->save();
                        if ($product->trashed()) $product->restore();
                    } else {
                        $product = Product::create($attributes);
                    }
                    $categoryIds = collect((array) ($row['categories'] ?? []))->map(fn (array $category) => \App\Models\Category::where('slug', $category['slug'] ?? '')->orWhere('path', $category['path'] ?? '')->value('id'))->filter()->values()->all();
                    if ($categoryIds !== []) $product->categories()->sync($categoryIds);
                    $restored++;
                }
                foreach ((array) ($manifest['files'] ?? []) as $file) {
                    $original = (string) ($file['original_path'] ?? '');
                    $archive = (string) ($file['archive_path'] ?? '');
                    if (!$this->safePath($original) || !$this->safePath($archive) || !Str::startsWith($archive, 'media/') || !File::exists($temp . DIRECTORY_SEPARATOR . $archive)) continue;
                    Storage::disk('public')->put($original, File::get($temp . DIRECTORY_SEPARATOR . $archive));
                }
            });
            return ['products' => $restored, 'files' => count((array) ($manifest['files'] ?? []))];
        } finally {
            if ($zip instanceof ZipArchive) $zip->close();
            File::deleteDirectory($temp);
        }
    }

    public function directory(): string { return storage_path('app/' . self::DIRECTORY); }

    private function safePath(string $path): bool
    {
        return $path !== '' && !Str::startsWith($path, ['/', '\\']) && !str_contains($path, '..') && !str_contains($path, '\\');
    }

    private function validateArchiveEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if ($name === 'manifest.json' || $name === 'products.json') continue;
            if (!Str::startsWith($name, 'media/') || !$this->safePath($name)) {
                throw new RuntimeException('ساختار فایل بک‌آپ معتبر نیست.');
            }
        }
    }
}
