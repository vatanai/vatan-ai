<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\Product;
use App\Models\SmartInstagram\AutomationRun;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\PostFlowSession;
use App\Models\SmartInstagram\Tag;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use App\Services\SmartInstagram\Gateways\RichInstagramGateway;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\OutboundService;
use App\Services\SmartInstagram\PersianText;
use Illuminate\Database\QueryException;

/**
 * جریان دایرکت «ثبت پست»:
 *  کامنت با کلمه‌ی کلیدی ← پاسخ خصوصی (پیام آغاز + دکمه‌ی «ارسال لینک»)
 *  کلیک/پاسخ مشتری ← بررسی فالو (is_user_follow_business)
 *     فالو کرده ← کارت چنددکمه‌ای (+ پیام قبل و بعد)
 *     فالو نکرده ← پیام «اول فالو کن» + دکمه‌ی «فالو کردم» (با سقف تکرار)
 *     نامشخص ← طبق تنظیم: ارسال کارت یا یک‌بار درخواست فالو
 *  بدون فالو اجباری و حالت «کارت مستقیم»: کارت در همان پاسخ خصوصی.
 * همه‌ی ارسال‌ها از OutboundService/SendPolicy عبور می‌کنند (پنجره‌ی متا، idempotency، قفل انسانی).
 */
class PostFlowService
{
    public const SESSION_DAYS = 7;

    public function __construct(
        private readonly OutboundService $outbound,
        private readonly GatewayManager $gateways,
        private readonly OperationLogger $logger,
    ) {
    }

    /** از موتور اتومیشن (اقدام post_flow) پس از تطبیق کامنت صدا زده می‌شود. */
    public function start(PostCampaign $campaign, Message $comment, Conversation $conversation, AutomationRun $run, string $key, bool $allowHumanLock): array
    {
        $commentId = (string) data_get($comment->meta, 'comment_id', '');
        if ($commentId === '') {
            return ['ok' => false, 'result' => 'این پیام کامنت نیست'];
        }
        $settings = (array) $campaign->settings;
        $contact = $conversation->contact;
        $delay = (int) data_get($settings, 'limits.dm_delay_seconds', 0);
        $direct = !$campaign->follow_required && data_get($settings, 'dm.mode') === 'direct_card';

        if ($direct) {
            $card = $this->cardMessage($campaign, $contact);
            if (!$card['ok']) {
                return ['ok' => false, 'result' => $card['error']];
            }
            $out = $this->outbound->queue($conversation, $card['title'], 'automation', 'private_reply', [
                'target_ref' => $commentId, 'automation_run_id' => $run->id, 'idempotency_key' => $key,
                'message_payload' => ['message' => $card['message'], 'fallback_text' => $card['fallback']],
                'allow_human_lock' => $allowHumanLock, 'delay_seconds' => $delay,
            ]);
            try {
                $this->openSession($campaign, $comment, $conversation, 'completed', $run->id);
            } catch (QueryException) {
                // همان کامنت قبلاً ثبت شده؛ idempotency ارسال را هم outbound تضمین می‌کند.
            }

            return $this->result($out, 'کارت دایرکت در پاسخ خصوصی در صف ارسال قرار گرفت');
        }

        try {
            $this->openSession($campaign, $comment, $conversation, 'awaiting_click', $run->id);
        } catch (QueryException) {
            return ['ok' => true, 'result' => 'جریان دایرکت این کامنت قبلاً شروع شده است'];
        }

        $text = $this->render((string) data_get($settings, 'dm.opening_text', ''), $contact);
        $button = (string) data_get($settings, 'dm.opening_button', 'ارسال لینک');
        $out = $this->outbound->queue($conversation, $text, 'automation', 'private_reply', [
            'target_ref' => $commentId, 'automation_run_id' => $run->id, 'idempotency_key' => $key,
            'message_payload' => [
                'message' => ['text' => $text, 'quick_replies' => [['content_type' => 'text', 'title' => $button, 'payload' => 'SIF:open:'.$campaign->id]]],
                'fallback_text' => $text."\n\n(برای دریافت، کلمه‌ی «{$button}» را بفرستید)",
            ],
            'allow_human_lock' => $allowHumanLock, 'delay_seconds' => $delay,
        ]);

        return $this->result($out, 'پیام آغاز دایرکت با دکمه‌ی «'.$button.'» در صف ارسال قرار گرفت');
    }

    /**
     * پاسخ/کلیک مشتری در دایرکت. اگر به جریان «ثبت پست» مربوط بود، مصرف می‌شود (true)
     * تا قوانین عمومی دایرکت (مثل خوش‌آمد) دوباره اجرا نشوند.
     */
    public function handleReply(Message $message): bool
    {
        if (!$message->isInbound() || $message->is_internal_note || $message->source_type === 'comment') {
            return false;
        }
        $conversation = $message->conversation()->with('contact', 'channel')->first();
        if (!$conversation) {
            return false;
        }

        if ($this->handleButtonPostback($message, $conversation)) {
            return true;
        }

        $session = PostFlowSession::query()
            ->where('contact_id', $conversation->contact_id)
            ->whereIn('stage', ['awaiting_click', 'awaiting_follow'])
            ->where('expires_at', '>', now())
            ->with('campaign.post')
            ->latest('id')->first();
        if (!$session || !$session->campaign || !in_array($session->campaign->status, ['active', 'test'], true)) {
            return false;
        }

        $campaign = $session->campaign;
        $contact = $conversation->contact;
        if (!$campaign->follow_required) {
            $this->deliverCard($session, $campaign, $conversation, $message);

            return true;
        }

        $status = $this->followStatus($conversation);
        $session->follow_checks++;
        $session->follow_status = $status === null ? 'unknown' : ($status ? 'following' : 'not_following');

        if ($status === true || ($status === null && (data_get($campaign->settings, 'follow.unknown_policy') === 'send' || $session->stage === 'awaiting_follow'))) {
            if ($status === null) {
                $this->logger->log('post_flow.follow_unknown', 'وضعیت فالو از API دریافت نشد؛ کارت طبق تنظیم ارسال شد.', $session, [], 'warning');
            }
            $session->save();
            $this->deliverCard($session, $campaign, $conversation, $message);
            if ($status === true) {
                $this->tag($contact, 'فالوور پیج');
            }

            return true;
        }

        $max = (int) data_get($campaign->settings, 'follow.max_checks', 3);
        if ($session->follow_checks <= $max) {
            $text = $this->render((string) data_get($campaign->settings, $session->stage === 'awaiting_follow' ? 'follow.retry_text' : 'follow.text', ''), $contact);
            $button = (string) data_get($campaign->settings, 'follow.button', 'فالو کردم ✅');
            $username = (string) ($conversation->channel?->username ?? '');
            $profileLine = $username !== '' ? "\ninstagram.com/{$username}" : '';
            $this->outbound->queue($conversation, $text, 'automation', 'dm', [
                'idempotency_key' => 'postflow:'.$session->id.':follow:'.$session->follow_checks,
                'automation_run_id' => $session->automation_run_id,
                'message_payload' => [
                    'message' => ['text' => $text.$profileLine, 'quick_replies' => [['content_type' => 'text', 'title' => $button, 'payload' => 'SIF:followed:'.$session->id]]],
                    'fallback_text' => $text.$profileLine,
                ],
                'user_initiated' => true,
            ]);
        }
        $session->stage = 'awaiting_follow';
        $session->save();

        return true;
    }

    /** پیش‌نمایش ساختار نهایی کارت (برای آزمون و نمایش). */
    public function cardMessage(PostCampaign $campaign, ?Contact $contact = null): array
    {
        $settings = (array) $campaign->settings;
        $card = (array) ($settings['card'] ?? []);
        $post = $campaign->post;
        $product = !empty($card['product_id']) ? Product::query()->whereKey((int) $card['product_id'])->first() : null;
        $productUrl = $product ? route('app.product', $product->route_slug) : null;

        $image = match ($card['image_source'] ?? 'post') {
            'product' => $product?->displayImageUrl(),
            'url' => trim((string) ($card['image_url'] ?? '')),
            'none' => null,
            default => $post?->coverUrl(),
        };
        $title = $this->render(trim((string) ($card['title'] ?? '')) ?: ($product?->name_fa ?: ($post?->shortCaption(60) ?? 'لینک درخواستی شما')), $contact);
        $subtitle = $this->render(trim((string) ($card['subtitle'] ?? '')), $contact);

        $buttons = [];
        $firstUrl = null;
        foreach (array_slice((array) ($card['buttons'] ?? []), 0, 3) as $i => $button) {
            if (($button['type'] ?? 'web_url') === 'postback') {
                $buttons[] = ['type' => 'postback', 'title' => mb_substr((string) $button['label'], 0, 20), 'payload' => 'SIB:'.$campaign->id.':'.$i];
                continue;
            }
            $url = trim((string) ($button['url'] ?? '')) ?: ($productUrl ?? '');
            if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }
            $firstUrl ??= $url;
            $buttons[] = ['type' => 'web_url', 'url' => $url, 'title' => mb_substr((string) $button['label'], 0, 20)];
        }
        if ($buttons === []) {
            return ['ok' => false, 'error' => 'کارت دایرکت حداقل یک دکمه با لینک معتبر لازم دارد.'];
        }

        $element = array_filter([
            'title' => mb_substr($title, 0, 80),
            'subtitle' => $subtitle !== '' ? mb_substr($subtitle, 0, 80) : null,
            'image_url' => $image && filter_var($image, FILTER_VALIDATE_URL) ? $image : null,
            'default_action' => $firstUrl ? ['type' => 'web_url', 'url' => $firstUrl] : null,
            'buttons' => $buttons,
        ], fn ($v) => $v !== null && $v !== '');

        return [
            'ok' => true,
            'title' => $title,
            'message' => ['attachment' => ['type' => 'template', 'payload' => ['template_type' => 'generic', 'elements' => [$element]]]],
            'fallback' => trim($title."\n".($firstUrl ?? '')),
        ];
    }

    private function deliverCard(PostFlowSession $session, PostCampaign $campaign, Conversation $conversation, Message $trigger): void
    {
        $contact = $conversation->contact;
        $settings = (array) $campaign->settings;
        $card = $this->cardMessage($campaign, $contact);
        $base = 'postflow:'.$session->id;

        $intro = $this->render((string) data_get($settings, 'card.intro_text', ''), $contact);
        if ($intro !== '') {
            $this->outbound->queue($conversation, $intro, 'automation', 'dm', ['idempotency_key' => $base.':intro', 'user_initiated' => true, 'automation_run_id' => $session->automation_run_id]);
        }
        if ($card['ok']) {
            $this->outbound->queue($conversation, $card['title'], 'automation', 'dm', [
                'idempotency_key' => $base.':card',
                'automation_run_id' => $session->automation_run_id,
                'message_payload' => ['message' => $card['message'], 'fallback_text' => $card['fallback']],
                'user_initiated' => true,
            ]);
        } else {
            $this->logger->error('post_flow.card_invalid', 'کارت سناریوی «'.$campaign->title.'» قابل ارسال نبود: '.$card['error'], $campaign);
        }
        $after = $this->render((string) data_get($settings, 'card.after_text', ''), $contact);
        if ($after !== '') {
            $this->outbound->queue($conversation, $after, 'automation', 'dm', ['idempotency_key' => $base.':after', 'user_initiated' => true, 'automation_run_id' => $session->automation_run_id]);
        }

        $session->forceFill(['stage' => 'completed', 'completed_at' => now()])->save();
    }

    /** کلیک روی دکمه‌ی «پاسخ سریع/دریافت اطلاعات» کارت → ارسال متن تنظیم‌شده. */
    private function handleButtonPostback(Message $message, Conversation $conversation): bool
    {
        $payload = (string) data_get($message->meta, 'payload', '');
        $campaign = null;
        $index = null;
        if (preg_match('/^SIB:(\d+):(\d)$/', $payload, $m) === 1) {
            $campaign = PostCampaign::query()->where('workspace_id', $conversation->workspace_id)->find((int) $m[1]);
            $index = (int) $m[2];
        } elseif (filled($message->body)) {
            // مسیر همگام‌سازی Composio payload را برنمی‌گرداند؛ متن دکمه با سناریوی تکمیل‌شده‌ی اخیر تطبیق داده می‌شود.
            $recent = PostFlowSession::query()->where('contact_id', $conversation->contact_id)->where('stage', 'completed')
                ->where('completed_at', '>=', now()->subDay())->with('campaign')->latest('id')->first();
            foreach ((array) data_get($recent?->campaign?->settings, 'card.buttons', []) as $i => $button) {
                if (($button['type'] ?? '') === 'postback' && PersianText::normalize((string) $button['label']) === PersianText::normalize((string) $message->body)) {
                    $campaign = $recent->campaign;
                    $index = $i;
                    break;
                }
            }
        }
        if (!$campaign || $index === null) {
            return false;
        }

        $reply = $this->render((string) data_get($campaign->settings, "card.buttons.{$index}.reply_text", ''), $conversation->contact);
        if ($reply !== '') {
            $this->outbound->queue($conversation, $reply, 'automation', 'dm', [
                'idempotency_key' => 'postflow:btn:'.$campaign->id.':'.$index.':'.$message->id,
                'user_initiated' => true,
            ]);
        }

        return true;
    }

    private function followStatus(Conversation $conversation): ?bool
    {
        $channel = $conversation->channel;
        if (!$channel) {
            return null;
        }
        try {
            $gateway = $this->gateways->for($channel);
        } catch (\Throwable) {
            return null;
        }

        return $gateway instanceof RichInstagramGateway ? $gateway->followStatus($channel, (string) $conversation->contact?->external_id) : null;
    }

    private function openSession(PostCampaign $campaign, Message $comment, Conversation $conversation, string $stage, ?int $runId = null): PostFlowSession
    {
        return PostFlowSession::query()->create([
            'workspace_id' => $campaign->workspace_id,
            'campaign_id' => $campaign->id,
            'contact_id' => $conversation->contact_id,
            'conversation_id' => $conversation->id,
            'comment_message_id' => $comment->id,
            'automation_run_id' => $runId,
            'mode' => 'live',
            'stage' => $stage,
            'expires_at' => now()->addDays(self::SESSION_DAYS),
            'completed_at' => $stage === 'completed' ? now() : null,
        ]);
    }

    private function tag(?Contact $contact, string $name): void
    {
        if (!$contact) {
            return;
        }
        $tag = Tag::query()->firstOrCreate(['workspace_id' => $contact->workspace_id, 'name' => $name]);
        $contact->tags()->syncWithoutDetaching([$tag->id]);
    }

    private function result($out, string $success): array
    {
        return ['ok' => $out->status !== 'blocked', 'result' => $out->status === 'blocked' ? 'مسدود: '.$out->policy_reason : $success, 'outbound_id' => $out->id];
    }

    public function render(string $text, ?Contact $contact): string
    {
        $name = $contact?->display_name ? (explode(' ', trim($contact->display_name))[0] ?: $contact->display_name) : ($contact?->username ?: 'دوست عزیز');

        return trim(strtr($text, [
            '{name}' => $name,
            '{username}' => $contact?->username ? '@'.$contact->username : '',
        ]));
    }
}
