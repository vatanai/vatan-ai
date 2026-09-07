<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductBackupService;
use App\Services\SiteBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ProductBackupSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_products_backup_contains_only_requested_products(): void
    {
        $first = $this->product('محصول اول', 'backup-selection-first');
        $second = $this->product('محصول دوم', 'backup-selection-second');

        $path = app(ProductBackupService::class)->create([$second->id]);

        try {
            $zip = new ZipArchive();
            self::assertSame(true, $zip->open($path));
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            $products = json_decode((string) $zip->getFromName('products.json'), true, 512, JSON_THROW_ON_ERROR);
            $zip->close();

            self::assertSame(1, $manifest['products_count']);
            self::assertSame([$second->id], $manifest['selected_product_ids']);
            self::assertCount(1, $products);
            self::assertSame($second->slug, $products[0]['attributes']['slug']);
            self::assertNotSame($first->slug, $products[0]['attributes']['slug']);
        } finally {
            File::delete($path);
        }
    }

    public function test_site_backup_contains_database_snapshot_and_manifest_for_selected_section(): void
    {
        $product = $this->product('محصول پشتیبان سایت', 'site-backup-product');
        $path = app(SiteBackupService::class)->create(['products']);

        try {
            $zip = new ZipArchive();
            self::assertSame(true, $zip->open($path));
            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true, 512, JSON_THROW_ON_ERROR);
            $productTable = collect($manifest['tables'])->firstWhere('name', 'products');
            $snapshot = json_decode((string) $zip->getFromName('database/tables/' . $productTable['archive_name']), true, 512, JSON_THROW_ON_ERROR);
            $zip->close();

            self::assertSame('vatan-site-v1', $manifest['format']);
            self::assertSame(['products'], $manifest['sections']);
            self::assertNotEmpty($manifest['sql_dump']);
            self::assertSame($product->id, collect($snapshot['rows'])->firstWhere('id', $product->id)['id']);
            self::assertContains('prompt_template', $snapshot['columns']);
            self::assertContains('input_schema', $snapshot['columns']);
        } finally {
            File::delete($path);
        }
    }

    private function product(string $name, string $slug): Product
    {
        return Product::query()->create([
            'name_fa' => $name,
            'name_en' => $slug,
            'slug' => $slug,
            'category' => 'test',
            'thumbnail' => 'test.jpg',
            'primary_model' => 'test/backup-model',
            'prompt_template' => 'backup test prompt',
            'status' => 'active',
        ]);
    }
}
