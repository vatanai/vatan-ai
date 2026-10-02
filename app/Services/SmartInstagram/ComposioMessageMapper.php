<?php

namespace App\Services\SmartInstagram;

use App\Models\SmartInstagram\Channel;

/** تبدیل پیام‌های Graph برگردانده‌شده از Composio به قرارداد داخلی اینستاگرام. */
class ComposioMessageMapper
{
    /**
     * @return array<string,mixed>
     */
    public function map(array $message, string $conversationId, Channel $channel): array
    {
        $from = $this->person($message['from'] ?? $message['sender'] ?? []);
        $recipient = $this->person($message['to'] ?? $message['recipient'] ?? $message['recipients'] ?? []);
        $outbound = $this->isOutbound($message, $from, $channel);
        $contact = $outbound ? $recipient : $from;
        $text = $message['message'] ?? $message['text'] ?? $message['body'] ?? null;
        if (is_array($text)) {
            $text = $text['text'] ?? null;
        }

        return [
            'type' => 'dm',
            'direction' => $outbound ? 'out' : 'in',
            'id' => (string) ($message['id'] ?? $message['message_id'] ?? ''),
            'sender' => [
                'id' => (string) ($contact['id'] ?? $contact['user_id'] ?? ''),
                'username' => $contact['username'] ?? null,
                'name' => $contact['name'] ?? $contact['display_name'] ?? null,
            ],
            'text' => is_string($text) ? $text : null,
            'timestamp' => $message['created_time'] ?? $message['timestamp'] ?? now()->toIso8601String(),
            'attachments' => $this->attachments($message),
            'conversation_id' => $conversationId,
            // شناسه‌ی داخلی کانال را نگه می‌داریم تا resolve() بین چند کانال اشتباه نکند؛
            // شناسه‌ی واقعی طرف مقابل در sender ذخیره می‌شود.
            'account_id' => $this->configuredAccountId($channel),
            '_source' => 'composio',
        ];
    }

    /** @return array<string,mixed> */
    private function person(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        if (isset($value['data']) && is_array($value['data'])) {
            foreach ($value['data'] as $person) {
                if (is_array($person) && ($person['id'] ?? $person['user_id'] ?? null)) {
                    return $person;
                }
            }
        }

        if (array_is_list($value)) {
            foreach ($value as $person) {
                if (is_array($person) && ($person['id'] ?? $person['user_id'] ?? null)) {
                    return $person;
                }
            }

            return [];
        }

        return $value;
    }

    private function isOutbound(array $message, array $from, Channel $channel): bool
    {
        if (!empty($message['is_echo']) || !empty($message['is_outbound'])) {
            return true;
        }

        $direction = strtolower((string) ($message['direction'] ?? $message['sent_by'] ?? ''));
        if (in_array($direction, ['out', 'outbound', 'outgoing', 'sent'], true)) {
            return true;
        }

        $ownIds = array_filter([
            $channel->external_account_id,
            data_get($channel->settings, 'composio_instagram_user_id'),
        ], fn ($id) => filled($id) && $id !== 'me');
        $fromId = (string) ($from['id'] ?? $from['user_id'] ?? '');
        if ($fromId !== '' && in_array($fromId, array_map('strval', $ownIds), true)) {
            return true;
        }

        $fromUsername = ltrim(strtolower((string) ($from['username'] ?? '')), '@');
        $ownUsername = ltrim(strtolower((string) $channel->username), '@');

        return $fromUsername !== '' && $ownUsername !== '' && $fromUsername === $ownUsername;
    }

    /** @return array<int,array{type:string,url:?string}> */
    private function attachments(array $message): array
    {
        $raw = $message['attachments'] ?? [];
        if (is_array($raw) && isset($raw['data']) && is_array($raw['data'])) {
            $raw = $raw['data'];
        }
        if (!is_array($raw)) {
            return [];
        }

        $attachments = [];
        foreach ($raw as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $type = (string) ($attachment['type'] ?? '');
            $url = data_get($attachment, 'payload.url')
                ?: ($attachment['url'] ?? null)
                ?: data_get($attachment, 'image_data.url')
                ?: data_get($attachment, 'video_data.url')
                ?: data_get($attachment, 'audio_data.url');
            if ($type === '') {
                $type = isset($attachment['image_data']) ? 'image' : (isset($attachment['video_data']) ? 'video' : 'file');
            }
            $attachments[] = [
                'type' => in_array($type, ['image', 'video', 'audio', 'file'], true) ? $type : 'file',
                'url' => $url,
            ];
        }

        return $attachments;
    }

    private function configuredAccountId(Channel $channel): string
    {
        return (string) (data_get($channel->settings, 'composio_instagram_user_id') ?: $channel->external_account_id ?: 'me');
    }
}
