<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\UserGalleryController;
use App\Models\GeneratedImage;
use App\Models\Order;
use App\Models\UserGalleryItem;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class UserGalleryMediaPresentationTest extends TestCase
{
    public function test_missing_private_snapshot_is_not_presented_as_a_valid_image(): void
    {
        Storage::fake('user_gallery');
        $item = new UserGalleryItem([
            'user_id' => 12,
            'source_type' => 'output_image',
            'original_path' => 'users/12/original/missing.png',
            'preview_path' => 'users/12/preview/missing.jpg',
            'thumbnail_path' => 'users/12/thumbnail/missing.webp',
            'disk' => 'user_gallery',
        ]);
        $item->id = 34;

        self::assertNull($this->invoke('galleryItemMediaUrl', [$item, 12, true]));
    }

    public function test_existing_thumbnail_and_generated_image_use_controlled_gallery_routes(): void
    {
        Storage::fake('user_gallery');
        Storage::fake('public');
        Storage::disk('user_gallery')->put('users/12/thumbnail/existing.webp', 'thumbnail');
        Storage::disk('public')->put('generated/existing.png', 'generated');

        $item = new UserGalleryItem([
            'user_id' => 12,
            'source_type' => 'output_image',
            'original_path' => 'users/12/original/existing.png',
            'thumbnail_path' => 'users/12/thumbnail/existing.webp',
            'disk' => 'user_gallery',
        ]);
        $item->id = 34;
        $image = new GeneratedImage(['user_id' => 12, 'image_path' => 'generated/existing.png']);
        $image->id = 56;

        self::assertSame(
            route('admin.users.gallery.thumbnail', [12, 34]),
            $this->invoke('galleryItemMediaUrl', [$item, 12, true]),
        );
        self::assertSame(
            route('admin.users.gallery.generated-image-thumbnail', 56),
            $this->invoke('generatedImageMediaUrl', [$image]),
        );
    }

    public function test_missing_private_input_falls_back_only_to_an_existing_public_input(): void
    {
        Storage::fake('user_gallery');
        Storage::fake('public');
        Storage::disk('public')->put('uploads/personal/available.jpg', 'input');

        $order = new Order([
            'user_id' => 12,
            'input_payload' => ['source_upload_path' => 'uploads/personal/available.jpg'],
        ]);
        $order->id = 78;
        $missingPrivateItem = new UserGalleryItem([
            'user_id' => 12,
            'source_type' => 'input_image',
            'original_path' => 'users/12/original/missing.jpg',
            'disk' => 'user_gallery',
            'mime_type' => 'image/jpeg',
        ]);
        $missingPrivateItem->id = 90;

        $media = $this->invoke('inputItemsForBuild', [$order, collect([$missingPrivateItem])]);

        self::assertCount(1, $media);
        self::assertSame(asset('storage/uploads/personal/available.jpg'), $media[0]['url']);
    }

    private function invoke(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod(UserGalleryController::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(app(UserGalleryController::class), $arguments);
    }
}
