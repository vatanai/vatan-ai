<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\Product;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\TelegramAdmin;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\Telegram\InstagramTelegramBot;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** بات تلگرام و مینی‌اپ «ثبت پست»: اتصال حساب، احراز هویت initData، ذخیره‌ی سناریو و اعلان پست تازه. */
class SmartInstagramTelegramAppTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = '123456:TEST-instagram-bot-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.telegram_instagram.bot_token' => self::TOKEN,
            'services.telegram_instagram.bot_username' => 'vatan_instagram_dashbord_bot',
            'smart_instagram.ai.enabled' => false,
        ]);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
    }

    private function initData(int $telegramId, string $token = self::TOKEN): string
    {
        $data = ['auth_date' => (string) time(), 'query_id' => 'AAH', 'user' => json_encode(['id' => $telegramId, 'first_name' => 'محسن', 'username' => 'mohsen'])];
        ksort($data);
        $check = collect($data)->map(fn ($v, $k) => $k.'='.$v)->implode("\n");
        $data['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', $token, 'WebAppData', true));

        return http_build_query($data);
    }

    private function leader(): Admin
    {
        return Admin::query()->create(['name' => 'محسن', 'email' => 'tg@example.test', 'password' => 'password', 'role' => 'leader', 'is_active' => true]);
    }

    private function linked(int $telegramId = 555): Admin
    {
        $admin = $this->leader();
        TelegramAdmin::query()->create(['workspace_id' => app(WorkspaceContext::class)->id(), 'admin_id' => $admin->id, 'telegram_id' => $telegramId, 'is_active' => true, 'linked_at' => now()]);

        return $admin;
    }

    private function igPost(array $attrs = []): Post
    {
        $channel = Channel::query()->firstOrCreate(['workspace_id' => app(WorkspaceContext::class)->id(), 'gateway' => 'sandbox'], [
            'name' => 'آزمایشی', 'username' => 'vatan.test', 'external_account_id' => 'acct_1', 'status' => 'connected', 'outbound_enabled' => true,
        ]);

        return Post::query()->create($attrs + [
            'workspace_id' => app(WorkspaceContext::class)->id(), 'channel_id' => $channel->id, 'media_id' => 'reel_'.uniqid(), 'caption' => 'کیف چرم — برای لینک «لینک» کامنت کن',
            'media_type' => 'VIDEO', 'product_type' => 'REELS', 'permalink' => 'https://www.instagram.com/reel/ABC/', 'source' => 'sync',
            'connection_status' => 'verified', 'published_at' => now()->subHour(), 'cover_path' => 'smart-instagram/posts/1/x.jpg',
        ]);
    }

    private function product(): Product
    {
        return Product::query()->create([
            'slug' => 'tg-test-product', 'name_fa' => 'محصول آزمایشی', 'name_en' => 'Test', 'category' => 'TEST', 'status' => 'active',
            'thumbnail' => 'products/test.jpg', 'primary_model' => 'test-model', 'prompt_template' => 'آزمایش',
        ]);
    }

    private function api(string $method, string $uri, array $data = [], int $telegramId = 555, ?string $initData = null)
    {
        return $this->json($method, $uri, $data, ['X-Telegram-Init-Data' => $initData ?? $this->initData($telegramId)]);
    }

    public function test_mini_app_page_renders(): void
    {
        $this->get(route('telegram.instagram.app', ['post' => 7]))->assertOk()->assertSee('telegram-web-app.js', false)->assertSee('"postId":7', false);
    }

    public function test_api_rejects_forged_and_unlinked_telegram_users(): void
    {
        $this->linked();
        $this->api('GET', '/api/tg/instagram/bootstrap', [], 555, $this->initData(555, '999:other-bot'))->assertStatus(401);
        $this->api('GET', '/api/tg/instagram/bootstrap', [], 777)->assertStatus(403)->assertJsonPath('code', 'TELEGRAM_NOT_LINKED')->assertJsonPath('telegram_id', 777);
        $this->api('GET', '/api/tg/instagram/bootstrap')->assertOk()->assertJsonPath('can_manage', true);
    }

    public function test_admin_links_telegram_account_through_deep_link(): void
    {
        $admin = $this->leader();
        $this->actingAs($admin, 'admin');
        $this->get(route('admin.smart-instagram.connections'))->assertOk()->assertSee('اتصال تلگرام خودم');

        $redirect = $this->post(route('admin.smart-instagram.connections.telegram'))->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://t.me/vatan_instagram_dashbord_bot?start=link_', $redirect);
        $start = substr($redirect, strpos($redirect, 'start=') + 6);

        $update = ['message' => ['chat' => ['id' => 901, 'type' => 'private'], 'from' => ['id' => 901, 'first_name' => 'محسن', 'username' => 'mohsen'], 'text' => '/start '.$start]];
        $this->postJson('/webhooks/telegram/instagram', $update)->assertStatus(403);
        $this->postJson('/webhooks/telegram/instagram', $update, ['X-Telegram-Bot-Api-Secret-Token' => app(InstagramTelegramBot::class)->webhookSecret()])->assertOk();

        $this->assertDatabaseHas('instagram_telegram_admins', ['telegram_id' => 901, 'admin_id' => $admin->id, 'is_active' => true]);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage') && str_contains((string) $r['text'], 'وصل شد'));

        // توکن یک‌بارمصرف است
        $this->postJson('/webhooks/telegram/instagram', ['message' => ['chat' => ['id' => 902, 'type' => 'private'], 'from' => ['id' => 902], 'text' => '/start '.$start]], ['X-Telegram-Bot-Api-Secret-Token' => app(InstagramTelegramBot::class)->webhookSecret()])->assertOk();
        $this->assertDatabaseMissing('instagram_telegram_admins', ['telegram_id' => 902]);
    }

    public function test_save_creates_campaign_with_rule_and_status_toggle(): void
    {
        $this->linked();
        $post = $this->igPost();
        $product = $this->product();

        $show = $this->api('GET', '/api/tg/instagram/posts/'.$post->id)->assertOk()->assertJsonPath('campaign.id', null);
        $this->assertSame('لینک', $show->json('campaign.keywords.0.keyword'));

        $settings = app(PostCampaignService::class)->defaults();
        $body = [
            'intent' => 'active', 'keywords' => [['keyword' => 'لینک', 'match_mode' => 'contains', 'is_active' => true]],
            'public_reply_enabled' => true, 'dm_enabled' => true, 'follow_required' => true, 'settings' => $settings,
        ];
        $this->api('POST', '/api/tg/instagram/posts/'.$post->id, $body)->assertStatus(422)->assertJsonPath('field', 'settings.card.product_id');

        $body['settings']['card']['product_id'] = $product->id;
        $this->api('POST', '/api/tg/instagram/posts/'.$post->id, $body)->assertOk()->assertJsonPath('campaign.status', 'active');

        $campaign = PostCampaign::query()->with('rule', 'versions')->firstOrFail();
        $this->assertSame('active', $campaign->rule->status);
        $this->assertSame(['لینک'], $campaign->rule->keywords);
        $this->assertSame('از مینی‌اپ تلگرام — محسن', $campaign->versions->first()->note);

        $this->api('POST', '/api/tg/instagram/posts/'.$post->id.'/status', ['status' => 'paused'])->assertOk();
        $this->assertSame('paused', $campaign->fresh()->status);
        $this->assertSame('paused', $campaign->rule->fresh()->status);

        $this->api('GET', '/api/tg/instagram/posts')->assertOk()->assertJsonPath('posts.0.campaign.status', 'paused');
    }

    public function test_viewer_role_cannot_save(): void
    {
        $admin = Admin::query()->create(['name' => 'ناظر', 'email' => 'v@example.test', 'password' => 'password', 'role' => 'admin', 'is_active' => true]);
        \App\Models\SmartInstagram\WorkspaceMember::query()->create(['workspace_id' => app(WorkspaceContext::class)->id(), 'admin_id' => $admin->id, 'role' => 'viewer', 'is_active' => true]);
        TelegramAdmin::query()->create(['workspace_id' => app(WorkspaceContext::class)->id(), 'admin_id' => $admin->id, 'telegram_id' => 556, 'is_active' => true]);
        $post = $this->igPost();

        $this->api('GET', '/api/tg/instagram/bootstrap', [], 556)->assertOk()->assertJsonPath('can_manage', false);
        $this->api('POST', '/api/tg/instagram/posts/'.$post->id, ['settings' => []], 556)->assertStatus(403);
    }

    public function test_new_posts_are_announced_once_and_old_ones_silently(): void
    {
        $this->linked(555);
        $fresh = $this->igPost();
        $old = $this->igPost(['published_at' => now()->subDays(5)]);

        $this->artisan('smart-instagram:telegram-bot', ['action' => 'notify', '--no-sync' => true])->assertSuccessful();
        $this->artisan('smart-instagram:telegram-bot', ['action' => 'notify', '--no-sync' => true])->assertSuccessful();

        $this->assertCount(3, Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/send')), 'ادمین + دو کارمند، فقط پست تازه، فقط یک بار');
        $sent = Http::recorded(fn (HttpRequest $r) => str_contains($r->url(), '/send') && $r['chat_id'] === 555)->values();
        $this->assertCount(1, $sent);
        [$request] = $sent[0];
        $this->assertSame(555, $request['chat_id']);
        $this->assertStringContainsString('/tg/instagram?post='.$fresh->id, json_encode($request['reply_markup'], JSON_UNESCAPED_SLASHES));
        $this->assertNotNull($old->fresh()->telegram_notified_at);
    }

    public function test_seeded_staff_can_use_bot_without_admin_account(): void
    {
        $this->assertDatabaseHas('instagram_telegram_admins', ['telegram_id' => 101754869, 'name' => 'ساغر محمدی', 'role' => 'manager', 'admin_id' => null]);
        $this->assertDatabaseHas('instagram_telegram_admins', ['telegram_id' => 6234518857, 'name' => 'عاطفه جورسرایی']);
        $post = $this->igPost();
        $product = $this->product();

        $this->api('GET', '/api/tg/instagram/bootstrap', [], 101754869)->assertOk()->assertJsonPath('can_manage', true)->assertJsonPath('admin.name', 'ساغر محمدی');
        $settings = app(PostCampaignService::class)->defaults();
        $settings['card']['product_id'] = $product->id;
        $this->api('POST', '/api/tg/instagram/posts/'.$post->id, ['intent' => 'active', 'keywords' => [['keyword' => 'لینک']], 'public_reply_enabled' => true, 'dm_enabled' => true, 'settings' => $settings], 6234518857)->assertOk();
        $this->assertSame('active', PostCampaign::query()->firstOrFail()->status);
    }

    public function test_owner_manages_staff_list_from_dashboard(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $this->get(route('admin.smart-instagram.connections'))->assertOk()->assertSee('کارمندان بات تلگرام')->assertSee('ساغر محمدی');

        $this->post(route('admin.smart-instagram.connections.telegram.store'), ['name' => 'کارمند تازه', 'telegram_id' => '۱۲۳۴۵۶۷', 'role' => 'viewer'])->assertRedirect()->assertSessionHasNoErrors();
        $member = TelegramAdmin::query()->where('telegram_id', 1234567)->firstOrFail();
        $this->assertSame('viewer', $member->role);
        $this->api('GET', '/api/tg/instagram/bootstrap', [], 1234567)->assertOk()->assertJsonPath('can_manage', false);

        $this->post(route('admin.smart-instagram.connections.telegram.store'), ['name' => 'x', 'telegram_id' => 'abc', 'role' => 'viewer'])->assertSessionHasErrors('telegram_id');

        $this->patch(route('admin.smart-instagram.connections.telegram.update', $member), ['is_active' => 0])->assertRedirect();
        $this->api('GET', '/api/tg/instagram/bootstrap', [], 1234567)->assertStatus(403);

        $this->delete(route('admin.smart-instagram.connections.telegram.destroy', $member))->assertRedirect();
        $this->assertDatabaseMissing('instagram_telegram_admins', ['telegram_id' => 1234567]);
    }

    public function test_unknown_user_gets_private_bot_message(): void
    {
        $this->postJson('/webhooks/telegram/instagram', ['message' => ['chat' => ['id' => 42, 'type' => 'private'], 'from' => ['id' => 42], 'text' => '/start']], ['X-Telegram-Bot-Api-Secret-Token' => app(InstagramTelegramBot::class)->webhookSecret()])->assertOk();
        Http::assertSent(fn (HttpRequest $r) => str_contains((string) $r['text'], 'مخصوص تیم وطن') && str_contains((string) $r['text'], '42'));
    }
}
