<?php

namespace Tests\Feature;

use App\Models\FaceProfile;
use App\Models\GeneratedImage;
use App\Models\User;
use App\Models\UserGalleryItem;
use App\Models\UserUpload;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileFileDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('generated_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('image_path');
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('user_uploads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('file_path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime_type')->nullable();
            $table->string('status')->default('stored');
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('face_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('name');
            $table->json('reference_images');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('user_gallery_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('source_type');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('original_path');
            $table->string('preview_path')->nullable();
            $table->string('disk')->default('user_gallery');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function test_owner_can_delete_generated_output_and_input_file(): void
    {
        Storage::fake('public');
        $user = $this->user('owner@example.test');

        Storage::disk('public')->put('generated/owner.webp', 'generated');
        Storage::disk('public')->put('uploads/owner.webp', 'input');
        $generated = GeneratedImage::query()->create([
            'user_id' => $user->id,
            'image_path' => 'generated/owner.webp',
            'size' => 7,
        ]);
        $upload = UserUpload::query()->create([
            'user_id' => $user->id,
            'file_path' => 'uploads/owner.webp',
            'size' => 5,
            'mime_type' => 'image/webp',
        ]);

        $this->actingAs($user)->delete(route('profile.generated-images.destroy', $generated))
            ->assertRedirect(route('app.profile', ['tab' => 'grid']))
            ->assertSessionHas('success');
        $this->actingAs($user)->delete(route('profile.uploads.destroy', $upload))
            ->assertRedirect(route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles']))
            ->assertSessionHas('success');

        self::assertDatabaseMissing('generated_images', ['id' => $generated->id]);
        self::assertDatabaseMissing('user_uploads', ['id' => $upload->id]);
        Storage::disk('public')->assertMissing('generated/owner.webp');
        Storage::disk('public')->assertMissing('uploads/owner.webp');
    }

    public function test_user_cannot_delete_another_users_output(): void
    {
        Storage::fake('public');
        $owner = $this->user('owner@example.test');
        $other = $this->user('other@example.test');
        Storage::disk('public')->put('generated/other.webp', 'generated');
        $generated = GeneratedImage::query()->create([
            'user_id' => $other->id,
            'image_path' => 'generated/other.webp',
            'size' => 7,
        ]);

        $this->actingAs($owner)->delete(route('profile.generated-images.destroy', $generated))
            ->assertNotFound();

        self::assertDatabaseHas('generated_images', ['id' => $generated->id]);
        Storage::disk('public')->assertExists('generated/other.webp');
    }

    public function test_deleting_face_profile_removes_references_but_not_user_gallery_item(): void
    {
        Storage::fake('public');
        Storage::fake('user_gallery');
        $user = $this->user('owner@example.test');
        Storage::disk('public')->put('face-profiles/one.jpg', 'one');
        Storage::disk('public')->put('face-profiles/two.jpg', 'two');
        Storage::disk('user_gallery')->put('users/1/original/gallery.jpg', 'gallery');

        $profile = FaceProfile::query()->create([
            'user_id' => $user->id,
            'name' => 'پروفایل اصلی',
            'reference_images' => [
                ['path' => 'face-profiles/one.jpg', 'size' => 3],
                ['path' => 'face-profiles/two.jpg', 'size' => 4],
            ],
            'status' => 'active',
        ]);
        $galleryItem = UserGalleryItem::query()->create([
            'user_id' => $user->id,
            'source_type' => 'input_image',
            'source_id' => $profile->id,
            'original_path' => 'users/1/original/gallery.jpg',
            'disk' => 'user_gallery',
            'mime_type' => 'image/jpeg',
            'size' => 100,
        ]);

        $this->actingAs($user)->delete(route('profile.face-profiles.destroy', $profile))
            ->assertRedirect(route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles']))
            ->assertSessionHas('success');

        self::assertDatabaseMissing('face_profiles', ['id' => $profile->id]);
        self::assertDatabaseHas('user_gallery_items', ['id' => $galleryItem->id]);
        Storage::disk('public')->assertMissing('face-profiles/one.jpg');
        Storage::disk('public')->assertMissing('face-profiles/two.jpg');
        Storage::disk('user_gallery')->assertExists('users/1/original/gallery.jpg');
    }

    private function user(string $email): User
    {
        return User::query()->create([
            'name' => 'کاربر',
            'email' => $email,
            'password' => 'password',
        ]);
    }
}
