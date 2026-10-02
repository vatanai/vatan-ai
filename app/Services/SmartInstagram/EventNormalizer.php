<?php

namespace App\Services\SmartInstagram;

use App\Models\MarketingEvent;
use Illuminate\Support\Carbon;

/**
 * تبدیل رویداد خام (Meta changes / Meta messaging / ورودی امضاشده‌ی n8n-Composio) به قرارداد داخلی واحد.
 * کد دامنه نمی‌داند رویداد از کجا آمده است (پروپوزال ۶ — قرارداد اتصال‌دهنده).
 *
 * @phpstan-type Normalized array{kind:string,direction:string,external_id:?string,sender_id:?string,sender_username:?string,
 *   sender_name:?string,text:?string,media_ref:?string,parent_id:?string,account_id:?string,occurred_at:Carbon,
 *   attachments:array<int,array{type:string,url:?string}>,referral:?array}
 */
class EventNormalizer
{
    public const SUPPORTED_TYPES = ['comment.received', 'dm.received', 'meta.mentions', 'instagram.normalized'];

    /** @return Normalized|null */
    public function normalize(MarketingEvent $event): ?array
    {
        $payload = (array) $event->payload;

        return match ($event->event_type) {
            'comment.received' => $this->fromComment($payload, $event),
            'meta.mentions' => $this->fromMention($payload, $event),
            'dm.received' => isset($payload['message']) || isset($payload['sender'])
                ? $this->fromMessaging($payload, $event)
                : $this->fromChangesMessage($payload, $event),
            'instagram.normalized' => $this->fromNormalized($payload, $event),
            default => null,
        };
    }

    private function fromComment(array $v, MarketingEvent $event): ?array
    {
        $id = (string) ($v['id'] ?? $v['comment_id'] ?? $event->external_id ?? '');
        if ($id === '') {
            return null;
        }

        return $this->make([
            'kind' => 'comment',
            'external_id' => 'c_'.$id,
            'comment_id' => $id,
            'sender_id' => data_get($v, 'from.id'),
            'sender_username' => data_get($v, 'from.username'),
            'text' => $v['text'] ?? null,
            'media_ref' => data_get($v, 'media.id'),
            'parent_id' => $v['parent_id'] ?? null,
            'occurred_at' => $event->occurred_at,
        ]);
    }

    private function fromMention(array $v, MarketingEvent $event): ?array
    {
        $id = (string) ($v['comment_id'] ?? $v['media_id'] ?? $event->external_id ?? '');
        if ($id === '') {
            return null;
        }

        return $this->make([
            'kind' => 'mention',
            'external_id' => 'm_'.$id,
            'comment_id' => $v['comment_id'] ?? null,
            'sender_id' => data_get($v, 'from.id'),
            'sender_username' => data_get($v, 'from.username'),
            'text' => $v['text'] ?? 'منشن در یک محتوا',
            'media_ref' => $v['media_id'] ?? null,
            'occurred_at' => $event->occurred_at,
        ]);
    }

    /** ساختار entry[].messaging[] متا. */
    private function fromMessaging(array $v, MarketingEvent $event): ?array
    {
        $message = (array) ($v['message'] ?? []);
        $mid = (string) ($message['mid'] ?? $event->external_id ?? '');
        if ($mid === '' || !empty($message['is_deleted'])) {
            return null;
        }

        $isEcho = !empty($message['is_echo']);
        $attachments = [];
        foreach ((array) ($message['attachments'] ?? []) as $attachment) {
            $type = (string) ($attachment['type'] ?? 'file');
            $attachments[] = [
                'type' => in_array($type, ['image', 'video', 'audio', 'file'], true) ? $type : ($type === 'story_mention' ? 'story' : 'link'),
                'url' => data_get($attachment, 'payload.url'),
            ];
        }

        $kind = 'dm';
        if (data_get($message, 'reply_to.story.id')) {
            $kind = 'story_reply';
        } elseif (data_get($v, 'referral.source') === 'ADS' || data_get($v, 'referral.ad_id') || data_get($message, 'referral.ad_id')) {
            $kind = 'ad';
        } elseif (collect($attachments)->contains('type', 'story')) {
            $kind = 'mention';
        }

        $timestamp = (int) ($v['timestamp'] ?? 0);

        return $this->make([
            'kind' => $kind,
            'direction' => $isEcho ? 'out' : 'in',
            'external_id' => $mid,
            'sender_id' => $isEcho ? data_get($v, 'recipient.id') : data_get($v, 'sender.id'),
            'account_id' => $isEcho ? data_get($v, 'sender.id') : data_get($v, 'recipient.id'),
            'text' => $message['text'] ?? null,
            'media_ref' => data_get($message, 'reply_to.story.id') ?: data_get($v, 'referral.ad_id') ?: data_get($message, 'referral.ad_id'),
            'parent_id' => data_get($message, 'reply_to.mid'),
            'attachments' => $attachments,
            'referral' => $v['referral'] ?? ($message['referral'] ?? null),
            'occurred_at' => $timestamp > 0 ? Carbon::createFromTimestampMs($timestamp) : $event->occurred_at,
        ]);
    }

    /** ساختار قدیمی changes[field=messages] که کنترلر فعلی ذخیره می‌کند. */
    private function fromChangesMessage(array $v, MarketingEvent $event): ?array
    {
        $mid = (string) (data_get($v, 'message.mid') ?? $v['id'] ?? $event->external_id ?? '');
        if ($mid === '') {
            return null;
        }

        return $this->make([
            'kind' => 'dm',
            'external_id' => $mid,
            'sender_id' => data_get($v, 'sender.id') ?? data_get($v, 'from.id'),
            'text' => data_get($v, 'message.text') ?? ($v['text'] ?? null),
            'occurred_at' => $event->occurred_at,
        ]);
    }

    /** قرارداد ورودی امضاشده‌ی n8n/Composio (doc: smart-instagram ingest). */
    private function fromNormalized(array $v, MarketingEvent $event): ?array
    {
        $type = (string) ($v['type'] ?? 'dm');
        $id = (string) ($v['id'] ?? $event->external_id ?? '');
        if ($id === '' || !in_array($type, ['dm', 'comment', 'story_reply', 'mention', 'ad'], true)) {
            return null;
        }

        return $this->make([
            'kind' => $type,
            'direction' => ($v['direction'] ?? 'in') === 'out' ? 'out' : 'in',
            'external_id' => ($type === 'comment' ? 'c_' : '').$id,
            'comment_id' => $type === 'comment' ? $id : null,
            'sender_id' => data_get($v, 'sender.id'),
            'sender_username' => data_get($v, 'sender.username'),
            'sender_name' => data_get($v, 'sender.name'),
            'account_id' => $v['account_id'] ?? null,
            'text' => $v['text'] ?? null,
            'media_ref' => $v['media_id'] ?? null,
            'parent_id' => $v['parent_id'] ?? null,
            'attachments' => collect((array) ($v['attachments'] ?? []))
                ->map(fn ($a) => ['type' => (string) ($a['type'] ?? 'file'), 'url' => $a['url'] ?? null])
                ->values()->all(),
            'referral' => $v['referral'] ?? null,
            'occurred_at' => isset($v['timestamp']) ? Carbon::parse($v['timestamp']) : $event->occurred_at,
        ]);
    }

    private function make(array $data): ?array
    {
        if (empty($data['sender_id'])) {
            return null;
        }

        return array_merge([
            'direction' => 'in',
            'comment_id' => null,
            'sender_username' => null,
            'sender_name' => null,
            'text' => null,
            'media_ref' => null,
            'parent_id' => null,
            'account_id' => null,
            'attachments' => [],
            'referral' => null,
        ], $data, [
            'sender_id' => (string) $data['sender_id'],
            'occurred_at' => $data['occurred_at'] instanceof Carbon ? $data['occurred_at'] : Carbon::parse($data['occurred_at'] ?? now()),
        ]);
    }
}
