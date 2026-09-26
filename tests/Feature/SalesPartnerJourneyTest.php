<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SalesPartnerActivity;
use App\Models\SalesPartnerLead;
use App\Models\SalesPartnerStage;
use App\Models\SalesPartnerTeamSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPartnerJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_profile_explains_the_user_journey_and_login_entry_point(): void
    {
        $this->get(route('profile'))
            ->assertOk()
            ->assertSee('مسیر تو در وطن')
            ->assertSee('مسیر اختصاصی‌ات بعد از ورود فعال می‌شود')
            ->assertSee('ورود و شروع مسیر');
    }

    public function test_authenticated_user_can_request_sales_partner_program_without_duplicate_leads(): void
    {
        $user = User::factory()->create([
            'name' => 'کاربر مسیر',
            'last_name' => 'آزمایشی',
            'status' => 'active',
            'login_count' => 2,
            'last_login_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile'))
            ->assertOk()
            ->assertSee('تعداد ورود')
            ->assertSee('لینک ساخته‌شده')
            ->assertSee('آماده همکاری فروش');

        $this->post(route('profile.partner-interest'))
            ->assertRedirect();

        $this->assertDatabaseHas('sales_partner_leads', [
            'user_id' => $user->id,
            'source' => 'user_profile',
            'stage' => 4,
            'priority' => 'high',
        ]);

        $this->post(route('profile.partner-interest'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, SalesPartnerLead::query()->where('user_id', $user->id)->count());
    }

    public function test_sales_team_page_reports_real_activity_and_saves_personal_target(): void
    {
        $admin = $this->createAdmin('team-report@example.test', 'مدیر گزارش تیم');
        $otherAdmin = $this->createAdmin('team-report-other@example.test', 'عضو دوم تیم');
        $lead = SalesPartnerLead::query()->create([
            'name' => 'پیج تست تیم',
            'handle' => '@team-report-test',
            'channel' => 'instagram',
            'stage' => 2,
            'status' => 'active',
            'priority' => 'normal',
            'assigned_to' => $admin->id,
            'next_follow_up_at' => now(),
        ]);
        SalesPartnerActivity::query()->create([
            'sales_partner_lead_id' => $lead->id,
            'admin_id' => $admin->id,
            'contact_type' => 'voice',
            'result' => 'positive',
            'contacted_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.marketing-technology.partners.team'))
            ->assertOk()
            ->assertSee('عملکرد تیم فروش')
            ->assertSee($admin->name)
            ->assertSee('پاسخ مثبت امروز')
            ->assertSee('پیگیری‌های عقب‌افتاده');

        $this->put(route('admin.marketing-technology.partners.team.settings.update', $admin), [
            'daily_contact_target' => 25,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('sales_partner_team_settings', [
            'admin_id' => $admin->id,
            'daily_contact_target' => 25,
            'is_active' => 1,
        ]);
        $this->assertTrue($otherAdmin->is_active);
    }

    public function test_daily_queue_prioritizes_my_due_cards_and_uses_my_daily_target(): void
    {
        $admin = $this->createAdmin('queue-owner@example.test', 'مسئول صف');
        $otherAdmin = $this->createAdmin('queue-other@example.test', 'مسئول دیگر');
        SalesPartnerTeamSetting::query()->create([
            'admin_id' => $admin->id,
            'daily_contact_target' => 3,
            'is_active' => true,
        ]);
        $mine = SalesPartnerLead::query()->create([
            'name' => 'کارت من',
            'handle' => '@queue-mine',
            'channel' => 'instagram',
            'stage' => 2,
            'status' => 'active',
            'priority' => 'high',
            'assigned_to' => $admin->id,
            'next_follow_up_at' => now()->subHour(),
        ]);
        SalesPartnerLead::query()->create([
            'name' => 'کارت همکار دیگر',
            'handle' => '@queue-other',
            'channel' => 'telegram',
            'stage' => 2,
            'status' => 'active',
            'priority' => 'normal',
            'assigned_to' => $otherAdmin->id,
            'next_follow_up_at' => now(),
        ]);
        SalesPartnerActivity::query()->create([
            'sales_partner_lead_id' => $mine->id,
            'admin_id' => $admin->id,
            'contact_type' => 'voice',
            'result' => 'no_response',
            'contacted_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.marketing-technology.partners.queue'))
            ->assertOk()
            ->assertSee('صف عملیاتی امروز')
            ->assertSee('کارت من')
            ->assertSee('هدف امروز '.$admin->name)
            ->assertSee('از 3')
            ->assertSee('https://instagram.com/queue-mine')
            ->assertDontSee('کارت همکار دیگر');

        $this->get(route('admin.marketing-technology.partners.queue', ['scope' => 'all', 'stage' => 2]))
            ->assertOk()
            ->assertSee('کارت همکار دیگر')
            ->assertSee('https://t.me/queue-other');
    }

    public function test_contact_result_moves_the_next_action_into_the_activity_log(): void
    {
        $admin = $this->createAdmin('queue-contact@example.test', 'ثبت‌کننده تماس');
        $lead = SalesPartnerLead::query()->create([
            'name' => 'کارت ثبت تماس',
            'handle' => '@queue-contact',
            'channel' => 'instagram',
            'stage' => 2,
            'status' => 'active',
            'priority' => 'normal',
            'assigned_to' => $admin->id,
            'next_follow_up_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.marketing-technology.partners.contacted', $lead), [
                'contact_type' => 'voice',
                'result' => 'positive',
                'note' => 'پاسخ مثبت برای دریافت توضیحات',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $lead->refresh();
        $this->assertSame(1, $lead->contact_count);
        $this->assertSame(1, $lead->positive_reply_count);
        $this->assertNotNull($lead->next_follow_up_at);
        $this->assertDatabaseHas('sales_partner_activities', [
            'sales_partner_lead_id' => $lead->id,
            'admin_id' => $admin->id,
            'result' => 'positive',
            'note' => 'پاسخ مثبت برای دریافت توضیحات',
        ]);
    }

    public function test_admin_can_register_a_manual_lead_and_move_it_through_the_board(): void
    {
        $admin = $this->createAdmin('manual-lead@example.test', 'مدیر دستی');
        $otherAdmin = $this->createAdmin('manual-other@example.test', 'عضو دستی');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.marketing-technology.partners.store'), [
                'name' => 'همکار دستی',
                'handle' => 'manual-partner',
                'channel' => 'other',
                'acquisition_source' => 'google',
                'stage' => 1,
                'priority' => 'high',
                'notes' => 'پایلوت دستی',
            ])
            ->assertRedirect();

        $lead = SalesPartnerLead::query()->where('handle', 'manual-partner')->firstOrFail();
        $this->assertSame('other', $lead->channel);
        $this->assertSame('google', $lead->acquisition_source);
        $this->assertNull($lead->contact_url);
        $this->assertNotNull($lead->next_follow_up_at);

        $this->get(route('admin.marketing-technology.partners.queue', ['scope' => 'all']))
            ->assertOk()
            ->assertSee('ارتباط دستی');

        $this->patch(route('admin.marketing-technology.partners.stage', $lead), [
            'stage' => 2,
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame(2, $lead->stage);
        $this->assertNotNull($lead->next_follow_up_at);

        $this->patch(route('admin.marketing-technology.partners.assignee', $lead), [
            'assigned_to' => $otherAdmin->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('sales_partner_leads', [
            'id' => $lead->id,
            'assigned_to' => $otherAdmin->id,
        ]);
    }

    public function test_stage_playbook_can_be_updated_for_the_manual_pilot(): void
    {
        $admin = $this->createAdmin('manual-settings@example.test', 'مدیر تنظیمات دستی');
        $stage = SalesPartnerStage::query()->where('stage', 2)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->put(route('admin.marketing-technology.partners.settings.update', $stage), [
                'title' => 'پیام اول پایلوت',
                'description' => $stage->description,
                'goal' => $stage->goal,
                'task' => $stage->task,
                'script' => 'اسکریپت تست دستی',
                'follow_up' => $stage->follow_up,
                'default_follow_up_hours' => $stage->default_follow_up_hours,
                'max_follow_ups' => $stage->max_follow_ups,
                'message_type' => 'both',
                'advance_when' => $stage->advance_when,
                'stop_when' => $stage->stop_when,
                'is_active' => 1,
            ])->assertRedirect();

        $this->assertDatabaseHas('sales_partner_stages', [
            'id' => $stage->id,
            'title' => 'پیام اول پایلوت',
            'script' => 'اسکریپت تست دستی',
        ]);
    }

    private function createAdmin(string $email, string $name): Admin
    {
        return Admin::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'test-password',
            'role' => 'leader',
            'is_active' => true,
        ]);
    }
}
