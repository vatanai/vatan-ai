<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\MarketingIntegration;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\OutboundMessage;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\PostFlowSession;
use App\Models\SmartInstagram\Workspace;
use App\Models\SmartInstagram\WorkspaceMember;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** «ثبت پست»: صفحات، همگام‌سازی، کلمات چندگانه، شرط فالو، کارت چند‌دکمه‌ای و حالت آزمایشی. */
class SmartInstagramPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'smart_instagram.ingest.secret' => 'ingest-test-secret',
            'smart_instagram.ai.enabled' => false,
            'services.openrouter.api_key' => 'test-key',
        ]);
    }

    private function leader(string $email = 'posts@example.test', string $role = 'leader'): Admin
    {
        return Admin::query()->create(['name' => 'ادمین '.$email, 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true]);
    }

    private function ws(): int
    {
        return app(WorkspaceContext::class)->id();
    }

    private function sandbox(array $settings = [], bool $outbound = true): Channel
    {
        return Channel::query()->create([
            'workspace_id' => $this->ws(), 'gateway' => 'sandbox', 'name' => 'آزمایشی', 'username' => 'vatan.test',
            'external_account_id' => 'acct_1', 'status' => 'connected', 'outbound_enabled' => $outbound, 'settings' => $settings,
        ]);
    }

    private function makePost(?Channel $channel = null, string $mediaId = 'reel_1', string $status = 'verified'): Post
    {
        return Post::query()->create([
            'workspace_id' => $this->ws(), 'channel_id' => $channel?->id, 'media_id' => $mediaId, 'caption' => 'کیف چرم دست‌دوز — برای لینک «لینک» کامنت کن',
            'media_type' => 'VIDEO', 'product_type' => 'REELS', 'permalink' => 'https://www.instagram.com/reel/ABC123/', 'shortcode' => 'ABC123',
            'source' => 'sync', 'connection_status' => $status, 'published_at' => now()->subDay(),
        ]);
    }

    private function payload(Post $post, string $intent = 'active', array $overrides = []): array
    {
        $settings = app(PostCampaignService::class)->defaults();
        $settings['reply']['ai_personalize'] = false;
        $settings['card']['title'] = 'کیف چرم {name}';
        $settings['card']['buttons'] = [
            ['preset' => 'product', 'type' => 'web_url', 'label' => 'مشاهده محصول', 'url' => 'https://aivatan.com/p/bag'],
            ['preset' => 'site', 'type' => 'web_url', 'label' => 'رفتن به سایت', 'url' => 'https://aivatan.com'],
            ['preset' => 'info', 'type' => 'postback', 'label' => 'دریافت اطلاعات', 'reply_text' => 'ارسال رایگان دارد'],
        ];

        return array_replace_recursive([
            'post_id' => $post->id, 'title' => 'کیف چرم', 'intent' => $intent,
            'follow_required' => '1', 'public_reply_enabled' => '1', 'dm_enabled' => '1',
            'keywords' => [
                ['keyword' => 'لینک', 'match_mode' => 'contains', 'is_active' => '1'],
                ['keyword' => 'لينك', 'match_mode' => 'contains', 'is_active' => '1'], // ی/ک عربی — تکراری
                ['keyword' => 'قیمت*', 'match_mode' => 'pattern', 'is_active' => '1'],
                ['keyword' => '۱', 'match_mode' => 'exact', 'is_active' => '0'],
            ],
            'settings' => $settings,
        ], $overrides);
    }

    private function ingest(array $payload)
    {
        $raw = json_encode($payload);
        $ts = time();

        return $this->call('POST', '/webhooks/smart-instagram/ingest', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_VATAN_TIMESTAMP' => (string) $ts,
            'HTTP_X_VATAN_SIGNATURE' => 'sha256='.hash_hmac('sha256', $ts.'.'.$raw, 'ingest-test-secret'),
        ], $raw);
    }

    public function test_pages_render_empty_state_and_sidebar_link(): void
    {
        $this->actingAs($this->leader(), 'admin');

        $this->get(route('admin.smart-instagram.posts.index'))->assertOk()
            ->assertSee('هنوز پستی ثبت نشده است')->assertSee('ثبت پست جدید')
            ->assertSee(route('admin.smart-instagram.posts.index'), false);
        $this->get(route('admin.smart-instagram.posts.create'))->assertOk()
            ->assertSee('اجرا با هوش مصنوعی')->assertSee('فالو اجباری')->assertSee('data-sip-preview', false);
        $this->get(route('admin.smart-instagram.dashboard'))->assertOk()->assertSee('ثبت پست');
        $this->get(route('admin.smart-instagram.content'))->assertOk();
    }

    public function test_store_normalizes_keywords_compiles_rule_and_renders_cards(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost($this->sandbox());

        $this->post(route('admin.smart-instagram.posts.store'), $this->payload($post))->assertRedirect()->assertSessionHasNoErrors();

        $campaign = PostCampaign::query()->with('keywords', 'rule')->firstOrFail();
        $this->assertSame('active', $campaign->status);
        $this->assertCount(3, $campaign->keywords, 'لینک/لينك یکی شدند');
        $this->assertFalse((bool) $campaign->keywords->firstWhere('normalized', '1')->is_active);
        $rule = $campaign->rule;
        $this->assertSame('comment_keyword', $rule->trigger);
        $this->assertSame('reel_1', $rule->scope_ref);
        $this->assertNotContains('1', (array) $rule->keywords, 'کلمه‌ی غیرفعال اجرا نمی‌شود');
        $this->assertContains('post_flow', collect($rule->actions)->pluck('type')->all());
        $this->assertSame(1, $campaign->versions()->count());

        $this->get(route('admin.smart-instagram.posts.index'))->assertOk()
            ->assertSee('کیف چرم')->assertSee('دریافت نشده')->assertSee('فالو اجباری');
        $this->get(route('admin.smart-instagram.posts.show', $campaign))->assertOk()->assertSee('قیف دایرکت');
        $this->get(route('admin.smart-instagram.posts.edit', $campaign))->assertOk()->assertSee('ویرایش سناریوی پست');
        // ویرایش قانون لینک‌شده از صفحه‌ی اتومیشن به ثبت پست هدایت می‌شود
        $this->get(route('admin.smart-instagram.automations.edit', $rule))->assertRedirect(route('admin.smart-instagram.posts.edit', $campaign));

        // شبیه‌سازی بدون ارسال
        $this->postJson(route('admin.smart-instagram.posts.simulate', $campaign), ['text' => 'قیمتش چنده؟', 'follows' => 'no'])
            ->assertOk()->assertJson(['matched' => true]);
        $this->postJson(route('admin.smart-instagram.posts.simulate', $campaign), ['text' => 'عالیه'])->assertJson(['matched' => false]);
        $this->assertSame(0, OutboundMessage::query()->count());
    }

    public function test_follow_gate_flow_delivers_multi_button_card_after_follow(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $channel = $this->sandbox(['sandbox_follow' => false]);
        $post = $this->makePost($channel);
        $this->post(route('admin.smart-instagram.posts.store'), $this->payload($post))->assertSessionHasNoErrors();

        // کامنت روی پست دیگر اجرا نمی‌شود
        $this->ingest(['type' => 'comment', 'id' => 'c_other', 'sender' => ['id' => 'u_9', 'username' => 'other'], 'text' => 'لینک', 'media_id' => 'reel_other'])->assertOk();
        $this->assertSame(0, AutomationRun::query()->count());

        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1', 'username' => 'mohsen_shop'], 'text' => 'لینك لطفاً', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, AutomationRun::query()->count());
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'public_reply')->where('status', 'sent')->count());
        $opening = OutboundMessage::query()->where('kind', 'private_reply')->firstOrFail();
        $this->assertSame('sent', $opening->status, (string) $opening->policy_reason);
        $session = PostFlowSession::query()->firstOrFail();
        $this->assertSame('awaiting_click', $session->stage);

        // همان کامنت دوباره: جریان تکراری نمی‌سازد
        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1'], 'text' => 'لینک', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, PostFlowSession::query()->count());

        // زدن دکمه، فالو نکرده → درخواست فالو
        $this->ingest(['type' => 'dm', 'id' => 'm_1', 'sender' => ['id' => 'u_1'], 'text' => 'ارسال لینک'])->assertOk();
        $this->assertSame('awaiting_follow', $session->fresh()->stage);
        $this->assertSame('not_following', $session->fresh()->follow_status);
        $this->assertTrue(OutboundMessage::query()->where('body', 'like', '%فالو%')->exists());

        // فالو کرد → کارت با سه دکمه
        $channel->forceFill(['settings' => ['sandbox_follow' => true]])->save();
        $this->ingest(['type' => 'dm', 'id' => 'm_2', 'sender' => ['id' => 'u_1'], 'text' => 'فالو کردم'])->assertOk();
        $this->assertSame('completed', $session->fresh()->stage);
        $card = OutboundMessage::query()->get()->first(fn ($o) => data_get($o->message_payload, 'message.attachment'));
        $this->assertNotNull($card, 'کارت ارسال شد');
        $buttons = data_get($card->message_payload, 'message.attachment.payload.elements.0.buttons');
        $this->assertCount(3, (array) $buttons);
        $this->assertSame(0, OutboundMessage::query()->whereIn('status', ['failed', 'blocked'])->count());
    }

    public function test_test_mode_and_manual_posts_never_send(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $channel = $this->sandbox();
        $post = $this->makePost($channel);
        $this->post(route('admin.smart-instagram.posts.store'), $this->payload($post, 'test'));
        $this->assertSame('test', PostCampaign::query()->value('status'));

        $this->ingest(['type' => 'comment', 'id' => 'c_t', 'sender' => ['id' => 'u_2'], 'text' => 'لینک', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, AutomationRun::query()->where('status', 'simulated')->count());
        $this->assertSame(0, OutboundMessage::query()->count());

        // پست دستی بدون تأیید اتصال فقط پیش‌نویس می‌ماند
        $manual = $this->makePost($channel, '999', 'needs_check');
        $this->post(route('admin.smart-instagram.posts.store'), $this->payload($manual, 'active'));
        $campaign = PostCampaign::query()->where('post_id', $manual->id)->firstOrFail();
        $this->assertSame('draft', $campaign->status);
        $this->post(route('admin.smart-instagram.posts.status', $campaign), ['status' => 'active']);
        $this->assertSame('draft', $campaign->fresh()->status);
    }

    public function test_card_requires_link_button_and_validates_input(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost($this->sandbox());
        $payload = $this->payload($post);
        $payload['settings']['card']['buttons'] = [['preset' => 'info', 'type' => 'postback', 'label' => 'اطلاعات', 'reply_text' => 'x']];

        $this->post(route('admin.smart-instagram.posts.store'), $payload)->assertSessionHasErrors('settings.card.buttons');
        $this->post(route('admin.smart-instagram.posts.store'), ['keywords' => [['keyword' => '']]] + $payload)->assertSessionHasErrors();
        $this->assertSame(0, PostCampaign::query()->count());
    }

    public function test_sync_from_meta_stores_cover_and_shows_missing_stats(): void
    {
        Storage::fake('public');
        $integration = MarketingIntegration::query()->create(['provider' => 'meta', 'name' => 'Meta', 'status' => 'connected', 'credentials' => ['access_token' => 'tok', 'instagram_user_id' => 'acct_9']]);
        Channel::query()->create(['workspace_id' => $this->ws(), 'marketing_integration_id' => $integration->id, 'gateway' => 'meta', 'name' => 'Meta', 'external_account_id' => 'acct_9', 'status' => 'connected']);
        Http::fake([
            '*/acct_9/media*' => Http::response(['data' => [
                ['id' => 'm_100', 'caption' => 'ریلز تازه', 'media_type' => 'VIDEO', 'media_product_type' => 'REELS', 'thumbnail_url' => 'https://cdn.example.test/t.jpg', 'permalink' => 'https://www.instagram.com/reel/XYZ/', 'timestamp' => '2026-10-01T10:00:00+0000', 'like_count' => 42, 'comments_count' => 7],
            ]]),
            'cdn.example.test/*' => Http::response('JPEGDATA', 200, ['Content-Type' => 'image/jpeg']),
        ]);
        $this->actingAs($this->leader(), 'admin');

        $this->post(route('admin.smart-instagram.posts.sync'))->assertRedirect()->assertSessionHas('success');

        $post = Post::query()->where('media_id', 'm_100')->firstOrFail();
        $this->assertSame('XYZ', $post->shortcode);
        $this->assertSame(42, $post->like_count);
        $this->assertNull($post->saved_count);
        Storage::disk('public')->assertExists($post->cover_path);
        $this->get(route('admin.smart-instagram.posts.create'))->assertOk()->assertSee('ریلز تازه');
    }

    public function test_workspace_isolation_and_permissions(): void
    {
        $post = $this->makePost($this->sandbox());
        $campaign = app(PostCampaignService::class)->save($post, $this->payload($post, 'draft'));

        $viewer = $this->leader('viewer@example.test', 'support');
        WorkspaceMember::query()->updateOrCreate(['workspace_id' => $this->ws(), 'admin_id' => $viewer->id], ['role' => 'viewer']);
        $this->actingAs($viewer, 'admin');
        $this->get(route('admin.smart-instagram.posts.index'))->assertOk();
        $this->get(route('admin.smart-instagram.posts.create'))->assertForbidden();
        $this->post(route('admin.smart-instagram.posts.status', $campaign), ['status' => 'active'])->assertForbidden();

        $other = Workspace::query()->create(['name' => 'دیگر', 'slug' => 'other-'.uniqid()]);
        $campaign->forceFill(['workspace_id' => $other->id])->save();
        $this->actingAs($this->leader('lead2@example.test'), 'admin');
        $this->get(route('admin.smart-instagram.posts.show', $campaign))->assertNotFound();
    }

    public function test_legacy_rule_import_keeps_behaviour(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $this->sandbox();
        $rule = AutomationRule::query()->create([
            'workspace_id' => $this->ws(), 'name' => 'قدیمی', 'trigger' => 'comment_keyword', 'scope_ref' => 'reel_legacy', 'keywords' => ['قیمت'],
            'match_mode' => 'contains', 'actions' => [['type' => 'public_reply', 'text' => 'دایرکت رو ببین']], 'status' => 'active',
        ]);
        $this->get(route('admin.smart-instagram.posts.index'))->assertSee('انتقال به ثبت پست');

        $this->post(route('admin.smart-instagram.posts.import', $rule))->assertRedirect();
        $campaign = PostCampaign::query()->firstOrFail();
        $this->assertFalse((bool) $campaign->follow_required);
        $this->assertSame('reel_legacy', $campaign->post->media_id);
    }
}
