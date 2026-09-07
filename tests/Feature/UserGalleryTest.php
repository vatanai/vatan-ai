<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\UserGallerySuggestion;
use App\Services\UserGalleryService;
use App\Services\UserGalleryGrowthService;
use App\Services\UserGalleryRecreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_consented_image_is_copied_to_private_gallery_with_preview_and_expiry(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');

        $user = User::query()->create([
            'name' => 'کاربر آزمایشی',
            'phone' => '09120000000',
            'status' => 'active',
        ]);
        Storage::disk('public')->put('uploads/test.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        $gallery = app(UserGalleryService::class);
        $gallery->syncConsent($user, true);
        $item = $gallery->capture($user, 'upload', null, 'uploads/test.png', 'public', 68, 'image/png');

        $this->assertNotNull($item);
        $this->assertTrue($item->expires_at->isFuture());
        Storage::disk('user_gallery')->assertExists($item->original_path);
        Storage::disk('user_gallery')->assertExists($item->preview_path);
        $this->assertDatabaseHas('user_gallery_events', ['action' => 'stored', 'user_id' => $user->id]);

        $gallery->deleteItem($item);
        Storage::disk('user_gallery')->assertMissing($item->original_path);
        Storage::disk('user_gallery')->assertMissing($item->preview_path);
        $this->assertDatabaseMissing('user_gallery_items', ['id' => $item->id]);
    }

    public function test_user_cannot_read_another_users_gallery_preview(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');
        $owner = User::query()->create([
            'name' => 'مالک',
            'phone' => '09120000001',
            'status' => 'active',
        ]);
        $otherUser = User::query()->create([
            'name' => 'کاربر دیگر',
            'phone' => '09120000002',
            'status' => 'active',
        ]);
        Storage::disk('public')->put('uploads/owner.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
        $gallery = app(UserGalleryService::class);
        $gallery->syncConsent($owner, true);
        $item = $gallery->capture($owner, 'upload', null, 'uploads/owner.png', 'public', 68, 'image/png');

        $this->actingAs($otherUser)->get(route('profile.gallery.preview', $item))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_suggestions_require_separate_consent_and_monthly_free_recreation_is_recorded(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');

        $user = User::query()->create([
            'name' => 'کاربر رشد',
            'phone' => '09120000003',
            'status' => 'active',
        ]);
        Storage::disk('public')->put('uploads/growth.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));
        $gallery = app(UserGalleryService::class);
        $gallery->syncConsent($user, true);
        $item = $gallery->capture($user, 'upload', null, 'uploads/growth.png', 'public', 68, 'image/png');
        $product = Product::query()->create([
            'name_fa' => 'محصول پیشنهاد',
            'name_en' => 'Gallery suggestion product',
            'slug' => 'gallery-suggestion-test',
            'category' => 'test',
            'thumbnail' => 'test.jpg',
            'primary_model' => 'test/gallery-model',
            'prompt_template' => 'gallery test prompt',
            'route_slug' => 'gallery-suggestion-test',
            'status' => 'active',
            'output_type' => 'image',
        ]);

        $growth = app(UserGalleryGrowthService::class);
        $growth->syncPreference($user, ['suggestions_enabled' => true, 'marketing_enabled' => true]);
        $this->assertSame(1, $growth->generateForUser($user->fresh()));
        $suggestion = UserGallerySuggestion::query()->firstOrFail();
        $this->assertTrue((bool) data_get($suggestion->preview_payload, 'watermarked'));
        $this->assertDatabaseHas('user_gallery_campaigns', ['user_id' => $user->id, 'campaign_type' => $suggestion->suggestion_type]);
        $this->assertDatabaseHas('user_gallery_notifications', ['user_id' => $user->id, 'channel' => 'in_app', 'consent_checked' => 1]);
        $this->assertDatabaseHas('user_gallery_cost_events', ['cost_type' => 'storage', 'user_gallery_item_id' => $item->id]);
        $this->assertDatabaseHas('user_gallery_cost_events', ['cost_type' => 'notification', 'user_id' => $user->id]);

        $recreation = app(UserGalleryRecreationService::class)->begin($user, $item, $product->id, 12);
        $this->assertSame('monthly_free', $recreation->pricing_mode);
        $this->assertSame(0, $recreation->charged_credit_cost);
        $this->assertSame(0, app(UserGalleryRecreationService::class)->freeRemaining($user->fresh()));
        app(UserGalleryRecreationService::class)->complete($recreation, 0);
        $this->assertDatabaseHas('user_gallery_cost_events', ['cost_type' => 'generation', 'user_gallery_recreation_id' => $recreation->id]);
    }
}
