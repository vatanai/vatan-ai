<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\CustomerJourney;
use App\Models\CustomerJourneyTask;
use App\Models\GeneratedImage;
use App\Models\User;
use App\Services\CustomerJourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_behavior_engine_moves_a_user_from_first_visit_to_purchase_ready(): void
    {
        $user = User::factory()->create(['tokens' => 30, 'promotional_tokens' => 30, 'tokens_used' => 0]);
        $service = app(CustomerJourneyService::class);

        $journey = $service->sync($user, 'تست ثبت‌نام');
        $this->assertSame(1, $journey->point);
        $this->assertSame('new', $journey->substage);

        GeneratedImage::query()->create(['user_id' => $user->id, 'image_path' => 'testing/one.jpg']);
        $journey = $service->sync($user->fresh(), 'تست اولین خروجی');
        $this->assertSame(2, $journey->point);
        $this->assertSame('first_output', $journey->substage);

        GeneratedImage::query()->create(['user_id' => $user->id, 'image_path' => 'testing/two.jpg']);
        $user->update(['tokens' => 12, 'promotional_tokens' => 0, 'tokens_used' => 18]);
        $journey = $service->sync($user->fresh(), 'تست مصرف اعتبار');
        $this->assertSame(3, $journey->point);
        $this->assertSame('second_output', $journey->substage);
        $this->assertSame(3, $journey->events()->count());
        $this->assertSame(1, $journey->tasks()->where('status', 'pending')->count());
    }

    public function test_customer_journey_dashboard_supports_human_review_and_manual_transfer(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر مسیر کاربران',
            'email' => 'customer-journey@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['name' => 'کاربر آزمایشی مسیر', 'tokens' => 0]);
        $journey = app(CustomerJourneyService::class)->sync($user, 'تست داشبورد');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.marketing-technology.customer-journey', ['sync' => 0]))
            ->assertOk()
            ->assertSee('مسیر کاربران')
            ->assertSee('کاربر آزمایشی مسیر')
            ->assertSee('گام 1');

        $this->post(route('admin.marketing-technology.customer-journey.review', $journey), [
            'reason' => 'نیاز به تماس انسانی',
        ])->assertRedirect();
        $journey->refresh();
        $this->assertSame(CustomerJourneyService::HUMAN_REVIEW, $journey->status);

        $task = CustomerJourneyTask::query()->where('customer_journey_id', $journey->id)->where('status', 'pending')->firstOrFail();
        $this->post(route('admin.marketing-technology.customer-journey.tasks.complete', $task))
            ->assertRedirect();
        $journey->refresh();
        $this->assertSame(CustomerJourneyService::ACTIVE, $journey->status);
        $this->assertDatabaseHas('customer_journey_tasks', ['id' => $task->id, 'status' => 'completed']);

        $this->patch(route('admin.marketing-technology.customer-journey.point', $journey), [
            'point' => 4,
            'reason' => 'کاربر برای خرید آماده است',
        ])->assertRedirect();
        $this->assertDatabaseHas('customer_journeys', ['id' => $journey->id, 'point' => 4, 'status' => 'active']);
    }

    public function test_customer_journey_dashboard_paginates_users_in_twenty_card_pages(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر صفحه‌بندی مسیر',
            'email' => 'customer-journey-pagination@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $service = app(CustomerJourneyService::class);
        User::factory()->count(21)->create()->each(fn (User $user) => $service->sync($user, 'تست صفحه‌بندی'));

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.marketing-technology.customer-journey', ['sync' => 0]));

        $response->assertOk()
            ->assertSee('۲۰ کارت در ۵ ستون و ۴ ردیف')
            ->assertViewHas('journeys', fn ($journeys): bool => $journeys->perPage() === 20 && $journeys->total() === 21 && $journeys->lastPage() === 2);
    }

    public function test_human_review_is_not_overwritten_by_behavior_sync(): void
    {
        $user = User::factory()->create(['tokens' => 0]);
        $service = app(CustomerJourneyService::class);
        $journey = $service->sync($user, 'تست حفاظت از بررسی انسانی');

        $service->sendToHumanReview($journey, 'نیاز به بررسی تیم فروش');
        $user->update(['tokens' => 200]);

        $synced = $service->sync($user->fresh(), 'همگام‌سازی پس از تغییر رفتار');

        $this->assertSame(CustomerJourneyService::HUMAN_REVIEW, $synced->status);
        $this->assertSame(1, $synced->point);
        $this->assertSame('نیاز به بررسی تیم فروش', $synced->last_reason);
    }

    public function test_customer_journey_settings_are_editable_without_enabling_external_sending(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تنظیمات مسیر',
            'email' => 'customer-journey-settings@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.marketing-technology.customer-journey.settings.update'), [
                'auto_credit_percent' => 40,
                'station_size' => 100,
                'final_credit_threshold' => 80,
                'inactivity_days' => 10,
                'max_daily_tasks' => 2,
            ])->assertRedirect();

        $this->assertDatabaseHas('customer_journey_settings', ['key' => 'station_size', 'value' => '100']);
        $this->assertDatabaseHas('customer_journey_settings', ['key' => 'inactivity_days', 'value' => '10']);
    }

    public function test_successful_login_places_every_user_in_the_customer_journey(): void
    {
        $user = User::factory()->create([
            'phone' => '09120001122',
            'password' => Hash::make('test-password'),
            'status' => 'active',
            'tokens' => 0,
        ]);

        $this->postJson('/auth/login-submit', [
            'phone' => $user->phone,
            'password' => 'test-password',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('customer_journeys', [
            'user_id' => $user->id,
            'point' => 1,
            'status' => CustomerJourneyService::ACTIVE,
        ]);
    }

    public function test_customer_journey_stage_actions_are_editable(): void
    {
        $admin = Admin::query()->create([
            'name' => 'مدیر تنظیمات گام‌ها',
            'email' => 'customer-journey-stages@example.test',
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.marketing-technology.customer-journey.stages.update', 5), [
                'short' => 'آماده خرید',
                'title' => 'آماده تصمیم خرید',
                'description' => 'کاربر ارزش محصول را دیده است.',
                'task_title' => 'تماس پیشنهاد خرید',
                'task_body' => 'مانع خرید را از کاربر بپرس.',
                'channel' => 'call',
                'message_template' => 'برای ادامه استفاده آماده‌ای؟',
                'delay_minutes' => 90,
                'human_required' => 1,
                'advance_rule' => 'علاقه‌مندی ثبت شود.',
                'stop_rule' => 'پاسخ منفی قطعی.',
                'enabled' => 1,
            ])->assertRedirect();

        $this->assertDatabaseHas('customer_journey_stages', [
            'point' => 5,
            'title' => 'آماده تصمیم خرید',
            'channel' => 'call',
            'delay_minutes' => 90,
            'human_required' => 1,
        ]);
    }
}
