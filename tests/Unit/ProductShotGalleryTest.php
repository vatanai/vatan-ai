<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\ProductShots\ShotPackService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductShotGalleryTest extends TestCase
{
    public function test_cover_input_other_product_images_and_shot_samples_are_all_shown_in_order(): void
    {
        Storage::fake('public');
        foreach (['cover', 'input', 'extra-1', 'extra-2', 'shot'] as $name) {
            Storage::disk('public')->put("products/{$name}.webp", $name);
        }

        $product = new Product();
        $product->name_fa = 'پک آزمایشی';
        $product->cover = 'products/cover.webp';
        $product->shot_settings = ['input_image' => 'products/input.webp'];
        $product->sample_outputs = ['products/extra-1.webp', 'products/extra-2.webp'];

        $gallery = app(ShotPackService::class)->productGallery($product, [
            ['name' => 'نمونهٔ اول', 'sample_url' => asset('storage/products/extra-1.webp')],
            ['name' => 'نمونهٔ دوم', 'sample_url' => asset('storage/products/shot.webp')],
        ]);

        $this->assertSame(['cover', 'input', 'slide', 'slide', 'slide'], $gallery->pluck('kind')->all());
        $this->assertSame([
            asset('storage/products/cover.webp'),
            asset('storage/products/input.webp'),
            asset('storage/products/extra-1.webp'),
            asset('storage/products/extra-2.webp'),
            asset('storage/products/shot.webp'),
        ], $gallery->pluck('url')->all());
    }
}
