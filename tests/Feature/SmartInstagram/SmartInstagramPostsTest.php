<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\MarketingIntegration;
use App\Models\Product;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\AiProfile;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Conversation;
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
        $settings['card']['product_id'] = Product::query()->firstOrCreate(
            ['slug' => 'smart-instagram-test-product'],
            [
                'name_fa' => 'محصول آزمایشی', 'name_en' => 'Test product',
                'category' => 'TEST', 'status' => 'active', 'thumbnail' => 'products/test.jpg',
                'primary_model' => 'test-model', 'prompt_template' => 'آزمایش',
            ]
        )->id;
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

    public function test_caption_keywords_are_used_when_form_does_not_send_keywords_and_order_is_compiled(): void
    {
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost($this->sandbox());
        $post->forceFill(['caption' => 'برای دریافت لینک، کلمه «سلفی» را کامنت کن #محصول'])->save();
        $payload = $this->payload($post);
        unset($payload['keywords']);
        $payload['settings']['flow']['order'] = 'dm_first';

        $this->post(route('admin.smart-instagram.posts.store'), $payload)->assertSessionHasNoErrors();

        $campaign = PostCampaign::query()->with('keywords', 'rule')->firstOrFail();
        $this->assertSame(['سلفی', 'محصول'], $campaign->keywords->pluck('keyword')->all());
        $this->assertSame(['post_flow', 'public_reply', 'add_tag', 'stop'], collect($campaign->rule->actions)->pluck('type')->all());

        $simulation = $this->postJson(route('admin.smart-instagram.posts.simulate', $campaign), ['text' => 'سلفی', 'follows' => 'yes'])
            ->assertOk()->json();
        $this->assertSame('دایرکت', $simulation['steps'][0]['where']);
        $lastStep = end($simulation['steps']);
        $this->assertSame('کامنت', $lastStep['where']);
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
        // فالو ندارد ← همان پاسخ خصوصی اول، درخواست فالو است (جریان دومرحله‌ای)
        $this->assertSame('awaiting_follow', $session->stage);
        $this->assertSame('not_following', $session->follow_status);
        $this->assertStringContainsString('فالو', $opening->body);

        // همان کامنت دوباره: جریان تکراری نمی‌سازد
        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1'], 'text' => 'لینک', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, PostFlowSession::query()->count());

        // فالو کرد → کارت با سه دکمه
        $channel->forceFill(['settings' => ['sandbox_follow' => true]])->save();
        $this->ingest(['type' => 'dm', 'id' => 'm_2', 'sender' => ['id' => 'u_1'], 'text' => 'فالو کردم'])->assertOk();
        $this->assertSame('completed', $session->fresh()->stage);
        $card = OutboundMessage::query()->get()->first(fn ($o) => data_get($o->message_payload, 'message.attachment.payload.template_type') === 'generic');
        $this->assertNotNull($card, 'کارت ارسال شد');
        $buttons = data_get($card->message_payload, 'message.attachment.payload.elements.0.buttons');
        $this->assertCount(3, (array) $buttons);
        $this->assertSame(0, OutboundMessage::query()->whereIn('status', ['failed', 'blocked'])->count());
    }

    public function test_post_campaign_ignores_stale_human_flag_but_respects_current_sensitive_comment(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $channel = $this->sandbox(['sandbox_follow' => false]);
        $post = $this->makePost($channel);
        $this->post(route('admin.smart-instagram.posts.store'), $this->payload($post))->assertSessionHasNoErrors();

        // یک پیام قدیمی می‌تواند گفتگو را به انسان واگذار کرده باشد؛ این پرچم نباید
        // کامنت تازه و بی‌خطرِ کمپین مشخص را برای همیشه قفل کند.
        $this->ingest(['type' => 'comment', 'id' => 'c_old', 'sender' => ['id' => 'u_lock', 'username' => 'locked_user'], 'text' => 'سلام', 'media_id' => 'reel_1'])->assertOk();
        $conversation = Conversation::query()->firstOrFail();
        $conversation->forceFill(['needs_human' => true])->save();

        $this->ingest(['type' => 'comment', 'id' => 'c_safe', 'sender' => ['id' => 'u_lock', 'username' => 'locked_user'], 'text' => 'لینک لطفاً', 'media_id' => 'reel_1'])->assertOk();

        $safeRun = AutomationRun::query()->latest('id')->firstOrFail();
        $this->assertSame('success', $safeRun->status);
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'public_reply')->where('status', 'sent')->count());
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'private_reply')->where('status', 'sent')->count());

        AiProfile::query()->where('workspace_id', $this->ws())->where('is_active', true)
            ->update(['escalation_keywords' => ['شکایت']]);
        $this->ingest(['type' => 'comment', 'id' => 'c_sensitive', 'sender' => ['id' => 'u_sensitive', 'username' => 'sensitive_user'], 'text' => 'لینک برای شکایت', 'media_id' => 'reel_1'])->assertOk();

        $sensitiveRun = AutomationRun::query()->latest('id')->firstOrFail();
        $this->assertSame('skipped', $sensitiveRun->status);
        $this->assertStringContainsString('همین پیام حساس است', (string) data_get($sensitiveRun->decisions, '0.result'));
        $this->assertSame(2, OutboundMessage::query()->count(), 'برای پیام حساس هیچ ارسال تازه‌ای ساخته نمی‌شود');

        // شبیه‌سازی داده‌ی تولیدشده پیش از اصلاح: اجرا «موفق» بوده ولی هیچ خروجی
        // و نشست دایرکتی ثبت نشده است. «بررسی مجدد» باید همان اجرا را ترمیم کند.
        OutboundMessage::query()->where('automation_run_id', $safeRun->id)->delete();
        PostFlowSession::query()->where('automation_run_id', $safeRun->id)->delete();
        $this->post(route('admin.smart-instagram.posts.recheck', PostCampaign::query()->firstOrFail()))
            ->assertRedirect()->assertSessionHas('success');

        $this->assertSame('success', $safeRun->fresh()->status);
        $this->assertSame(1, OutboundMessage::query()->where('automation_run_id', $safeRun->id)->where('kind', 'public_reply')->count());
        $this->assertSame(1, OutboundMessage::query()->where('automation_run_id', $safeRun->id)->where('kind', 'private_reply')->count());
    }

    public function test_live_comment_personalization_receives_campaign_post_and_customer_context(): void
    {
        config(['smart_instagram.ai.enabled' => true, 'smart_instagram.outbound_enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost($this->sandbox());
        $payload = $this->payload($post);
        $payload['settings']['reply']['ai_personalize'] = true;
        $this->post(route('admin.smart-instagram.posts.store'), $payload)->assertSessionHasNoErrors();

        $captured = [];
        Http::fake(['*/chat/completions' => function ($request) use (&$captured) {
            $captured[] = $request->data();

            return Http::response([
                'model' => 'test/model',
                'usage' => ['prompt_tokens' => 40, 'completion_tokens' => 12],
                'choices' => [['message' => ['content' => json_encode(['reply' => 'محسن جان، جزئیات این کیف رو توی دایرکت ببین 🌿'])]]],
            ]);
        }]);

        $this->ingest([
            'type' => 'comment',
            'id' => 'c-ai',
            'sender' => ['id' => 'u-ai', 'username' => 'mohsen_shop', 'name' => 'محسن'],
            'text' => 'لینک این کیف رو می‌فرستی؟',
            'media_id' => 'reel_1',
        ])->assertOk();

        $this->assertNotEmpty($captured);
        $request = collect($captured)->last(fn (array $data) => str_contains((string) data_get($data, 'messages.0.content'), 'قواعد مشترک و الزامی'));
        $this->assertNotNull($request);
        $system = collect($request['messages'])->firstWhere('role', 'system')['content'];
        $user = collect($request['messages'])->firstWhere('role', 'user')['content'];
        $this->assertStringContainsString('قواعد مشترک و الزامی', $system);
        $this->assertStringContainsString('متن رباتیک', $system);
        $this->assertStringContainsString('کیف چرم دست‌دوز', $user);
        $this->assertStringContainsString('لینک این کیف رو می‌فرستی؟', $user);
        $this->assertStringContainsString('جریان دایرکت فعال است: بله', $user);
        $this->assertStringContainsString('محسن جان', OutboundMessage::query()->where('kind', 'public_reply')->value('body'));
    }

    public function test_ai_writer_generates_all_post_sections_with_shared_prompt_contract(): void
    {
        config(['smart_instagram.ai.enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost($this->sandbox());
        $captured = null;
        Http::fake(['*/chat/completions' => function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response([
                'model' => 'test/model',
                'usage' => ['prompt_tokens' => 60, 'completion_tokens' => 30],
                'choices' => [['message' => ['content' => json_encode([
                    'public_replies' => ['{name} جان، جزئیات رو توی دایرکت ببین 🌿'],
                    'opening_text' => 'سلام {name} جان، برای دیدن لینک روی دکمه بزن.',
                    'opening_button' => 'دیدن لینک',
                    'follow_text' => '{name} جان، اول پیج رو دنبال کن و بعد بزن فالو کردم.',
                    'follow_retry_text' => 'اگر فالو کردی، دوباره روی دکمه بزن.',
                    'follow_button' => 'فالو کردم',
                    'card_intro' => 'اینم اطلاعاتی که خواستی.',
                    'card_title' => 'کیف چرم دست‌دوز',
                    'card_subtitle' => 'جزئیات محصول را ببین',
                    'card_buttons' => ['مشاهده محصول'],
                ])]]],
            ]);
        }]);

        $response = $this->postJson(route('admin.smart-instagram.posts.ai.generate'), [
            'sections' => ['public_reply', 'opening', 'follow', 'card'],
            'post_id' => $post->id,
            'keywords' => ['لینک'],
            'hint' => 'لحن خودمانی و کوتاه',
        ])->assertOk()->assertJsonPath('ok', true);

        $response->assertJsonPath('fields.opening_button', 'دیدن لینک')
            ->assertJsonPath('fields.follow_button', 'فالو کردم')
            ->assertJsonPath('fields.card_buttons.0', 'مشاهده محصول');
        $this->assertNotNull($captured);
        $this->assertStringContainsString('قواعد مشترک و الزامی', $captured['messages'][0]['content']);
        $this->assertStringContainsString('کیف چرم دست‌دوز', $captured['messages'][1]['content']);
    }

    public function test_ai_writer_refreshes_only_the_requested_field(): void
    {
        config(['smart_instagram.ai.enabled' => true]);
        $this->actingAs($this->leader(), 'admin');
        $post = $this->makePost();
        $captured = null;
        Http::fake(['*/chat/completions' => function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response([
                'model' => 'test/model',
                'usage' => ['prompt_tokens' => 30, 'completion_tokens' => 10],
                'choices' => [['message' => ['content' => json_encode([
                    'card_title' => 'تیتر تازه‌ی کارت',
                    'card_subtitle' => 'نباید به این فیلد اعمال شود',
                    'card_buttons' => ['نباید تولید شود'],
                ])]]],
            ]);
        }]);

        $this->postJson(route('admin.smart-instagram.posts.ai.generate'), [
            'sections' => ['card'],
            'target_field' => 'card_title',
            'post_id' => $post->id,
            'keywords' => ['لینک'],
        ])->assertOk()
            ->assertJsonPath('target_field', 'card_title')
            ->assertJsonPath('target_value', 'تیتر تازه‌ی کارت')
            ->assertJsonPath('fields.card_title', 'تیتر تازه‌ی کارت')
            ->assertJsonMissingPath('fields.card_subtitle')
            ->assertJsonMissingPath('fields.card_buttons');

        $this->assertNotNull($captured);
        $this->assertStringContainsString('فقط همین فیلد را تولید کن', $captured['messages'][0]['content']);
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
