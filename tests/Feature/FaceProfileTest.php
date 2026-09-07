<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\FaceProfile;
use App\Http\Controllers\VideoProductController;
use App\Services\ProductBuildSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

class FaceProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedInteger('tokens')->default(0);
            $table->unsignedInteger('tokens_purchased')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('promotional_tokens')->default(0);
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->timestamp('referral_attributed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('tokens')->default(0);
            $table->unsignedTinyInteger('face_profile_limit')->default(0);
            $table->string('status')->default('active');
            $table->string('model_tier_key')->default('free');
            $table->timestamps();
        });

        Schema::create('face_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->json('reference_images');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function test_professional_plan_allows_one_face_profile_and_stores_its_images(): void
    {
        Storage::fake('public');
        $plan = $this->plan('professional', 1, 'pro');
        $user = $this->user($plan);

        $this->actingAs($user)->post(route('profile.face-profiles.store'), [
            'name' => 'پروفایل اصلی',
            'images' => [UploadedFile::fake()->image('portrait.jpg', 900, 1200)],
        ])->assertRedirect(route('app.profile', ['tab' => 'files', 'file_tab' => 'face-profiles']));

        self::assertDatabaseCount('face_profiles', 1);
        $profile = $user->faceProfiles()->firstOrFail();
        self::assertSame('پروفایل اصلی', $profile->name);
        Storage::disk('public')->assertExists($profile->referenceImageEntries()[0]['path']);

        $this->actingAs($user)->post(route('profile.face-profiles.store'), [
            'name' => 'پروفایل دوم',
            'images' => [UploadedFile::fake()->image('portrait-2.jpg', 900, 1200)],
        ])->assertSessionHasErrors('face_profile');

        self::assertDatabaseCount('face_profiles', 1);
    }

    public function test_free_plan_cannot_create_a_face_profile(): void
    {
        Storage::fake('public');
        $user = $this->user($this->plan('gift', 0, 'free'));

        $this->actingAs($user)->post(route('profile.face-profiles.store'), [
            'name' => 'پروفایل اصلی',
            'images' => [UploadedFile::fake()->image('portrait.jpg', 900, 1200)],
        ])->assertSessionHasErrors('face_profile');

        self::assertDatabaseCount('face_profiles', 0);
    }

    public function test_face_profile_selection_makes_identity_image_upload_optional(): void
    {
        $product = new \App\Models\Product([
            'identity_preservation' => true,
            'subject_type' => 'face',
            'input_schema' => [[
                'field_id' => 'portrait',
                'type' => 'image_upload',
                'label_fa' => 'تصویر اصلی',
                'required' => '1',
                'max_size_mb' => 10,
            ]],
        ]);

        request()->merge(['face_profile_id' => 12]);
        $rules = app(ProductBuildSchema::class)->rules($product);

        self::assertSame('nullable', $rules['uploads.portrait'][0]);
    }

    public function test_video_product_can_resolve_an_owned_face_profile_without_a_new_upload(): void
    {
        Storage::fake('public');
        $user = $this->user($this->plan('video-pro', 1, 'pro'));
        Storage::disk('public')->put('face-profiles/video-person.jpg', 'test-image');
        $profile = FaceProfile::query()->create([
            'user_id' => $user->id,
            'name' => 'چهره ویدیو',
            'reference_images' => [[
                'path' => 'face-profiles/video-person.jpg',
                'mime' => 'image/jpeg',
                'size' => 10,
            ]],
            'status' => 'active',
        ]);
        $request = Request::create('/app/create/video/generate', 'POST', ['face_profile_id' => $profile->id]);
        $request->setUserResolver(fn () => $user);
        $method = new ReflectionMethod(app(VideoProductController::class), 'selectedFaceProfile');
        $method->setAccessible(true);

        $selected = $method->invoke(app(VideoProductController::class), $request, $user);

        self::assertSame($profile->id, $selected->id);
        self::assertSame('face-profiles/video-person.jpg', $selected->referenceImageEntries()[0]['path']);
    }

    private function plan(string $slug, int $limit, string $tier): Plan
    {
        return Plan::query()->create([
            'plan_code' => 'PLN-' . strtoupper($slug),
            'name' => $slug,
            'slug' => $slug,
            'face_profile_limit' => $limit,
            'model_tier_key' => $tier,
        ]);
    }

    private function user(Plan $plan): User
    {
        return User::query()->create([
            'name' => 'کاربر',
            'email' => $plan->slug . '@example.test',
            'password' => 'password',
            'plan_id' => $plan->id,
        ]);
    }
}
