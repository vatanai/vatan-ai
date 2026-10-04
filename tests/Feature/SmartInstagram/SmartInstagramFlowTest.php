<?php

namespace Tests\Feature\SmartInstagram;

use App\Models\Admin;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\AiSuggestion;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Deal;
use App\Models\SmartInstagram\KnowledgeSource;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\OutboundMessage;
use App\Models\SmartInstagram\WorkspaceMember;
use App\Services\SmartInstagram\Ai\KnowledgeService;
use App\Services\SmartInstagram\Ai\SalesAssistant;
use App\Services\SmartInstagram\Automation\AutomationTemplates;
use App\Services\SmartInstagram\ComposioMessageMapper;
use App\Services\SmartInstagram\OutboundService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmartInstagramFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.meta.app_secret' => 'meta-test-secret',
            'smart_instagram.ingest.secret' => 'ingest-test-secret',
            'smart_instagram.ai.enabled' => false,
            'services.openrouter.api_key' => 'test-key',
        ]);
    }

    private function admin(string $role = 'leader', string $email = 'si@example.test'): Admin
    {
        return Admin::query()->create(['name' => 'ادمین '.$email, 'email' => $email, 'password' => 'password', 'role' => $role, 'is_active' => true]);
    }

    private function sandboxChannel(bool $outbound = true): Channel
    {
        return Channel::query()->create([
            'workspace_id' => app(WorkspaceContext::class)->id(), 'gateway' => 'sandbox', 'name' => 'آزمایشی',
            'external_account_id' => 'acct_1', 'status' => 'connected', 'outbound_enabled' => $outbound,
        ]);
    }

    private function postMetaWebhook(array $payload)
    {
        $raw = json_encode($payload);

        return $this->call('POST', '/webhooks/meta', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $raw, 'meta-test-secret'),
        ], $raw);
    }

    private function postIngest(array $payload, ?int $timestamp = null, ?string $secret = 'ingest-test-secret')
    {
        $raw = json_encode($payload);
        $timestamp ??= time();

        return $this->call('POST', '/webhooks/smart-instagram/ingest', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_VATAN_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_VATAN_SIGNATURE' => 'sha256='.hash_hmac('sha256', $timestamp.'.'.$raw, (string) $secret),
        ], $raw);
    }

    public function test_meta_comment_and_dm_webhooks_build_contact_conversation_and_timeline_idempotently(): void
    {
        $this->sandboxChannel(false)->forceFill(['gateway' => 'meta'])->save();
        $comment = ['entry' => [['id' => 'acct_1', 'changes' => [['field' => 'comments', 'value' => [
            'id' => '17890', 'text' => 'قیمتش چنده؟', 'from' => ['id' => 'igsid_1', 'username' => 'leather_shop'], 'media' => ['id' => 'reel_99'],
        ]]]]]];

        $this->postMetaWebhook($comment)->assertOk()->assertJson(['stored' => 1]);
        $this->postMetaWebhook($comment)->assertOk()->assertJson(['stored' => 0]);

        $dm = ['entry' => [['id' => 'acct_1', 'messaging' => [[
            'sender' => ['id' => 'igsid_1'], 'recipient' => ['id' => 'acct_1'], 'timestamp' => now()->getTimestampMs(),
            'message' => ['mid' => 'mid_1', 'text' => 'سلام، برای کیف چرم عکس می‌خوام', 'attachments' => [['type' => 'audio', 'payload' => ['url' => 'https://cdn.example.test/a.mp4']]]],
        ]]]]];
        Http::fake(['cdn.example.test/*' => Http::response('AUDIO', 200, ['Content-Type' => 'audio/mp4'])]);
        $this->postMetaWebhook($dm)->assertOk()->assertJson(['stored' => 1]);

        $this->assertSame(1, Contact::query()->count());
        $contact = Contact::query()->first();
        $this->assertSame('leather_shop', $contact->username);
        $this->assertSame('comment', $contact->first_source);
        $this->assertSame(1, Conversation::query()->count());
        $this->assertSame(2, Message::query()->count());
        $this->assertSame('reel_99', Message::query()->where('source_type', 'comment')->value('source_ref'));
        $this->assertSame('17890', data_get(Message::query()->where('source_type', 'comment')->first()->meta, 'comment_id'));
        $this->assertSame(2, MarketingEvent::query()->where('processing_status', 'processed')->count());

        $dmMessage = Message::query()->where('external_id', 'mid_1')->first();
        $this->assertSame('audio', $dmMessage->message_type);
        $attachment = $dmMessage->attachments()->first();
        $this->assertSame('stored', $attachment->fetch_status, (string) $attachment->fetch_error);
        $this->assertNull($attachment->remote_url);

        // پردازش دوباره‌ی همان رویداد رکورد تکراری نمی‌سازد
        $event = MarketingEvent::query()->first();
        $event->forceFill(['processing_status' => 'received'])->save();
        app(\App\Services\SmartInstagram\InboxIngestService::class)->process($event->fresh());
        $this->assertSame(2, Message::query()->count());
        $this->assertSame('duplicate', $event->fresh()->processing_status);
    }

    public function test_signed_ingest_rejects_bad_signature_and_stale_timestamp(): void
    {
        $payload = ['type' => 'dm', 'id' => 'n8n_1', 'sender' => ['id' => 'u_9', 'username' => 'gold_house'], 'text' => 'سلام'];

        $this->postIngest($payload, null, 'wrong-secret')->assertStatus(401);
        $this->postIngest($payload, time() - 3600)->assertStatus(401);
        $this->postIngest($payload)->assertOk()->assertJson(['stored' => 1]);
        $this->postIngest($payload)->assertOk()->assertJson(['stored' => 0, 'duplicates' => 1]);

        $this->assertSame('gold_house', Contact::query()->value('username'));
        $this->assertSame('سلام', Message::query()->value('body'));
    }

    public function test_send_policy_blocks_when_outbound_disabled_and_enforces_window_and_single_private_reply(): void
    {
        $channel = $this->sandboxChannel();
        $ws = app(WorkspaceContext::class)->id();
        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'c1']);
        $conversation = Conversation::query()->create(['workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id, 'status' => 'unanswered', 'last_inbound_at' => now()->subHour()]);
        Message::query()->create(['workspace_id' => $ws, 'conversation_id' => $conversation->id, 'external_id' => 'c_55', 'direction' => 'in', 'source_type' => 'comment', 'body' => 'قیمت', 'meta' => ['comment_id' => '55'], 'occurred_at' => now()->subHour()]);
        $service = app(OutboundService::class);

        config(['smart_instagram.outbound_enabled' => false]);
        $blocked = $service->queue($conversation, 'سلام', 'human');
        $this->assertSame('blocked', $blocked->status);
        $this->assertStringContainsString('کلید اصلی', $blocked->policy_reason);

        config(['smart_instagram.outbound_enabled' => true]);
        $sent = $service->queue($conversation->fresh(), 'سلام، خوش آمدید', 'human');
        $this->assertSame('sent', $sent->fresh()->status);
        $this->assertSame('waiting_customer', $conversation->fresh()->status);
        $this->assertNotNull($conversation->fresh()->first_response_seconds);

        $this->assertSame('blocked', $service->queue($conversation->fresh(), 'سلام، خوش آمدید', 'human')->status, 'متن تکراری');

        $this->assertSame('sent', $service->queue($conversation->fresh(), 'جزئیات در دایرکت', 'human', 'private_reply', ['target_ref' => '55'])->fresh()->status);
        $second = $service->queue($conversation->fresh(), 'باز هم', 'human', 'private_reply', ['target_ref' => '55']);
        $this->assertSame('blocked', $second->status);

        $conversation->forceFill(['last_inbound_at' => now()->subDays(2)])->save();
        $this->assertStringContainsString('۲۴ساعته', $service->queue($conversation->fresh(), 'دیر', 'human')->policy_reason);

        $conversation->forceFill(['last_inbound_at' => now(), 'needs_human' => true])->save();
        $this->assertSame('blocked', $service->queue($conversation->fresh(), 'پاسخ خودکار', 'ai')->status);

        $conversation->forceFill(['needs_human' => true, 'ai_paused' => false])->save();
        $this->assertNotSame('blocked', $service->queue($conversation->fresh(), 'پاسخ کمپین', 'automation', 'dm', ['allow_human_lock' => true])->status);

        $conversation->forceFill(['ai_paused' => true])->save();
        $paused = $service->queue($conversation->fresh(), 'پاسخ در توقف دستی', 'automation', 'dm', ['allow_human_lock' => true]);
        $this->assertSame('blocked', $paused->status);
        $this->assertStringContainsString('دستی متوقف', (string) $paused->policy_reason);
    }

    public function test_comment_replies_do_not_consume_the_direct_message_automation_cap(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $channel = $this->sandboxChannel();
        $ws = app(WorkspaceContext::class)->id();
        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'c-cap']);
        $conversation = Conversation::query()->create([
            'workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id,
            'status' => 'unanswered', 'last_inbound_at' => now(),
        ]);
        foreach (['c1', 'c2', 'c3'] as $commentId) {
            Message::query()->create([
                'workspace_id' => $ws, 'conversation_id' => $conversation->id, 'external_id' => $commentId,
                'direction' => 'in', 'source_type' => 'comment', 'body' => 'سلفی',
                'meta' => ['comment_id' => $commentId], 'occurred_at' => now(),
            ]);
        }

        $service = app(OutboundService::class);
        foreach (['c1', 'c2', 'c3'] as $commentId) {
            $reply = $service->queue($conversation->fresh(), 'اطلاعات کامنت '.$commentId, 'automation', 'public_reply', ['target_ref' => $commentId]);
            $this->assertNotSame('blocked', $reply->status);
        }
        $this->assertSame(3, OutboundMessage::query()->where('kind', 'public_reply')->count());

        $dm = $service->queue($conversation->fresh(), 'دایرکت خودکار', 'automation', 'dm');
        $this->assertNotSame('blocked', $dm->status);
        $this->assertSame(1, OutboundMessage::query()->where('kind', 'dm')->count());
    }

    public function test_composio_mapper_puts_phone_sent_messages_in_customer_conversation(): void
    {
        $channel = $this->sandboxChannel();
        $channel->forceFill(['username' => 'ai_vatan', 'external_account_id' => 'me', 'settings' => ['composio_instagram_user_id' => 'me']])->save();

        $payload = app(ComposioMessageMapper::class)->map([
            'id' => 'ig_out_1',
            'created_time' => now()->toIso8601String(),
            'from' => ['id' => 'own_ig', 'username' => 'ai_vatan'],
            'to' => ['data' => [['id' => 'customer_1', 'username' => 'redruby.dubai']]],
            'message' => '',
            'attachments' => ['data' => [['image_data' => ['url' => 'https://cdn.example.test/card.jpg']]]],
        ], 'conversation_1', $channel);

        $this->assertSame('out', $payload['direction']);
        $this->assertSame('me', $payload['account_id']);
        $this->assertSame('customer_1', $payload['sender']['id']);
        $this->assertSame('redruby.dubai', $payload['sender']['username']);
        $this->assertSame('image', $payload['attachments'][0]['type']);
        $this->assertSame('https://cdn.example.test/card.jpg', $payload['attachments'][0]['url']);
    }

    public function test_price_comment_template_runs_safely_live_and_in_test_mode(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $this->sandboxChannel();
        $ws = app(WorkspaceContext::class)->id();
        $template = AutomationTemplates::all()['price_comment'];
        $rule = AutomationRule::query()->create([
            'workspace_id' => $ws, 'name' => $template['name'], 'trigger' => $template['trigger'], 'keywords' => $template['keywords'],
            'match_mode' => 'contains', 'actions' => $template['actions'], 'guards' => $template['guards'], 'status' => 'active',
        ]);

        $this->postIngest(['type' => 'comment', 'id' => '900', 'sender' => ['id' => 'buyer_1', 'username' => 'bag_store'], 'text' => 'قيمت چنده؟', 'media_id' => 'reel_1'])->assertOk();

        $this->assertSame(1, AutomationRun::query()->where('status', 'success')->count());
        $this->assertSame(2, OutboundMessage::query()->where('status', 'sent')->count(), 'پاسخ عمومی + خصوصی');
        $this->assertTrue(Contact::query()->first()->tags()->where('name', 'پرسش قیمت')->exists());
        $this->assertSame(1, Deal::query()->count());
        $this->assertSame('reel_1', Deal::query()->value('source_ref'));

        // کامنت دوم همان مشتری: فاصله‌ی تکرار رعایت می‌شود
        $this->postIngest(['type' => 'comment', 'id' => '901', 'sender' => ['id' => 'buyer_1'], 'text' => 'قیمت؟', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, AutomationRun::query()->where('status', 'skipped')->count());
        $this->assertSame(2, OutboundMessage::query()->count());

        // حالت آزمایشی: ثبت می‌شود ولی هیچ پیامی نمی‌رود
        $rule->forceFill(['status' => 'test'])->save();
        $this->postIngest(['type' => 'comment', 'id' => '902', 'sender' => ['id' => 'buyer_2'], 'text' => 'قیمت', 'media_id' => 'reel_1'])->assertOk();
        $this->assertSame(1, AutomationRun::query()->where('status', 'simulated')->count());
        $this->assertSame(2, OutboundMessage::query()->count());
    }

    public function test_sensitive_message_flags_conversation_and_stops_automated_messaging(): void
    {
        config(['smart_instagram.outbound_enabled' => true]);
        $this->sandboxChannel();
        $ws = app(WorkspaceContext::class)->id();
        AutomationRule::query()->create([
            'workspace_id' => $ws, 'name' => 'خوش‌آمد', 'trigger' => 'first_message', 'actions' => [['type' => 'send_dm', 'text' => 'سلام!']],
            'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 0, 'stop_on_sensitive' => true], 'status' => 'active',
        ]);

        $this->postIngest(['type' => 'dm', 'id' => 'm1', 'sender' => ['id' => 'angry_1'], 'text' => 'می‌خوام شکایت کنم، پولم برنگشت'])->assertOk();

        $conversation = Conversation::query()->first();
        $this->assertTrue($conversation->needs_human);
        $this->assertSame('high', $conversation->priority);
        $this->assertSame(0, OutboundMessage::query()->count());
    }

    public function test_ai_assistant_uses_only_approved_knowledge_and_flags_unverified_prices(): void
    {
        config(['smart_instagram.ai.enabled' => true]);
        $ws = app(WorkspaceContext::class)->id();
        $knowledge = app(KnowledgeService::class);
        $knowledge->create(['title' => 'تعرفه', 'category' => 'pricing', 'content' => 'قیمت عکس محصول پوشاک: هر عکس ۲۵۰۰۰۰ تومان', 'status' => 'approved']);
        $knowledge->create(['title' => 'پیش‌نویس', 'category' => 'pricing', 'content' => 'قیمت محرمانه‌ی تأییدنشده ۹۹۹۹', 'status' => 'draft']);

        $channel = $this->sandboxChannel(false);
        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'ai_1', 'username' => 'dress_shop']);
        $conversation = Conversation::query()->create(['workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id, 'status' => 'new', 'last_inbound_at' => now()]);
        $message = Message::query()->create(['workspace_id' => $ws, 'conversation_id' => $conversation->id, 'direction' => 'in', 'source_type' => 'dm', 'body' => 'قیمت عکس پوشاک چنده؟', 'occurred_at' => now()]);

        $captured = null;
        Http::fake(['*/chat/completions' => function ($request) use (&$captured) {
            $captured = $request->data();

            return Http::response(['model' => 'test/model', 'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 20], 'choices' => [['message' => ['content' => json_encode([
                'intent' => 'price', 'stage' => 'evaluating', 'urgency' => 'normal', 'risk' => 'low', 'needs_human' => false,
                'reason' => 'قیمت در دانش هست', 'summary' => 'مشتری قیمت عکس پوشاک می‌خواهد', 'next_action' => 'ارسال نمونه',
                'reply' => 'هر عکس ۲۵۰,۰۰۰ تومان است؛ با تخفیف ۱۰۰,۰۰۰ تومان!', 'confidence' => 0.9, 'used_sources' => ['S1'],
                'missing_info' => ['زمان تحویل'], 'lead_score' => 75, 'score_reason' => 'نیاز روشن', 'industry' => 'پوشاک',
            ])]]]]);
        }]);

        $suggestion = app(SalesAssistant::class)->analyze($conversation, $message->id);

        $this->assertNotNull($suggestion);
        $userPrompt = collect($captured['messages'])->firstWhere('role', 'user')['content'];
        $this->assertStringContainsString('۲۵۰۰۰۰', $userPrompt);
        $this->assertStringNotContainsString('۹۹۹۹', $userPrompt, 'دانش تأییدنشده نباید به مدل برسد');
        $this->assertContains('unverified_numbers', $suggestion->flags, '۱۰۰,۰۰۰ در دانش نیست');
        $this->assertLessThanOrEqual(0.4, $suggestion->confidence);
        $conversation->refresh();
        $this->assertSame('price', $conversation->intent);
        $this->assertSame('hot', $contact->fresh()->lead_status);
        $this->assertSame('پوشاک', $contact->fresh()->industry);

        // بازبینی: استفاده با ویرایش و یادگیری
        $this->actingAs($this->admin(), 'admin');
        config(['smart_instagram.outbound_enabled' => false]);
        $this->post(route('admin.smart-instagram.inbox.reply', $conversation), ['body' => 'هر عکس ۲۵۰ هزار تومان است.', 'suggestion_id' => $suggestion->id])->assertRedirect();
        $this->assertSame('edited', $suggestion->fresh()->status);
        $this->post(route('admin.smart-instagram.suggestions.review', $suggestion), ['decision' => 'learn'])->assertRedirect();
        $this->assertTrue(KnowledgeSource::query()->where('category', 'approved_reply')->where('status', 'approved')->exists());
    }

    public function test_knowledge_upload_extracts_docx_and_csv_and_edit_requires_reapproval(): void
    {
        $this->actingAs($this->admin(), 'admin');

        $docx = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new \ZipArchive();
        $zip->open($docx, \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>ارسال رایگان برای سفارش بالای دو میلیون</w:t></w:r></w:p><w:p><w:r><w:t>تحویل سه روز کاری</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        $this->post(route('admin.smart-instagram.knowledge.sources.store'), [
            'title' => 'سیاست ارسال', 'category' => 'policy', 'approve_now' => '1', 'ai_allowed' => '1',
            'file' => new UploadedFile($docx, 'policy.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true),
        ])->assertRedirect();
        $source = KnowledgeSource::query()->where('title', 'سیاست ارسال')->firstOrFail();
        $this->assertStringContainsString('تحویل سه روز کاری', $source->content);
        $this->assertSame('approved', $source->status);
        $this->assertGreaterThan(0, $source->chunk_count);

        $csv = UploadedFile::fake()->createWithContent('prices.csv', "خدمت,قیمت\nعکس پوشاک,250000\nویدیو,900000\n");
        $this->post(route('admin.smart-instagram.knowledge.sources.store'), ['title' => 'قیمت‌ها', 'category' => 'pricing', 'file' => $csv])->assertRedirect();
        $this->assertStringContainsString('خدمت: ویدیو | قیمت: 900000', KnowledgeSource::query()->where('title', 'قیمت‌ها')->value('content'));

        $this->put(route('admin.smart-instagram.knowledge.sources.update', $source), ['title' => 'سیاست ارسال', 'category' => 'policy', 'content' => 'تحویل پنج روز کاری', 'ai_allowed' => '1'])->assertRedirect();
        $source->refresh();
        $this->assertSame('draft', $source->status);
        $this->assertSame(2, $source->version);

        $results = app(\App\Services\SmartInstagram\Ai\KnowledgeRetriever::class)->search(app(WorkspaceContext::class)->id(), 'تحویل چند روز؟');
        $this->assertTrue($results->isEmpty(), 'منبع ویرایش‌شده تا تأیید دوباره قابل بازیابی نیست');
    }

    public function test_prompt_file_upload_creates_new_active_profile_version(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $file = UploadedFile::fake()->createWithContent('persona.md', "تو مشاور فروش لوکس طلا هستی و با لحن فاخر و کوتاه پاسخ می‌دهی.");

        $this->post(route('admin.smart-instagram.knowledge.profile.save'), [
            'prompt_file' => $file, 'prompt_file_mode' => 'replace', 'tone' => 'luxury', 'reply_length' => 'short',
            'bot_disclosure' => 'when_asked', 'min_confidence' => 0.7, 'escalation_keywords' => "شکایت\nبازگشت وجه",
        ])->assertRedirect();

        $active = \App\Models\SmartInstagram\AiProfile::query()->where('is_active', true)->get();
        $this->assertCount(1, $active);
        $this->assertSame(2, $active->first()->version);
        $this->assertStringContainsString('مشاور فروش لوکس طلا', $active->first()->persona_prompt);
        $this->assertSame(['شکایت', 'بازگشت وجه'], $active->first()->escalation_keywords);
    }

    public function test_playground_returns_structured_answer(): void
    {
        config(['smart_instagram.ai.enabled' => true]);
        $this->actingAs($this->admin(), 'admin');
        Http::fake(['*/chat/completions' => Http::response(['model' => 'm', 'choices' => [['message' => ['content' => json_encode(['intent' => 'greeting', 'reply' => 'سلام! چطور کمکتون کنم؟', 'confidence' => 0.8, 'needs_human' => false])]]]])]);

        $this->postJson(route('admin.smart-instagram.knowledge.playground'), ['message' => 'سلام'])
            ->assertOk()->assertJson(['ok' => true, 'output' => ['intent' => 'greeting', 'reply' => 'سلام! چطور کمکتون کنم؟']]);
    }

    public function test_roles_limit_operator_and_viewer(): void
    {
        $ws = app(WorkspaceContext::class)->id();
        $channel = $this->sandboxChannel(false);
        $owner = $this->admin('leader', 'owner@example.test');
        $operator = $this->admin('admin', 'operator@example.test');
        $viewer = $this->admin('admin', 'viewer@example.test');
        WorkspaceMember::query()->create(['workspace_id' => $ws, 'admin_id' => $viewer->id, 'role' => 'viewer']);

        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'r1']);
        $mine = Conversation::query()->create(['workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id, 'status' => 'assigned', 'assigned_admin_id' => $owner->id]);

        $this->actingAs($operator, 'admin');
        $this->get(route('admin.smart-instagram.inbox.show', $mine))->assertForbidden();
        $this->get(route('admin.smart-instagram.connections'))->assertOk()->assertDontSee('ثبت نقش');
        $this->post(route('admin.smart-instagram.connections.sandbox'))->assertForbidden();

        $this->actingAs($viewer, 'admin');
        $this->get(route('admin.smart-instagram.inbox.show', $mine))->assertOk()->assertDontSee('ثبت و ارسال');
        $this->post(route('admin.smart-instagram.inbox.reply', $mine), ['body' => 'x'])->assertForbidden();
    }

    public function test_contact_erase_removes_all_related_data(): void
    {
        $ws = app(WorkspaceContext::class)->id();
        $channel = $this->sandboxChannel(false);
        $contact = Contact::query()->create(['workspace_id' => $ws, 'external_id' => 'del_1']);
        $conversation = Conversation::query()->create(['workspace_id' => $ws, 'channel_id' => $channel->id, 'contact_id' => $contact->id]);
        Message::query()->create(['workspace_id' => $ws, 'conversation_id' => $conversation->id, 'direction' => 'in', 'body' => 'x', 'occurred_at' => now()]);
        AiSuggestion::query()->create(['workspace_id' => $ws, 'conversation_id' => $conversation->id, 'body' => 'y']);

        $this->actingAs($this->admin(), 'admin');
        $this->delete(route('admin.smart-instagram.contacts.destroy', $contact))->assertRedirect(route('admin.smart-instagram.contacts.index'));

        $this->assertSame(0, Contact::query()->count());
        $this->assertSame(0, Conversation::query()->count());
        $this->assertSame(0, Message::query()->count());
        $this->assertSame(0, AiSuggestion::query()->count());
    }

    public function test_automation_form_validation_and_versioning(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $base = [
            'name' => 'قانون', 'trigger' => 'dm_keyword', 'keywords' => '', 'match_mode' => 'contains', 'status' => 'draft',
            'actions' => [['type' => 'send_dm', 'text' => 'سلام {name}']], 'guards' => ['max_per_contact_per_day' => 1, 'cooldown_minutes' => 10, 'stop_on_sensitive' => 1],
        ];
        $this->post(route('admin.smart-instagram.automations.store'), $base)->assertSessionHasErrors('keywords');

        $this->post(route('admin.smart-instagram.automations.store'), ['keywords' => "سلام\nدرود"] + $base)->assertRedirect();
        $rule = AutomationRule::query()->firstOrFail();
        $this->assertSame(['سلام', 'درود'], $rule->keywords);
        $this->assertSame(1, $rule->version);

        $this->put(route('admin.smart-instagram.automations.update', $rule), ['keywords' => 'سلام'] + $base)->assertRedirect();
        $this->assertSame(2, $rule->fresh()->version);

        $this->postJson(route('admin.smart-instagram.automations.simulate', $rule), ['text' => 'سلام وقت بخیر', 'source' => 'dm'])
            ->assertOk()->assertJson(['matched' => true]);
        $this->postJson(route('admin.smart-instagram.automations.simulate', $rule), ['text' => 'قیمت', 'source' => 'dm'])
            ->assertOk()->assertJson(['matched' => false]);
    }
}
