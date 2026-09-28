<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\FaceProfile;
use App\Models\GeneratedImage;
use App\Models\Order;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\ServiceCreditTransactionReport;
use App\Services\UserGalleryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

/**
 * نمایش عکس‌های ورودی و خروجی ساخت‌ها در «گالری کاربران» و «ساخت و تراکنش‌ها»ی داشبورد.
 */
class AdminBuildMediaDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function admin(string $email): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر تست', 'email' => $email, 'password' => 'password',
            'role' => 'leader', 'is_active' => true,
        ]);
    }

    private function exchange(): ExchangeRateService
    {
        $exchange = Mockery::mock(ExchangeRateService::class);
        $exchange->shouldReceive('usdToIrr')->andReturn([
            'rate' => 600000, 'source' => 'تست', 'online' => false, 'at' => now(),
        ]);

        return $exchange;
    }

    public function test_output_snapshot_in_user_gallery_is_served_to_admin_instead_of_404(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');
        $user = User::query()->create(['name' => 'کاربر خروجی', 'phone' => '09120000101', 'status' => 'active']);
        Storage::disk('public')->put('generated/out.png', base64_decode(self::PNG));
        $image = GeneratedImage::query()->create(['user_id' => $user->id, 'image_path' => 'generated/out.png']);

        $item = app(UserGalleryService::class)->captureOutput(
            $user, 'output_image', (int) $image->id, 'generated/out.png', 'public', 68, 'image/png',
        );
        $this->assertNotNull($item);

        $admin = $this->admin('gallery-output@example.test');
        foreach (['thumbnail', 'preview', 'original'] as $route) {
            $this->actingAs($admin, 'admin')
                ->get(route('admin.users.gallery.' . $route, [$user->id, $item->id]))
                ->assertOk();
        }

        // حذف تکی از این مسیر همچنان فقط برای ورودی‌هاست.
        $this->actingAs($admin, 'admin')
            ->delete(route('admin.users.gallery.destroy', [$user->id, $item->id]))
            ->assertNotFound();
    }

    public function test_output_thumbnail_falls_back_to_original_when_preview_cannot_be_built(): void
    {
        Storage::fake('public');
        // فایلی که خوانا ولی قابل تبدیل به بندانگشتی نیست (مثلاً فرمت پشتیبانی‌نشده).
        Storage::disk('public')->put('generated/strange.png', 'not-a-real-image');
        $image = GeneratedImage::query()->create(['image_path' => 'generated/strange.png']);
        $admin = $this->admin('thumb-fallback@example.test');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.service-credits.image-thumbnail', $image))
            ->assertOk();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.gallery.generated-image-thumbnail', $image))
            ->assertOk();
    }

    public function test_remote_output_thumbnail_redirects_instead_of_404(): void
    {
        Storage::fake('public');
        $image = GeneratedImage::query()->create(['image_path' => 'https://cdn.example.test/out.png']);

        $this->actingAs($this->admin('thumb-remote@example.test'), 'admin')
            ->get(route('admin.service-credits.image-thumbnail', $image))
            ->assertRedirect('https://cdn.example.test/out.png');
    }

    public function test_ready_thumbnail_is_linked_as_a_static_file_in_the_report(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('generated/ready.png', base64_decode(self::PNG));
        $user = User::query()->create(['name' => 'کاربر', 'phone' => '09120000102', 'status' => 'active']);
        $order = Order::query()->create([
            'user_id' => $user->id, 'status' => 'completed', 'processing_status' => 'completed', 'completed_at' => now(),
        ]);
        $image = GeneratedImage::query()->create(['user_id' => $user->id, 'order_id' => $order->id, 'image_path' => 'generated/ready.png']);

        $report = new ServiceCreditTransactionReport($this->exchange());
        $row = collect($report->build(Request::create('/', 'GET', ['source' => 'user', 'per_page' => 100]))['transactions']->items())
            ->firstWhere('id', 'order-' . $order->id);
        $this->assertSame(route('admin.service-credits.image-thumbnail', $image), $row['output_preview_url']);

        app(\App\Services\ProfileMediaThumbnailService::class)->generate($image, 160);
        $row = collect($report->build(Request::create('/', 'GET', ['source' => 'user', 'per_page' => 100]))['transactions']->items())
            ->firstWhere('id', 'order-' . $order->id);
        $this->assertStringContainsString('/storage/profile-thumbnails/', $row['output_preview_url']);
    }

    public function test_face_profile_reference_images_are_shown_as_build_inputs(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');
        Storage::disk('public')->put('face-profiles/ref.png', base64_decode(self::PNG));
        $user = User::query()->create(['name' => 'کاربر کارکتر', 'phone' => '09120000103', 'status' => 'active']);
        $profile = FaceProfile::query()->create([
            'user_id' => $user->id, 'name' => 'سارا', 'status' => 'active',
            'reference_images' => [['path' => 'face-profiles/ref.png', 'mime' => 'image/png', 'size' => 68]],
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id, 'status' => 'completed', 'processing_status' => 'completed', 'completed_at' => now(),
            'input_payload' => ['face_profile_id' => $profile->id, 'resolved_prompt' => 'portrait', 'source_upload_paths' => []],
        ]);

        $row = collect((new ServiceCreditTransactionReport($this->exchange()))
            ->build(Request::create('/', 'GET', ['source' => 'user', 'per_page' => 100]))['transactions']->items())
            ->firstWhere('id', 'order-' . $order->id);

        $images = collect($row['input_media'])->where('type', 'image')->values();
        $this->assertCount(1, $images);
        $this->assertStringEndsWith('/storage/face-profiles/ref.png', $images[0]['preview_url']);
        $this->assertStringContainsString('سارا', $images[0]['label']);

        $this->actingAs($this->admin('face-inputs@example.test'), 'admin')
            ->get(route('admin.users.gallery.show', $user))
            ->assertOk()
            ->assertSee('/storage/face-profiles/ref.png', false);
    }
}
