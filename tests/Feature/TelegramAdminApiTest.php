<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\TelegramUser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TelegramAdminApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admins', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->nullable(); $table->string('phone')->nullable();
            $table->string('role')->default('admin'); $table->boolean('is_active')->default(true); $table->string('password')->nullable();
            $table->string('password_reveal')->nullable(); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name')->nullable(); $table->string('last_name')->nullable(); $table->string('referral_code')->nullable(); $table->unsignedInteger('tokens')->default(0); $table->timestamps();
        });
        Schema::create('telegram_users', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('telegram_id')->unique(); $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('username')->nullable(); $table->string('first_name')->nullable(); $table->string('last_name')->nullable(); $table->string('language_code')->nullable();
            $table->boolean('is_premium')->default(false); $table->boolean('is_blocked')->default(false); $table->string('registration_state')->default('idle');
            $table->timestamp('started_at')->nullable(); $table->timestamp('last_active_at')->nullable(); $table->timestamp('registration_completed_at')->nullable();
            $table->json('registration_payload')->nullable(); $table->json('metadata')->nullable(); $table->text('phone')->nullable(); $table->timestamp('phone_verified_at')->nullable(); $table->timestamp('blocked_at')->nullable(); $table->timestamps();
        });
        Schema::create('telegram_product_clicks', function (Blueprint $table): void {
            $table->id(); $table->uuid('launch_token')->unique(); $table->unsignedBigInteger('telegram_user_id'); $table->unsignedBigInteger('product_id')->nullable();
            $table->string('source')->nullable(); $table->timestamp('clicked_at')->nullable(); $table->timestamps();
        });
        Schema::create('telegram_campaigns', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('created_by')->nullable(); $table->string('name'); $table->json('segment_definition')->nullable();
            $table->string('status')->default('draft'); $table->text('body')->nullable(); $table->string('media_type')->nullable(); $table->text('media_file_id')->nullable(); $table->json('buttons')->nullable();
            $table->timestamp('scheduled_at')->nullable(); $table->unsignedInteger('recipient_count')->default(0); $table->unsignedInteger('sent_count')->default(0); $table->unsignedInteger('failed_count')->default(0); $table->timestamps();
        });
        Schema::create('telegram_campaign_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('campaign_id'); $table->unsignedBigInteger('telegram_user_id'); $table->timestamp('sent_at')->nullable();
            $table->string('delivery_status')->default('pending'); $table->timestamps();
        });
        Schema::create('telegram_segments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('created_by')->nullable(); $table->string('name');
            $table->json('definition'); $table->unsignedInteger('user_count')->default(0); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('telegram_segments');
        Schema::dropIfExists('telegram_campaign_logs');
        Schema::dropIfExists('telegram_campaigns');
        Schema::dropIfExists('telegram_product_clicks');
        Schema::dropIfExists('telegram_users');
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        parent::tearDown();
    }

    public function test_telegram_api_requires_admin_authentication(): void
    {
        $this->getJson('/api/telegram-users')->assertUnauthorized();
    }

    public function test_admin_can_filter_telegram_users_without_exposing_private_phone_data(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر تست', 'email' => 'telegram-api@example.test', 'role' => 'leader', 'is_active' => true]);
        $siteUser = \App\Models\User::query()->create(['name' => 'کاربر', 'last_name' => 'متصل', 'tokens' => 40]);
        $telegramUser = TelegramUser::query()->create(['telegram_id' => 901001, 'username' => 'real_user', 'first_name' => 'کاربر', 'user_id' => $siteUser->id, 'phone' => '09120000000']);
        $telegramUser->productClicks()->create(['launch_token' => (string) \Illuminate\Support\Str::uuid(), 'source' => 'channel_post', 'clicked_at' => now()]);
        TelegramUser::query()->create(['telegram_id' => 901002, 'username' => 'other_user', 'first_name' => 'دیگر']);

        $response = $this->actingAs($admin, 'admin')->getJson('/api/telegram-users?linked=yes&source=channel_post');

        $response->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.telegram_id', 901001);
        $response->assertJsonMissing(['phone' => '09120000000']);
    }

    public function test_admin_user_upsert_is_idempotent(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر تست', 'email' => 'telegram-upsert@example.test', 'role' => 'leader', 'is_active' => true]);

        $first = $this->actingAs($admin, 'admin')->postJson('/api/telegram-users', [
            'telegram_id' => 901003,
            'username' => 'first_name_user',
            'first_name' => 'اول',
        ]);
        $second = $this->actingAs($admin, 'admin')->postJson('/api/telegram-users', [
            'telegram_id' => 901003,
            'username' => 'updated_user',
            'first_name' => 'به‌روزشده',
        ]);

        $first->assertCreated()->assertJsonPath('created', true);
        $second->assertOk()->assertJsonPath('created', false)->assertJsonPath('data.username', 'updated_user');
        $this->assertSame(1, TelegramUser::query()->where('telegram_id', 901003)->count());
    }

    public function test_admin_can_create_and_list_draft_campaigns_without_sending(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر کمپین', 'email' => 'campaign-api@example.test', 'role' => 'leader', 'is_active' => true]);

        $created = $this->actingAs($admin, 'admin')->postJson('/api/campaigns', [
            'name' => 'کاربران هفته اخیر',
            'segment_definition' => ['active_days' => 7, 'linked' => true],
            'body' => 'پیام آزمایشی',
        ]);

        $created->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.segment_definition.active_days', 7);
        $this->assertDatabaseHas('telegram_campaigns', ['name' => 'کاربران هفته اخیر', 'status' => 'draft']);

        $this->actingAs($admin, 'admin')->getJson('/api/campaigns?status=draft')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.status', 'draft');
    }

    public function test_admin_can_save_segment_from_filters_and_count_matching_users(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر سگمنت', 'email' => 'telegram-segment@example.test', 'role' => 'leader', 'is_active' => true]);
        TelegramUser::query()->create([
            'telegram_id' => 901004,
            'first_name' => 'فعال',
            'last_active_at' => now()->subDays(2),
            'is_blocked' => false,
        ]);
        TelegramUser::query()->create([
            'telegram_id' => 901005,
            'first_name' => 'قدیمی',
            'last_active_at' => now()->subDays(12),
            'is_blocked' => false,
        ]);
        TelegramUser::query()->create([
            'telegram_id' => 901006,
            'first_name' => 'مسدود',
            'last_active_at' => now()->subDays(2),
            'is_blocked' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->post('/admin/telegram/segments', [
            'name' => 'کاربران فعال هفت روز اخیر',
            'definition' => json_encode(['active_days' => 7], JSON_UNESCAPED_UNICODE),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('telegram_segments', [
            'name' => 'کاربران فعال هفت روز اخیر',
            'user_count' => 1,
        ]);
    }

    public function test_segment_definition_rejects_invalid_values_and_does_not_create_a_segment(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر اعتبارسنجی', 'email' => 'telegram-segment-validation@example.test', 'role' => 'leader', 'is_active' => true]);

        $this->actingAs($admin, 'admin')->from('/admin/telegram/users')->post('/admin/telegram/segments', [
            'name' => 'سگمنت نامعتبر',
            'definition' => json_encode(['active_days' => 0], JSON_UNESCAPED_UNICODE),
        ])->assertRedirect('/admin/telegram/users');

        $this->assertDatabaseMissing('telegram_segments', ['name' => 'سگمنت نامعتبر']);
    }

    public function test_admin_can_open_the_telegram_users_page_with_saved_segment_area(): void
    {
        $admin = Admin::query()->create(['name' => 'مدیر صفحه', 'email' => 'telegram-users-page@example.test', 'role' => 'leader', 'is_active' => true]);
        \App\Models\TelegramSegment::query()->create([
            'created_by' => $admin->id,
            'name' => 'سگمنت تست صفحه',
            'definition' => ['active_days' => 7],
            'user_count' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->get('/admin/telegram/users')
            ->assertOk()
            ->assertSee('سگمنت‌های ذخیره‌شده')
            ->assertSee('سگمنت تست صفحه');
    }
}
