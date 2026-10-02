<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\KnowledgeSource;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\Task;
use App\Models\SmartInstagram\Deal;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** همه‌ی صفحات «اینستاگرام هوشمند» با داده‌ی خالی و پر رندر شوند و منوی قدیمی آسیب نبیند. */
class SmartInstagramPagesTest extends TestCase
{
    use RefreshDatabase;

    private function leader(): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر اینستاگرام', 'email' => 'si-leader@example.test', 'password' => 'password', 'role' => 'leader', 'is_active' => true,
        ]);
    }

    private function seedData(): Conversation
    {
        $ws = app(WorkspaceContext::class)->id();
        $channel = Channel::query()->create(['workspace_id' => $ws, 'gateway' => 'sandbox', 'name' => 'کانال تست', 'status' => 'connected']);
        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'u1', 'username' => 'shop_test', 'display_name' => 'فروشگاه تست', 'first_source' => 'comment', 'lead_status' => 'hot', 'lead_score' => 80, 'last_interaction_at' => now()]);
        $conversation = Conversation::query()->create(['workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id, 'status' => 'unanswered', 'last_message_at' => now(), 'last_inbound_at' => now(), 'last_message_preview' => 'قیمت؟', 'intent' => 'price', 'needs_human' => true]);
        Message::query()->create(['workspace_id' => $ws, 'conversation_id' => $conversation->id, 'external_id' => 'c_1', 'direction' => 'in', 'source_type' => 'comment', 'source_ref' => 'media_1', 'body' => 'قیمت؟', 'meta' => ['comment_id' => '1'], 'occurred_at' => now()]);
        Deal::query()->create(['workspace_id' => $ws, 'contact_id' => $contact->id, 'title' => 'فرصت تست', 'stage' => 'won', 'outcome' => 'won', 'value_toman' => 2500000, 'source_type' => 'comment', 'source_ref' => 'media_1', 'closed_at' => now()]);
        Task::query()->create(['workspace_id' => $ws, 'contact_id' => $contact->id, 'title' => 'پیگیری', 'due_at' => now()->subHour()]);
        AutomationRule::query()->create(['workspace_id' => $ws, 'name' => 'قانون تست', 'trigger' => 'comment_keyword', 'keywords' => ['قیمت'], 'actions' => [['type' => 'add_tag', 'tag' => 'x']], 'status' => 'test']);
        KnowledgeSource::query()->create(['workspace_id' => $ws, 'title' => 'دانش تست', 'category' => 'faq', 'content' => 'متن', 'status' => 'approved']);

        return $conversation;
    }

    public function test_pages_require_admin_login(): void
    {
        $this->get(route('admin.smart-instagram.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_all_pages_render_empty_and_with_data(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $routes = [
            'admin.smart-instagram.dashboard' => 'داشبورد اینستاگرام',
            'admin.smart-instagram.inbox' => 'صندوق گفتگو',
            'admin.smart-instagram.contacts.index' => 'مشتریان و لیدها',
            'admin.smart-instagram.pipeline' => 'قیف فروش و وظایف',
            'admin.smart-instagram.automations.index' => 'اتومیشن‌ها',
            'admin.smart-instagram.automations.create' => 'قانون اتومیشن تازه',
            'admin.smart-instagram.knowledge.index' => 'دانش هوش مصنوعی',
            'admin.smart-instagram.content' => 'محتوا و فراخوان‌ها',
            'admin.smart-instagram.reports' => 'گزارش‌ها',
            'admin.smart-instagram.connections' => 'اتصال‌ها و تیم',
            'admin.smart-instagram.health' => 'سلامت و لاگ‌ها',
        ];

        foreach ($routes as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title);
        }

        $conversation = $this->seedData();
        \Illuminate\Support\Facades\Cache::flush();

        foreach ($routes as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title);
        }
        foreach (['profile', 'sources', 'playground', 'learning'] as $tab) {
            $this->get(route('admin.smart-instagram.knowledge.index', ['tab' => $tab]))->assertOk();
        }
        foreach (['overview', 'events', 'outbound', 'logs'] as $tab) {
            $this->get(route('admin.smart-instagram.health', ['tab' => $tab]))->assertOk();
        }
        $this->get(route('admin.smart-instagram.pipeline', ['tab' => 'tasks']))->assertOk()->assertSee('پیگیری');
        $this->get(route('admin.smart-instagram.contacts.index', ['view' => 'cards']))->assertOk()->assertSee('فروشگاه تست');
        $this->get(route('admin.smart-instagram.inbox.show', $conversation))->assertOk()->assertSee('فروشگاه تست')->assertSee('قیمت؟')->assertSee('پاسخ خصوصی به کامنت');
        $this->get(route('admin.smart-instagram.contacts.show', $conversation->contact_id))->assertOk()->assertSee('تایم‌لاین تعامل');
        $rule = AutomationRule::query()->first();
        $this->get(route('admin.smart-instagram.automations.show', $rule))->assertOk()->assertSee('آزمون سریع');
        $this->get(route('admin.smart-instagram.automations.edit', $rule))->assertOk();
        $this->get(route('admin.smart-instagram.knowledge.sources.show', KnowledgeSource::query()->first()))->assertOk();
        $this->get(route('admin.smart-instagram.dashboard'))->assertSee('۲٫۵ میلیون');
        $this->getJson(route('admin.smart-instagram.inbox.poll'))->assertOk()->assertJsonStructure(['latest', 'last_message_id', 'unanswered']);
    }

    public function test_cached_dashboard_and_reports_survive_real_cache_store(): void
    {
        // Laravel اجازه‌ی unserialize شیء را نمی‌دهد (cache.serializable_classes=false)؛ کش باید فقط داده‌ی ساده باشد.
        config(['cache.default' => 'file']);
        \Illuminate\Support\Facades\Cache::flush();
        $this->actingAs($this->leader(), 'admin');
        $this->seedData();

        foreach ([1, 2] as $pass) {
            $this->get(route('admin.smart-instagram.dashboard'))->assertOk();
            $this->get(route('admin.smart-instagram.reports'))->assertOk()->assertSee('قانون تست');
        }
        \Illuminate\Support\Facades\Cache::flush();
    }

    public function test_sidebar_has_new_menu_under_studio_and_keeps_old_item(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $html = view('admin.partials.sidebar')->render();

        $this->assertSame(1, substr_count($html, 'id="studio-smart-instagram-submenu"'));
        $this->assertStringContainsString('اینستاگرام — کامنت هوشمند', $html);
        $studio = strpos($html, 'id="studio-submenu-new"');
        $sales = strpos($html, 'id="sales-marketing-submenu-new"');
        $smart = strpos($html, 'id="studio-smart-instagram-submenu"');
        $this->assertTrue($studio < $smart && $smart < $sales, 'منوی اینستاگرام هوشمند باید داخل استودیو تولید باشد.');
        foreach (['داشبورد اینستاگرام', 'صندوق گفتگو', 'مشتریان و لیدها', 'قیف فروش و وظایف', 'اتومیشن‌ها', 'دانش هوش مصنوعی', 'محتوا و فراخوان‌ها', 'گزارش‌ها', 'اتصال‌ها و تیم', 'سلامت و لاگ‌ها'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_old_instagram_route_still_works(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $this->get('/admin/instagram')->assertRedirect('/admin/dashboard/instagram');
    }
}
