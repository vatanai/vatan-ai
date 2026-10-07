<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\MarketingEvent;
use App\Models\Product;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\OutboundMessage;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostFlowSession;
use App\Services\SmartInstagram\ContactNameResolver;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * رفع مشکلات تست واقعی «ثبت پست» (۱۲ مهر):
 * پاسخ سامانه به کامنت خودش، توقف جریان بعد از زدن دکمه، دکمه‌ی واقعی در پیام اول،
 * «،» یتیم اول پیام و ثبت وب‌هوک زنده.
 */
class SmartInstagramPostFlowFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'smart_instagram.ingest.secret' => 'ingest-test-secret',
            'smart_instagram.ai.enabled' => false,
            'smart_instagram.outbound_enabled' => true,
            'services.meta.app_secret' => 'meta-test-secret',
        ]);
    }

    private function ws(): int
    {
        return app(WorkspaceContext::class)->id();
    }

    private function channel(array $settings = []): Channel
    {
        return Channel::query()->create([
            'workspace_id' => $this->ws(), 'gateway' => 'sandbox', 'name' => 'آزمایشی', 'username' => 'ai_vatan',
            'external_account_id' => 'acct_1', 'status' => 'connected', 'outbound_enabled' => true,
            'settings' => array_merge(['sandbox_follow' => false], $settings),
        ]);
    }

    private function campaign(Channel $channel, string $openingText = '{name} عزیز، خوشحالم که به این پست علاقه‌مندی!'): void
    {
        $this->actingAs(Admin::query()->create(['name' => 'ادمین', 'email' => 'fix@example.test', 'password' => 'password', 'role' => 'leader', 'is_active' => true]), 'admin');
        $post = Post::query()->create([
            'workspace_id' => $this->ws(), 'channel_id' => $channel->id, 'media_id' => 'reel_1', 'caption' => 'کلاژ سه‌تایی',
            'media_type' => 'VIDEO', 'product_type' => 'REELS', 'permalink' => 'https://www.instagram.com/reel/ABC/', 'shortcode' => 'ABC',
            'source' => 'sync', 'connection_status' => 'verified', 'published_at' => now()->subDay(),
        ]);
        $settings = app(PostCampaignService::class)->defaults();
        $settings['reply']['ai_personalize'] = false;
        $settings['dm']['opening_text'] = $openingText;
        $settings['dm']['opening_button'] = 'مشاهده اطلاعات';
        $settings['card']['product_id'] = Product::query()->firstOrCreate(['slug' => 'si-fix-product'], [
            'name_fa' => 'محصول', 'name_en' => 'Product', 'category' => 'TEST', 'status' => 'active',
            'thumbnail' => 'products/test.jpg', 'primary_model' => 'test-model', 'prompt_template' => 'آزمایش',
        ])->id;
        $settings['card']['buttons'] = [['preset' => 'product', 'type' => 'web_url', 'label' => 'مشاهده محصول', 'url' => 'https://aivatan.com/p/x']];

        $this->post(route('admin.smart-instagram.posts.store'), [
            'post_id' => $post->id, 'title' => 'کلاژ', 'intent' => 'active',
            'follow_required' => '1', 'public_reply_enabled' => '1', 'dm_enabled' => '1',
            'keywords' => [['keyword' => 'کلاژ', 'match_mode' => 'contains', 'is_active' => '1']],
            'settings' => $settings,
        ])->assertSessionHasNoErrors();
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

    public function test_own_reply_comment_never_triggers_a_second_reply(): void
    {
        $channel = $this->channel();
        $this->campaign($channel);

        $this->ingest(['type' => 'comment', 'id' => 'c_user', 'sender' => ['id' => 'u_1', 'username' => 'shaygangp'], 'text' => 'کلاژ', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, AutomationRun::query()->count());

        // پاسخ عمومی خود پیج (که کلمه‌ی کلیدی را هم دارد) دوباره از همگام‌سازی برمی‌گردد.
        $this->ingest(['type' => 'comment', 'id' => 'c_own', 'sender' => ['id' => '17841400000', 'username' => 'ai_vatan'], 'text' => 'کلاژ سه‌تایی واقعاً می‌تونه عکس‌هات رو خاص کنه!', 'media_id' => 'reel_1', 'parent_id' => 'c_user'])->assertOk();

        $this->assertSame(1, AutomationRun::query()->count(), 'روی کامنت خود پیج هیچ قانونی اجرا نمی‌شود');
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'public_reply')->count());
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'private_reply')->count());
        $this->assertFalse(Contact::query()->where('username', 'ai_vatan')->exists());
        $this->assertSame('ignored', MarketingEvent::query()->where('external_id', 'comment:c_own')->value('processing_status'));
    }

    public function test_button_click_continues_flow_even_if_conversation_was_flagged_for_human(): void
    {
        $channel = $this->channel();
        $this->campaign($channel);
        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1', 'username' => 'shaygangp'], 'text' => 'کلاژ', 'media_id' => 'reel_1'])->assertOk();

        // پیام اول: بدون نام، نباید با «،» شروع شود؛ دکمه‌ی واقعی زیر متن (قالب دکمه‌ای) حتی بدون وب‌هوک.
        $opening = OutboundMessage::query()->where('kind', 'private_reply')->firstOrFail();
        $this->assertSame('sent', $opening->status, (string) $opening->policy_reason);
        $this->assertSame('خوشحالم که به این پست علاقه‌مندی!', $opening->body);
        $this->assertSame('button', data_get($opening->message_payload, 'message.attachment.payload.template_type'));
        $this->assertSame('خوشحالم که به این پست علاقه‌مندی!', data_get($opening->message_payload, 'message.attachment.payload.text'));
        $this->assertSame(['type' => 'postback', 'title' => 'مشاهده اطلاعات', 'payload' => 'SIF:open:'.PostFlowSession::query()->value('campaign_id')], data_get($opening->message_payload, 'message.attachment.payload.buttons.0'));

        // تحلیل هوش مصنوعی گفتگو را «نیازمند انسان» علامت زده است.
        Conversation::query()->update(['needs_human' => true]);

        $this->ingest(['type' => 'dm', 'id' => 'm_1', 'sender' => ['id' => 'u_1'], 'text' => 'مشاهده اطلاعات'])->assertOk();

        $session = PostFlowSession::query()->firstOrFail();
        $this->assertSame('awaiting_follow', $session->stage);
        $follow = OutboundMessage::query()->where('kind', 'dm')->latest('id')->firstOrFail();
        $this->assertSame('sent', $follow->status, (string) $follow->policy_reason);
        $this->assertSame('https://www.instagram.com/ai_vatan', data_get($follow->message_payload, 'message.attachment.payload.buttons.0.url'));
        $this->assertSame('فالو کردم ✅', data_get($follow->message_payload, 'message.attachment.payload.buttons.1.title'));

        // فالو کرد ← کارت، باز هم بدون مسدود شدن
        $channel->forceFill(['settings' => ['sandbox_follow' => true]])->save();
        $this->ingest(['type' => 'dm', 'id' => 'm_2', 'sender' => ['id' => 'u_1'], 'text' => 'فالو کردم ✅'])->assertOk();
        $this->assertSame('completed', $session->fresh()->stage);
        $this->assertSame(0, OutboundMessage::query()->whereIn('status', ['blocked', 'failed'])->count());
    }

    public function test_paused_conversation_and_sensitive_reply_still_stop_the_flow(): void
    {
        $channel = $this->channel();
        $this->campaign($channel);
        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1', 'username' => 'shaygangp'], 'text' => 'کلاژ', 'media_id' => 'reel_1'])->assertOk();

        \App\Models\SmartInstagram\AiProfile::query()->where('workspace_id', $this->ws())->where('is_active', true)->update(['escalation_keywords' => ['شکایت']]);
        $this->ingest(['type' => 'dm', 'id' => 'm_1', 'sender' => ['id' => 'u_1'], 'text' => 'شکایت دارم'])->assertOk();
        $this->assertSame('awaiting_click', PostFlowSession::query()->value('stage'));
        $this->assertSame(0, OutboundMessage::query()->where('kind', 'dm')->count());

        Conversation::query()->update(['needs_human' => false, 'ai_paused' => true]);
        $this->ingest(['type' => 'dm', 'id' => 'm_2', 'sender' => ['id' => 'u_1'], 'text' => 'مشاهده اطلاعات'])->assertOk();
        $this->assertSame(0, OutboundMessage::query()->where('kind', 'dm')->where('status', 'sent')->count(), 'توقف دستی همیشه محترم است');
    }

    public function test_live_meta_webhook_switches_to_real_button_template(): void
    {
        $channel = $this->channel(['meta_webhook_at' => now()->toIso8601String()]);
        $this->campaign($channel);
        $this->ingest(['type' => 'comment', 'id' => 'c_1', 'sender' => ['id' => 'u_1', 'username' => 'shaygangp'], 'text' => 'کلاژ', 'media_id' => 'reel_1'])->assertOk();

        $opening = OutboundMessage::query()->where('kind', 'private_reply')->firstOrFail();
        $this->assertSame('sent', $opening->status, (string) $opening->policy_reason);
        $payload = data_get($opening->message_payload, 'message.attachment.payload');
        $this->assertSame('button', $payload['template_type']);
        $this->assertSame('خوشحالم که به این پست علاقه‌مندی!', $payload['text']);
        $this->assertSame(['type' => 'postback', 'title' => 'مشاهده اطلاعات', 'payload' => 'SIF:open:'.PostFlowSession::query()->value('campaign_id')], $payload['buttons'][0]);

        // postback از وب‌هوک ← درخواست فالو با دو دکمه‌ی واقعی (مشاهده پیج + فالو کردم)
        $raw = json_encode(['object' => 'instagram', 'entry' => [['id' => 'acct_1', 'messaging' => [[
            'sender' => ['id' => 'u_1'], 'recipient' => ['id' => 'acct_1'], 'timestamp' => now()->getTimestampMs(),
            'postback' => ['mid' => 'pb_1', 'title' => 'مشاهده اطلاعات', 'payload' => $payload['buttons'][0]['payload']],
        ]]]]]);
        $this->call('POST', '/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $raw, 'meta-test-secret'),
        ], $raw)->assertOk();

        $follow = OutboundMessage::query()->where('kind', 'dm')->latest('id')->firstOrFail();
        $buttons = data_get($follow->message_payload, 'message.attachment.payload.buttons');
        $this->assertSame('web_url', $buttons[0]['type']);
        $this->assertSame('https://www.instagram.com/ai_vatan', $buttons[0]['url']);
        $this->assertSame('postback', $buttons[1]['type']);
        $this->assertSame('acct_1', data_get($channel->fresh()->settings, 'instagram_account_id'));
    }

    public function test_meta_webhook_marks_channel_live_and_ignores_own_comment(): void
    {
        $channel = $this->channel();
        $this->assertFalse($channel->hasLiveWebhook());

        $raw = json_encode(['object' => 'instagram', 'entry' => [['id' => '17841400000', 'changes' => [['field' => 'comments', 'value' => [
            'id' => 'c_self', 'text' => 'کلاژ', 'from' => ['id' => '17841400000', 'username' => 'ai_vatan'], 'media' => ['id' => 'reel_1'],
        ]]]]]]);
        $this->call('POST', '/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $raw, 'meta-test-secret'),
        ], $raw)->assertOk();

        $this->assertTrue($channel->fresh()->hasLiveWebhook());
        $this->assertSame('ignored', MarketingEvent::query()->where('external_id', 'c_self')->value('processing_status'));
        $this->assertSame(0, Contact::query()->count());
    }


    public function test_fast_sync_reads_only_campaign_posts_skips_own_comments_and_stops_with_live_webhook(): void
    {
        config([
            'smart_instagram.composio.enabled' => true,
            'services.composio.api_key' => 'test', 'services.composio.connected_account_id' => 'ca_1', 'services.composio.user_id' => 'user_1',
            'services.composio.instagram_user_id' => 'me',
        ]);
        $calls = [];
        $this->app->instance(\App\Services\ComposioClient::class, new class($calls) extends \App\Services\ComposioClient {
            public function __construct(public array &$calls) {}
            public function execute(string $toolSlug, array $arguments, ?string $connectedAccountId = null, ?string $userId = null): array
            {
                $this->calls[] = $toolSlug;
                $data = $toolSlug === 'INSTAGRAM_GET_IG_MEDIA_COMMENTS' ? ['data' => [
                    ['id' => 'c_fast', 'text' => 'کلاژ', 'from' => ['id' => 'u_7', 'username' => 'shaygangp']],
                    ['id' => 'c_self', 'text' => 'کلاژ سه‌تایی رو ببین', 'from' => ['id' => '1784', 'username' => 'ai_vatan'], 'parent_id' => 'c_fast'],
                ]] : ['data' => []];

                return ['ok' => true, 'message' => 'ok', 'data' => $data, 'external_id' => null, 'retryable' => false, 'status' => 200];
            }
        });

        $this->artisan('smart-instagram:sync-composio --fast')->assertSuccessful();
        $channel = Channel::query()->where('gateway', 'composio')->firstOrFail();
        $this->assertSame([], $calls, 'بدون سناریوی فعال هیچ تماسی با API گرفته نمی‌شود');

        $channel->forceFill(['username' => 'ai_vatan'])->save();
        $this->campaign($channel);
        \App\Models\SmartInstagram\AutomationRule::query()->update(['scope_ref' => 'reel_1']);

        $this->artisan('smart-instagram:sync-composio --fast')->assertSuccessful();
        // فقط کامنت‌های پست سناریو خوانده می‌شود؛ چون همین کامنت جریانی «منتظر کلیک» ساخت، چند گفتگوی اخیر هم خوانده می‌شود.
        $this->assertSame(['INSTAGRAM_GET_IG_MEDIA_COMMENTS', 'INSTAGRAM_LIST_ALL_CONVERSATIONS'], $calls);
        $this->assertSame('awaiting_click', PostFlowSession::query()->value('stage'));
        $this->assertTrue(MarketingEvent::query()->where('external_id', 'comment:c_fast')->exists());
        $this->assertFalse(MarketingEvent::query()->where('external_id', 'comment:c_self')->exists());

        $channel->forceFill(['settings' => array_merge((array) $channel->settings, ['meta_webhook_at' => now()->toIso8601String()])])->save();
        $calls = [];
        $this->artisan('smart-instagram:sync-composio --fast')->assertSuccessful();
        $this->assertSame([], $calls, 'با وب‌هوک فعال چرخه‌ی سریع کاری نمی‌کند');
    }


    public function test_cleanup_migration_removes_only_own_account_runs_and_sessions(): void
    {
        $channel = $this->channel();
        $this->campaign($channel);
        $this->ingest(['type' => 'comment', 'id' => 'c_user', 'sender' => ['id' => 'u_1', 'username' => 'shaygangp'], 'text' => 'کلاژ', 'media_id' => 'reel_1'])->assertOk();

        // داده‌ی قدیمی: پیش از اصلاح، روی پاسخ خود پیج هم قانون اجرا شده بود.
        $own = Contact::query()->create(['workspace_id' => $this->ws(), 'external_id' => 'own_1', 'username' => 'AI_Vatan']);
        $conversation = Conversation::query()->create(['workspace_id' => $this->ws(), 'channel_id' => $channel->id, 'contact_id' => $own->id, 'status' => 'new']);
        $userRun = AutomationRun::query()->firstOrFail();
        $ownRun = AutomationRun::query()->create(['workspace_id' => $this->ws(), 'rule_id' => $userRun->rule_id, 'rule_version' => 1, 'message_id' => null, 'contact_id' => $own->id, 'mode' => 'live', 'status' => 'success']);
        $outbound = OutboundMessage::query()->create([
            'workspace_id' => $this->ws(), 'channel_id' => $channel->id, 'conversation_id' => $conversation->id, 'contact_id' => $own->id,
            'kind' => 'public_reply', 'target_ref' => 'c_own', 'body' => 'x', 'origin' => 'automation', 'automation_run_id' => $ownRun->id,
            'status' => 'sent', 'idempotency_key' => 'own-test',
        ]);
        PostFlowSession::query()->create(['workspace_id' => $this->ws(), 'campaign_id' => PostFlowSession::query()->value('campaign_id'), 'contact_id' => $own->id, 'conversation_id' => $conversation->id, 'comment_message_id' => 999, 'mode' => 'live', 'stage' => 'awaiting_click', 'expires_at' => now()->addDay()]);

        $migration = require database_path('migrations/2026_10_05_120000_cleanup_smart_instagram_own_account_runs.php');
        $migration->up();
        $migration->up(); // تکرار بی‌اثر است

        $this->assertNull(AutomationRun::query()->find($ownRun->id));
        $this->assertNotNull(AutomationRun::query()->find($userRun->id), 'اجرای کاربر واقعی دست نمی‌خورد');
        $this->assertSame(1, PostFlowSession::query()->count());
        $this->assertNull($outbound->fresh()->automation_run_id, 'سابقه‌ی ارسال حفظ می‌شود');
        $this->assertSame(1, (int) \App\Models\SmartInstagram\AutomationRule::query()->whereKey($userRun->rule_id)->value('runs_count'));
        $this->assertNotNull(Contact::query()->find($own->id));
    }


    public function test_keyword_only_comments_use_rotating_human_variants_without_ai(): void
    {
        config(['smart_instagram.ai.enabled' => true]);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['choices' => [['message' => ['content' => '{"reply":"کاملاً موافقم! حتماً امتحانش کن!"}']]]], 200)]);
        $channel = $this->channel();
        $this->campaign($channel);
        $campaign = \App\Models\SmartInstagram\PostCampaign::query()->firstOrFail();
        $settings = (array) $campaign->settings;
        $settings['reply']['ai_personalize'] = true;
        $settings['reply']['styles'] = ['{name} جان، برات دایرکت کردیم 💌', 'فرستادیمش، دایرکتت رو ببین ✨', 'لینک توی دایرکتته 🎀'];
        app(PostCampaignService::class)->save($campaign->post, [
            'title' => $campaign->title, 'status' => 'active', 'follow_required' => '1', 'public_reply_enabled' => '1', 'dm_enabled' => '1',
            'keywords' => [['keyword' => 'کلاژ', 'match_mode' => 'contains', 'is_active' => '1']], 'settings' => $settings,
        ], $campaign);

        foreach (['u_1' => 'کلاژ', 'u_2' => 'کلاژ لطفا', 'u_3' => 'سلام کلاژ رو میخوام'] as $id => $text) {
            $this->ingest(['type' => 'comment', 'id' => 'c_'.$id, 'sender' => ['id' => $id, 'username' => 'user'.$id], 'text' => $text, 'media_id' => 'reel_1'])->assertOk();
        }

        $replies = OutboundMessage::query()->where('kind', 'public_reply')->orderBy('id')->pluck('body')->all();
        $this->assertSame(['برات دایرکت کردیم 💌', 'فرستادیمش، دایرکتت رو ببین ✨', 'لینک توی دایرکتته 🎀'], $replies, 'چرخشی، بدون تکرار و بدون «دوست عزیز»');
        $this->assertSame(0, \App\Models\SmartInstagram\AiRun::query()->where('purpose', 'comment_reply')->count(), 'برای کامنت کلمه‌ی کلیدی هوش مصنوعی صدا زده نمی‌شود');
    }

    public function test_ai_reply_is_humanized(): void
    {
        $writer = \App\Services\SmartInstagram\Posts\CommentReplyWriter::class;
        $this->assertSame('کاملاً موافقم! امتحانش کن 😍', $writer::humanize('کاملاً موافقم! حتماً امتحانش کن!! 😍✨🎀'));
        $this->assertTrue($writer::isKeywordRequest('لینک لطفاً 🙏', ['لینک']));
        $this->assertFalse($writer::isKeywordRequest('قیمتش چنده؟', ['قیمت']));
    }

    public function test_card_uses_square_jpeg_built_from_post_cover(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public', ['url' => 'https://aivatan.test/storage']);
        $img = imagecreatetruecolor(540, 960);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 30, 30));
        ob_start(); imagewebp($img); $webp = ob_get_clean();
        \Illuminate\Support\Facades\Storage::disk('public')->put('smart-instagram/posts/1/cover.webp', $webp);

        $channel = $this->channel();
        $this->campaign($channel);
        Post::query()->update(['cover_path' => 'smart-instagram/posts/1/cover.webp', 'cover_source_url' => 'https://scontent.cdninstagram.com/x.jpg']);
        $campaign = \App\Models\SmartInstagram\PostCampaign::query()->with('post')->firstOrFail();

        $card = app(\App\Services\SmartInstagram\Posts\PostFlowService::class)->cardMessage($campaign);
        $url = data_get($card, 'message.attachment.payload.elements.0.image_url');
        $this->assertStringEndsWith('.jpg', $url);
        $this->assertStringContainsString('smart-instagram/cards/post-', $url);
        $files = \Illuminate\Support\Facades\Storage::disk('public')->files('smart-instagram/cards');
        $this->assertCount(1, $files);
        [$w, $h, $type] = getimagesizefromstring(\Illuminate\Support\Facades\Storage::disk('public')->get($files[0]));
        $this->assertSame([1080, 1080, IMAGETYPE_JPEG], [$w, $h, $type]);

        // بار دوم دوباره ساخته نمی‌شود
        app(\App\Services\SmartInstagram\Posts\PostFlowService::class)->cardMessage($campaign);
        $this->assertCount(1, \Illuminate\Support\Facades\Storage::disk('public')->files('smart-instagram/cards'));
    }

    public function test_collage_text_migration_only_replaces_untouched_texts_and_adds_version(): void
    {
        $channel = $this->channel();
        $this->campaign($channel);
        $campaign = \App\Models\SmartInstagram\PostCampaign::query()->firstOrFail();
        $settings = (array) $campaign->settings;
        $settings['card']['intro_text'] = 'کلاژ سه‌تایی رو با ما بساز!';
        $settings['card']['title'] = 'ساخت کلاژ سه‌تایی';
        $settings['card']['subtitle'] = 'متن دلخواه مدیر';
        $campaign->forceFill(['settings' => $settings])->save();
        $version = (int) $campaign->version;

        $migration = require database_path('migrations/2026_10_06_100000_improve_smart_instagram_collage_card_texts.php');
        $migration->up();
        $migration->up();

        $fresh = $campaign->fresh();
        $this->assertSame('{name} جان، اینم لینکی که خواستی 👇', data_get($fresh->settings, 'card.intro_text'));
        $this->assertSame('کلاژ سه‌تایی با عکس خودت', data_get($fresh->settings, 'card.title'));
        $this->assertSame('متن دلخواه مدیر', data_get($fresh->settings, 'card.subtitle'), 'متن ویرایش‌شده‌ی مدیر دست نمی‌خورد');
        $this->assertSame($version + 1, (int) $fresh->version);
        $this->assertTrue(\App\Models\SmartInstagram\PostCampaignVersion::query()->where('campaign_id', $campaign->id)->where('version', $version + 1)->exists());
    }


    public function test_button_template_falls_back_to_quick_replies_with_same_text(): void
    {
        $message = app(\App\Services\SmartInstagram\Posts\PostFlowService::class)->buttonMessage(null, 'برای دریافت روی دکمه بزن', [
            ['title' => 'مشاهده پیج', 'url' => 'https://www.instagram.com/ai_vatan'],
            ['title' => 'فالو کردم ✅', 'payload' => 'SIF:followed:1'],
        ]);
        $quick = \App\Services\SmartInstagram\Gateways\RichMessageFallback::quickReplies($message);

        $this->assertSame("برای دریافت روی دکمه بزن\nhttps://www.instagram.com/ai_vatan", $quick['text']);
        $this->assertSame([['content_type' => 'text', 'title' => 'فالو کردم ✅', 'payload' => 'SIF:followed:1']], $quick['quick_replies']);
        $this->assertNull(\App\Services\SmartInstagram\Gateways\RichMessageFallback::quickReplies(['text' => 'x']));
    }

    public function test_name_placeholder_removal_leaves_no_orphan_punctuation(): void
    {
        $resolver = app(ContactNameResolver::class);
        $contact = new Contact(['username' => 'shaygangp', 'display_name' => null]);

        $this->assertSame('خوشحالم که به کلاژ علاقه‌مندی!', $resolver->render('{name} عزیز، خوشحالم که به کلاژ علاقه‌مندی!', $contact));
        $this->assertSame("سلام 👋\nبرای دریافت روی دکمه بزن.", $resolver->render("سلام {name} جان 👋\nبرای دریافت روی دکمه بزن.", $contact));
        $this->assertSame('سلام، خوش اومدی!', $resolver->render('سلام {name}، خوش اومدی!', $contact));
        $this->assertSame('محسن جان، خوش اومدی!', $resolver->render('{name} جان، خوش اومدی!', new Contact(['username' => 'mohsen.shop'])));
    }
}
