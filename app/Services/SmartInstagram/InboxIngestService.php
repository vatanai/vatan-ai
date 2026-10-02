<?php

namespace App\Services\SmartInstagram;

use App\Jobs\SmartInstagram\AnalyzeConversation;
use App\Jobs\SmartInstagram\FetchMessageAttachment;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\AiProfile;
use App\Models\SmartInstagram\Contact;
use App\Models\SmartInstagram\Conversation;
use App\Models\SmartInstagram\Message;
use App\Models\SmartInstagram\MessageAttachment;
use App\Services\SmartInstagram\Automation\AutomationEngine;
use Illuminate\Support\Facades\DB;

/**
 * صف پردازش و نرمال‌سازی (پروپوزال ۸.۱ — گام‌های ۴ تا ۷):
 * رویداد خام → مشتری + گفتگو + پیام + رسانه → اتومیشن → تحلیل هوش مصنوعی.
 * کاملاً idempotent: پردازش دوباره‌ی یک رویداد، رکورد تکراری نمی‌سازد.
 */
class InboxIngestService
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly EventNormalizer $normalizer,
        private readonly ChannelService $channels,
        private readonly AutomationEngine $automations,
        private readonly OperationLogger $logger,
    ) {
    }

    public function process(MarketingEvent $event): string
    {
        if (in_array($event->processing_status, ['processed', 'ignored', 'duplicate'], true)) {
            return $event->processing_status;
        }

        try {
            $normalized = $this->normalizer->normalize($event);
            if (!$normalized) {
                $this->mark($event, 'ignored');

                return 'ignored';
            }

            $message = $this->store($normalized, $event);
            if (!$message) {
                $this->mark($event, 'duplicate');

                return 'duplicate';
            }

            $this->mark($event, 'processed');
            $this->afterStore($message);

            return 'processed';
        } catch (\Throwable $e) {
            $event->forceFill(['processing_status' => 'failed', 'error_message' => mb_substr($e->getMessage(), 0, 1000), 'processed_at' => now()])->save();
            $this->logger->error('ingest.failed', 'پردازش رویداد #'.$event->id.' ناموفق بود.', $event, ['error' => $e->getMessage()]);

            return 'failed';
        }
    }

    /** @param array<string,mixed> $n */
    public function store(array $n, ?MarketingEvent $event = null): ?Message
    {
        $workspaceId = $this->context->id();
        $channel = $this->channels->resolve($n['account_id']);

        return DB::transaction(function () use ($n, $event, $workspaceId, $channel): ?Message {
            if ($n['external_id'] && Message::query()->where('workspace_id', $workspaceId)->where('external_id', $n['external_id'])->exists()) {
                return null;
            }

            $contact = Contact::query()->firstOrNew(['workspace_id' => $workspaceId, 'external_id' => $n['sender_id']]);
            if (!$contact->exists) {
                $contact->first_source = $n['kind'];
                $contact->first_source_ref = $n['media_ref'];
            }
            $contact->username = $n['sender_username'] ?: $contact->username;
            $contact->display_name = $n['sender_name'] ?: $contact->display_name;
            if ($n['direction'] === 'in') {
                $contact->last_interaction_at = $n['occurred_at'];
            }
            $contact->save();

            $conversation = Conversation::query()->firstOrCreate(
                ['channel_id' => $channel->id, 'contact_id' => $contact->id],
                ['workspace_id' => $workspaceId, 'status' => 'new']
            );

            $isInbound = $n['direction'] === 'in';
            $message = Message::query()->create([
                'workspace_id' => $workspaceId,
                'conversation_id' => $conversation->id,
                'external_id' => $n['external_id'],
                'direction' => $isInbound ? 'in' : 'out',
                'source_type' => $n['kind'],
                'source_ref' => $n['media_ref'],
                'parent_external_id' => $n['parent_id'],
                'message_type' => $this->messageType($n),
                'body' => $n['text'],
                'sent_by' => $isInbound ? 'customer' : 'instagram_app',
                'marketing_event_id' => $event?->id,
                'meta' => array_filter([
                    'comment_id' => $n['comment_id'],
                    'referral' => $n['referral'],
                ]),
                'occurred_at' => $n['occurred_at'],
            ]);

            foreach ($n['attachments'] as $attachment) {
                MessageAttachment::query()->create([
                    'workspace_id' => $workspaceId,
                    'message_id' => $message->id,
                    'type' => $attachment['type'],
                    'remote_url' => $attachment['url'],
                    'fetch_status' => $attachment['url'] && in_array($attachment['type'], ['image', 'video', 'audio', 'file'], true) ? 'pending' : 'skipped',
                ]);
            }

            $this->touchConversation($conversation, $message, $n);
            $channel->forceFill(['last_event_at' => now()])->save();

            return $message;
        });
    }

    private function touchConversation(Conversation $conversation, Message $message, array $n): void
    {
        $preview = $message->body ?: match ($message->message_type) {
            'audio' => 'پیام صوتی',
            'image' => 'تصویر',
            'video' => 'ویدیو',
            default => 'پیوست',
        };

        $updates = [
            'last_message_preview' => mb_substr((string) $preview, 0, 280),
            'last_message_direction' => $message->direction,
            'last_message_at' => $message->occurred_at,
            'last_source' => $message->source_type,
        ];

        if ($message->isInbound()) {
            $updates['last_inbound_at'] = $message->occurred_at;
            $updates['unread_count'] = $conversation->unread_count + 1;
            $updates['status'] = $conversation->status === 'new' || !$conversation->exists ? 'new' : 'unanswered';
            if ($conversation->status === 'closed') {
                $updates['closed_at'] = null;
            }
            if ($this->isSensitive((string) $message->body)) {
                $updates['needs_human'] = true;
                $updates['priority'] = 'high';
            }
        } else {
            $updates['last_outbound_at'] = $message->occurred_at;
            $updates['status'] = 'waiting_customer';
            $updates['unread_count'] = 0;
            if (!$conversation->first_response_at && $conversation->last_inbound_at) {
                $updates['first_response_at'] = $message->occurred_at;
                $updates['first_response_seconds'] = max(0, $conversation->last_inbound_at->diffInSeconds($message->occurred_at));
            }
        }

        $conversation->forceFill($updates)->save();
    }

    private function afterStore(Message $message): void
    {
        foreach ($message->attachments()->where('fetch_status', 'pending')->pluck('id') as $attachmentId) {
            FetchMessageAttachment::dispatch($attachmentId)->onQueue(config('smart_instagram.queues.media', 'default'));
        }

        if (!$message->isInbound()) {
            return;
        }

        $this->automations->handle($message);

        $conversation = $message->conversation()->first();
        if ($conversation && config('smart_instagram.ai.enabled') && !$conversation->ai_paused && filled($message->body)) {
            AnalyzeConversation::dispatch($conversation->id, $message->id)->onQueue(config('smart_instagram.queues.ai', 'default'));
        }
    }

    /** کلمات حساس پروفایل فعال (پروپوزال سناریو ۵) — پاسخ خودکار متوقف و گفتگو اولویت‌دار می‌شود. */
    public function isSensitive(string $text): bool
    {
        if (trim($text) === '') {
            return false;
        }

        $keywords = (array) (AiProfile::query()->where('workspace_id', $this->context->id())->where('is_active', true)->value('escalation_keywords') ?? []);
        if (is_string($keywords)) {
            $keywords = (array) json_decode($keywords, true);
        }

        foreach ($keywords as $keyword) {
            if (PersianText::containsKeyword($text, (string) $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function messageType(array $n): string
    {
        $types = array_column($n['attachments'], 'type');
        foreach (['audio', 'video', 'image', 'file'] as $type) {
            if (in_array($type, $types, true)) {
                return $type;
            }
        }

        return 'text';
    }

    private function mark(MarketingEvent $event, string $status): void
    {
        $event->forceFill(['processing_status' => $status, 'processed_at' => now(), 'error_message' => null])->save();
    }
}
