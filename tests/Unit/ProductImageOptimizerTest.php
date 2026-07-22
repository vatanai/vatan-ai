<?php

namespace Tests\Unit;

use App\Services\ProductImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageOptimizerTest extends TestCase
{
    public function test_suitable_image_is_kept_byte_for_byte(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('suitable.jpg', 800, 600);
        $originalHash = hash_file('sha256', $file->getRealPath());

        $path = app(ProductImageOptimizer::class)->store($file, 'products/main');

        Storage::disk('public')->assertExists($path);
        $this->assertSame($originalHash, hash('sha256', Storage::disk('public')->get($path)));
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_large_image_is_resized_without_changing_its_ratio(): void
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('large.jpg', 2400, 1200);

        $path = app(ProductImageOptimizer::class)->store($file, 'products/main');
        $stored = Storage::disk('public')->get($path);
        $info = getimagesizefromstring($stored);

        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame(1600, $info[0]);
        $this->assertSame(800, $info[1]);
        $this->assertSame(2.0, $info[0] / $info[1]);
    }

    public function test_existing_suitable_product_image_keeps_the_same_path(): void
    {
        Storage::fake('public');
        $source = UploadedFile::fake()->image('existing.jpg', 800, 600);
        $path = $source->storeAs('products/main', 'existing.jpg', 'public');

        $result = app(ProductImageOptimizer::class)->optimizeStored($path, 'products/main');

        $this->assertSame($path, $result);
        Storage::disk('public')->assertExists($path);
    }

    public function test_existing_large_product_image_creates_replacement_without_deleting_original(): void
    {
        Storage::fake('public');
        $source = UploadedFile::fake()->image('existing-large.jpg', 2400, 1200);
        $path = $source->storeAs('products/main', 'existing-large.jpg', 'public');

        $result = app(ProductImageOptimizer::class)->optimizeStored($path, 'products/main');

        $this->assertNotSame($path, $result);
        $this->assertStringEndsWith('.webp', $result);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertExists($result);
        $info = getimagesizefromstring(Storage::disk('public')->get($result));
        $this->assertSame([1600, 800], [$info[0], $info[1]]);
    }
}
