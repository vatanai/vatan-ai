<?php

namespace App\Services\SmartInstagram;

use App\Jobs\SmartInstagram\SendOutboundMessage;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\OutboundMessage;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use Illuminate\Support\Str;

/** صف پیام خروجی و ارسال به اتصال‌دهنده با retry کنترل‌شده (پروپوزال ۸.۲). */
class OutboundService
{
    public function __construct(
        private readonly SendPolicy $policy,
        private readonly GatewayManager $gateways,
        private readonly OperationLogger $logger,
    ) {
    }

    /**
     * @param array{admin_id?:?int,automation_run_id?:?int,ai_suggestion_id?:?int,target_ref?:?string,idempotency_key?:?string,dispatch?:bool,allow_human_lock?:bool} $options
     */
    public function queue(Conversation $conversation, string $body, string $origin, string $kind = 'dm', array $options = []): OutboundMessage
    {
        $body = trim($body);
        $targetRef = $options['target_ref'] ?? null;
        $allowHumanLock = (bool) ($options['allow_human_lock'] ?? false);
        $decision = $this->policy->evaluate($conversation, $kind, $origin, $body, $targetRef, $allowHumanLock);
        $key = $options['idempotency_key'] ?? ($origin.':'.$conversation->id.':'.Str::uuid());

        $existing = OutboundMessage::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return $existing;
        }

        $outbound = OutboundMessage::query()->create([
            'workspace_id' => $conversation->workspace_id,
            'channel_id' => $conversation->channel_id,
            'conversation_id' => $conversation->id,
            'contact_id' => $conversation->contact_id,
            'kind' => $kind,
            'target_ref' => $targetRef,
            'body' => $body,
            'origin' => $origin,
            'admin_id' => $options['admin_id'] ?? null,
            'automation_run_id' => $options['automation_run_id'] ?? null,
            'ai_suggestion_id' => $options['ai_suggestion_id'] ?? null,
            'allow_human_lock' => $allowHumanLock,
            'status' => $decision['allowed'] ? 'pending' : 'blocked',
            'policy_reason' => $decision['reason'],
            'window_expires_at' => $decision['window_expires_at'],
            'idempotency_key' => $key,
        ]);

        if (!$decision['allowed']) {
            $this->logger->log('outbound.blocked', (string) $decision['reason'], $outbound, ['origin' => $origin, 'kind' => $kind], 'warning', $options['admin_id'] ?? null);
        } elseif ($options['dispatch'] ?? true) {
            SendOutboundMessage::dispatch($outbound->id)->onQueue(config('smart_instagram.queues.outbound', 'default'));
        }

        return $outbound;
    }

    /** ارسال واقعی؛ از جاب صدا زده می‌شود. */
    public function deliver(OutboundMessage $outbound): OutboundMessage
    {
        if (!in_array($outbound->status, ['pending', 'retrying'], true)) {
            return $outbound;
        }

        $outbound->loadMissing('conversation.channel', 'conversation.contact');
        $conversation = $outbound->conversation;

        // بررسی دوباره درست پیش از ارسال: ممکن است در فاصله‌ی صف، گفتگو به انسان واگذار شده باشد.
        if (in_array($outbound->origin, ['ai', 'automation'], true) && !$outbound->allow_human_lock && ($conversation->ai_paused || $conversation->needs_human || $conversation->contact->opted_out)) {
            $outbound->forceFill(['status' => 'blocked', 'policy_reason' => 'پیش از ارسال، گفتگو به انسان واگذار یا متوقف شد.'])->save();

            return $outbound;
        }
        if (!config('smart_instagram.outbound_enabled') || !$conversation->channel->outbound_enabled) {
            $outbound->forceFill(['status' => 'blocked', 'policy_reason' => 'ارسال پیش از تحویل خاموش شد.'])->save();

            return $outbound;
        }

        $outbound->forceFill(['status' => 'sending', 'attempts' => $outbound->attempts + 1])->save();
        $gateway = $this->gateways->for($conversation->channel);
        $result = match ($outbound->kind) {
            'private_reply' => $gateway->sendPrivateReply($conversation->channel, (string) $outbound->target_ref, $outbound->body),
            'public_reply' => $gateway->replyToComment($conversation->channel, (string) $outbound->target_ref, $outbound->body),
            default => $gateway->sendDirectMessage($conversation->channel, (string) $conversation->contact->external_id, $outbound->body),
        };

        if ($result->ok) {
            $message = Message::query()->create([
                'workspace_id' => $outbound->workspace_id,
                'conversation_id' => $conversation->id,
                'external_id' => $result->externalId,
                'direction' => 'out',
                'source_type' => $outbound->kind === 'dm' ? 'dm' : 'comment',
                'message_type' => 'text',
                'body' => $outbound->body,
                'sent_by' => $outbound->origin,
                'admin_id' => $outbound->admin_id,
                'delivery_status' => 'sent',
                'meta' => array_filter(['outbound_id' => $outbound->id, 'kind' => $outbound->kind, 'comment_id' => $outbound->kind !== 'dm' ? $outbound->target_ref : null]),
                'occurred_at' => now(),
            ]);
            $outbound->forceFill([
                'status' => 'sent', 'sent_at' => now(), 'external_id' => $result->externalId, 'error' => null,
                'message_id' => $message->id, 'provider_response' => ['message' => $result->message],
            ])->save();

            $updates = [
                'last_outbound_at' => now(), 'last_message_at' => now(), 'last_message_direction' => 'out',
                'last_message_preview' => mb_substr($outbound->body, 0, 280), 'unread_count' => 0,
                'status' => $conversation->status === 'closed' ? 'closed' : 'waiting_customer',
            ];
            if (!$conversation->first_response_at && $conversation->last_inbound_at) {
                $updates['first_response_at'] = now();
                $updates['first_response_seconds'] = max(0, $conversation->last_inbound_at->diffInSeconds(now()));
            }
            $conversation->forceFill($updates)->save();

            return $outbound;
        }

        $maxAttempts = (int) config('smart_instagram.policy.max_attempts', 4);
        if ($result->retryable && $outbound->attempts < $maxAttempts) {
            $delay = min(3600, 30 * (2 ** ($outbound->attempts - 1)));
            $outbound->forceFill(['status' => 'retrying', 'error' => $result->message, 'next_attempt_at' => now()->addSeconds($delay), 'provider_response' => $result->data])->save();
            SendOutboundMessage::dispatch($outbound->id)->delay($delay)->onQueue(config('smart_instagram.queues.outbound', 'default'));

            return $outbound;
        }

        $outbound->forceFill(['status' => 'failed', 'error' => $result->message, 'provider_response' => $result->data])->save();
        $this->logger->error('outbound.failed', 'ارسال پیام #'.$outbound->id.' ناموفق بود: '.$result->message, $outbound, ['kind' => $outbound->kind, 'origin' => $outbound->origin]);

        return $outbound;
    }

    /** بازآزمایی دستی پیام ناموفق از پنل سلامت. */
    public function retry(OutboundMessage $outbound): OutboundMessage
    {
        if ($outbound->status !== 'failed') {
            return $outbound;
        }
        $outbound->forceFill(['status' => 'pending', 'attempts' => 0, 'error' => null])->save();
        SendOutboundMessage::dispatch($outbound->id)->onQueue(config('smart_instagram.queues.outbound', 'default'));

        return $outbound;
    }
}
