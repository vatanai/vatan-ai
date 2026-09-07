<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ActivityLog;
use App\Models\GeneratedImage;
use App\Models\Plan;
use App\Models\ReferralConversion;
use App\Models\ReferralVisit;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserPlanChangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('admins', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedInteger('tokens')->default(0);
            $table->string('status')->default('active');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('avatar')->nullable();
            $table->string('password');
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
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->timestamps();
        });

        Schema::create('generated_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('image_path');
            $table->text('user_prompt')->nullable();
            $table->decimal('cost', 10, 6)->default(0);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type');
            $table->string('message');
            $table->unsignedBigInteger('generation_id')->nullable();
            $table->unsignedBigInteger('prompt_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('level')->default('info');
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_visits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('inviter_id');
            $table->string('referral_code');
            $table->string('visitor_token');
            $table->string('landing_url')->nullable();
            $table->timestamp('visited_at');
            $table->unsignedBigInteger('converted_user_id')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_conversions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('visit_id')->nullable();
            $table->unsignedBigInteger('inviter_id');
            $table->unsignedBigInteger('invitee_id')->unique();
            $table->string('status')->default('qualified');
            $table->timestamps();
        });
    }

    public function test_leader_can_change_user_plan_without_changing_credits(): void
    {
        $leader = $this->admin('leader');
        $plan = Plan::query()->create([
            'plan_code' => 'PLN-PRO',
            'name' => 'حرفه‌ای',
            'slug' => 'professional',
            'price' => 485000,
            'tokens' => 515,
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'name' => 'کاربر',
            'email' => 'plan-user@example.test',
            'password' => 'password',
            'tokens' => 73,
        ]);

        $this->actingAs($leader, 'admin')
            ->patchJson(route('admin.users.plan', $user), ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('plan_id', $plan->id)
            ->assertJsonPath('plan_name', 'حرفه‌ای');

        $user->refresh();
        self::assertSame($plan->id, $user->plan_id);
        self::assertSame(73, $user->tokens);

        $this->actingAs($leader, 'admin')
            ->patchJson(route('admin.users.plan', $user), ['plan_id' => null])
            ->assertOk()
            ->assertJsonPath('plan_name', 'رایگان');

        $user->refresh();
        self::assertNull($user->plan_id);
        self::assertSame(73, $user->tokens);
    }

    public function test_non_leader_cannot_change_a_user_plan(): void
    {
        $admin = $this->admin('admin');
        $plan = Plan::query()->create([
            'plan_code' => 'PLN-ADV',
            'name' => 'پیشرفته',
            'slug' => 'advanced',
            'price' => 2290000,
            'tokens' => 2620,
            'status' => 'active',
        ]);
        $user = User::query()->create([
            'name' => 'کاربر',
            'email' => 'protected-user@example.test',
            'password' => 'password',
            'tokens' => 10,
        ]);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.users.plan', $user), ['plan_id' => $plan->id])
            ->assertForbidden();

        self::assertNull($user->fresh()->plan_id);
    }

    public function test_leader_can_change_plan_for_multiple_users_without_changing_credits(): void
    {
        $leader = $this->admin('leader');
        $plan = Plan::query()->create([
            'plan_code' => 'PLN-BULK',
            'name' => 'پیشرفته',
            'slug' => 'advanced-bulk',
            'price' => 2290000,
            'tokens' => 2620,
            'status' => 'active',
        ]);
        $first = User::query()->create([
            'name' => 'کاربر اول', 'email' => 'bulk-one@example.test', 'password' => 'password', 'tokens' => 41,
        ]);
        $second = User::query()->create([
            'name' => 'کاربر دوم', 'email' => 'bulk-two@example.test', 'password' => 'password', 'tokens' => 87,
        ]);

        $this->actingAs($leader, 'admin')
            ->patchJson(route('admin.users.bulk-plan'), [
                'user_ids' => [$first->id, $second->id],
                'plan_id' => $plan->id,
            ])
            ->assertOk()
            ->assertJsonPath('updated', 2)
            ->assertJsonPath('plan_name', 'پیشرفته');

        $first->refresh();
        $second->refresh();
        self::assertSame($plan->id, $first->plan_id);
        self::assertSame($plan->id, $second->plan_id);
        self::assertSame(41, $first->tokens);
        self::assertSame(87, $second->tokens);

        $this->actingAs($leader, 'admin')
            ->patchJson(route('admin.users.bulk-plan'), [
                'user_ids' => [$first->id, $second->id],
                'plan_id' => null,
            ])
            ->assertOk()
            ->assertJsonPath('updated', 2)
            ->assertJsonPath('plan_id', null)
            ->assertJsonPath('plan_name', 'رایگان');

        $first->refresh();
        $second->refresh();
        self::assertNull($first->plan_id);
        self::assertNull($second->plan_id);
        self::assertSame(41, $first->tokens);
        self::assertSame(87, $second->tokens);
    }

    public function test_admin_can_change_status_for_one_or_multiple_users(): void
    {
        $admin = $this->admin('admin');
        $first = User::query()->create([
            'name' => 'کاربر اول', 'email' => 'status-one@example.test', 'password' => 'password', 'status' => 'active',
        ]);
        $second = User::query()->create([
            'name' => 'کاربر دوم', 'email' => 'status-two@example.test', 'password' => 'password', 'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.users.status', $first), ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('user_id', $first->id)
            ->assertJsonPath('user_status', 'suspended');

        self::assertSame('suspended', $first->fresh()->status);

        $this->actingAs($admin, 'admin')
            ->patchJson(route('admin.users.bulk-status'), [
                'user_ids' => [$first->id, $second->id],
                'status' => 'deleted',
            ])
            ->assertOk()
            ->assertJsonPath('updated', 2)
            ->assertJsonPath('user_status', 'deleted');

        self::assertSame('deleted', $first->fresh()->status);
        self::assertSame('deleted', $second->fresh()->status);
    }

    public function test_invitation_source_connects_the_inviter_and_the_referral_link(): void
    {
        $inviter = User::query()->create([
            'name' => 'دعوت‌کننده',
            'email' => 'inviter@example.test',
            'password' => 'password',
            'referral_code' => 'VATAN123',
        ]);
        $invitee = User::query()->create([
            'name' => 'دعوت‌شده',
            'email' => 'invitee@example.test',
            'password' => 'password',
            'referred_by' => $inviter->id,
        ]);
        $visit = ReferralVisit::query()->create([
            'inviter_id' => $inviter->id,
            'referral_code' => 'VATAN123',
            'visitor_token' => 'visitor-test-token',
            'landing_url' => 'http://vatan-ai.test/r/VATAN123',
            'visited_at' => now(),
            'converted_user_id' => $invitee->id,
            'converted_at' => now(),
        ]);
        ReferralConversion::query()->create([
            'visit_id' => $visit->id,
            'inviter_id' => $inviter->id,
            'invitee_id' => $invitee->id,
            'status' => 'qualified',
        ]);

        $invitee->load(['referrer', 'referralConversion.visit']);

        self::assertSame($inviter->id, $invitee->referrer?->id);
        self::assertSame('VATAN123', $invitee->referralConversion?->visit?->referral_code);
        self::assertSame('http://vatan-ai.test/r/VATAN123', $invitee->referralConversion?->visit?->landing_url);
    }

    public function test_user_admin_pages_render_real_statistics_and_server_side_search(): void
    {
        $admin = $this->admin('leader');
        $matchedUser = User::query()->create([
            'name' => 'کاربر یافتنی',
            'last_name' => 'تست',
            'email' => 'matched@example.test',
            'phone' => '09120000000',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now(),
            'registered_at' => now(),
        ]);
        User::query()->create([
            'name' => 'کاربر نامرتبط',
            'email' => 'other@example.test',
            'password' => 'password',
            'status' => 'active',
            'last_login_at' => now()->subMonths(2),
            'registered_at' => now()->subHour(),
        ]);
        GeneratedImage::query()->create([
            'user_id' => $matchedUser->id,
            'image_path' => 'generated/log-image.jpg',
            'cost' => 1.25,
        ]);
        ActivityLog::query()->create([
            'user_id' => $matchedUser->id,
            'type' => 'generate_success',
            'message' => 'تصویر با موفقیت ساخته شد.',
            'level' => 'success',
            'meta' => ['source' => 'test'],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index', ['q' => 'یافتنی']))
            ->assertOk()
            ->assertViewHas('monthlyActiveUsersCount', 1)
            ->assertViewHas('totalGeneratedImagesCount', 1)
            ->assertSee('کاربر یافتنی')
            ->assertDontSee('کاربر نامرتبط');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeInOrder(['کاربر یافتنی', 'کاربر نامرتبط']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.all_activities'))
            ->assertOk()
            ->assertSee('تصویر با موفقیت ساخته شد.');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.all_logs'))
            ->assertOk()
            ->assertSee('generated/log-image.jpg');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.logs', $matchedUser))
            ->assertOk()
            ->assertSee('تصویر با موفقیت ساخته شد.');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.users.tokens'))
            ->assertOk();
    }

    private function admin(string $role): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر تست',
            'email' => $role . '@example.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => true,
        ]);
    }
}
