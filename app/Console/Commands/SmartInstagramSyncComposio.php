<?php

namespace App\Console\Commands;

use App\Jobs\SmartInstagram\ProcessInstagramEvent;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\AutomationRule;
use App\Services\ComposioClient;
use App\Services\SmartInstagram\ChannelService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** همگام‌سازی خواندنی دایرکت‌ها، رسانه‌ها و کامنت‌های حساب متصل Composio. */
class SmartInstagramSyncComposio extends Command
{
    protected $signature = 'smart-instagram:sync-composio {--limit=25 : تعداد گفتگوها و رسانه‌های هر اجرا} {--comments : دریافت کامنت‌های رسانه‌ها هم انجام شود}';

    protected $description = 'دریافت خواندنی داده‌های اینستاگرام از Composio و ورود آن به اینستاگرام هوشمند';

    public function handle(ComposioClient $client, WorkspaceContext $context, ChannelService $channels): int
    {
        if (!Schema::hasTable('marketing_events')) {
            $this->warn('جداول اینستاگرام هوشمند هنوز ساخته نشده‌اند.');

            return self::SUCCESS;
        }
        if (!(bool) config('smart_instagram.composio.enabled')) {
            $this->line('اتصال Composio روی این محیط فعال نیست.');

            return self::SUCCESS;
        }

        $channel = $channels->syncFromComposio();
        if (!$channel) {
            $this->error('تنظیمات کانال Composio کامل نیست.');

            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 100));
        $stored = 0;
        $duplicates = 0;
        $failed = 0;

        $conversations = $this->executeTool($client, 'INSTAGRAM_LIST_ALL_CONVERSATIONS', ['limit' => $limit], $channel);
        if (!$conversations['ok']) {
            $this->error($conversations['message']);

            return self::FAILURE;
        }

        foreach ($this->rows($conversations['data'], ['conversations', 'data']) as $conversation) {
            $conversationId = (string) ($conversation['id'] ?? $conversation['conversation_id'] ?? '');
            if ($conversationId === '') {
                continue;
            }
            $messages = $this->executeTool($client, 'INSTAGRAM_LIST_ALL_MESSAGES', ['conversation_id' => $conversationId, 'limit' => 200], $channel);
            if (!$messages['ok']) {
                $failed++;
                continue;
            }
            foreach ($this->rows($messages['data'], ['messages', 'data']) as $message) {
                $event = $this->storeEvent($this->messagePayload($message, $conversationId));
                $event === 'stored' ? $stored++ : ($event === 'duplicate' ? $duplicates++ : $failed++);
            }
        }

        if ($this->option('comments')) {
            $mediaResult = $this->executeTool($client, 'INSTAGRAM_GET_IG_USER_MEDIA', [
                'ig_user_id' => config('services.composio.instagram_user_id', 'me'),
                'limit' => $limit,
                'fields' => 'id,caption,media_type,permalink,timestamp,username',
            ], $channel);
            if ($mediaResult['ok']) {
                $mediaRows = $this->rows($mediaResult['data'], ['media', 'data']);
                $targetMediaIds = $this->targetMediaIds($channel, $mediaRows);
                foreach ($mediaRows as $media) {
                    $mediaId = (string) ($media['id'] ?? '');
                    if ($mediaId === '') {
                        continue;
                    }
                    if ($targetMediaIds !== [] && !in_array($mediaId, $targetMediaIds, true)) {
                        continue;
                    }
                    $this->ingestMediaComments($client, $channel, $mediaId, $limit, $stored, $duplicates, $failed);
                }
                $knownMediaIds = array_values(array_filter(array_map(fn ($media) => (string) ($media['id'] ?? ''), $mediaRows)));
                foreach (array_diff($targetMediaIds, $knownMediaIds) as $mediaId) {
                    $this->ingestMediaComments($client, $channel, (string) $mediaId, $limit, $stored, $duplicates, $failed);
                }
            } else {
                $failed++;
            }
        }

        $this->info("stored: {$stored} · duplicates: {$duplicates} · failed: {$failed}");

        return $failed > 0 && $stored === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function ingestMediaComments(ComposioClient $client, $channel, string $mediaId, int $limit, int &$stored, int &$duplicates, int &$failed): void
    {
        $comments = $this->executeTool($client, 'INSTAGRAM_GET_IG_MEDIA_COMMENTS', [
            'ig_media_id' => $mediaId,
            'limit' => $limit,
            'fields' => 'id,text,username,timestamp,from,hidden,media,parent_id',
        ], $channel);
        if (!$comments['ok']) {
            $failed++;
            return;
        }
        foreach ($this->rows($comments['data'], ['comments', 'data']) as $comment) {
            $event = $this->storeEvent($this->commentPayload($comment, $mediaId));
            $event === 'stored' ? $stored++ : ($event === 'duplicate' ? $duplicates++ : $failed++);
        }
    }

    /** @return array<int,string> */
    private function targetMediaIds($channel, array $mediaRows): array
    {
        $rules = AutomationRule::query()
            ->where('workspace_id', $channel->workspace_id)
            ->whereIn('status', ['active', 'test'])
            ->where('trigger', 'comment_keyword')
            ->get(['scope_ref', 'conditions']);
        if ($rules->isEmpty()) {
            return [];
        }

        $hasGlobalRule = $rules->contains(function ($rule): bool {
            return !$rule->scope_ref && !data_get($rule->conditions, 'post_url');
        });
        if ($hasGlobalRule) {
            return [];
        }

        $ids = $rules->pluck('scope_ref')->filter()->map(fn ($id) => (string) $id)->values()->all();
        foreach ($rules as $rule) {
            $shortcode = $this->instagramShortcode((string) data_get($rule->conditions, 'post_url', ''));
            if ($shortcode === null) {
                continue;
            }
            foreach ($mediaRows as $media) {
                $permalink = (string) ($media['permalink'] ?? '');
                if ($permalink !== '' && str_contains($permalink, '/'.$shortcode)) {
                    $ids[] = (string) ($media['id'] ?? '');
                }
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    private function instagramShortcode(string $url): ?string
    {
        return preg_match('~instagram\.com/(?:p|reel|tv)/([^/?#]+)~i', $url, $matches) === 1 ? $matches[1] : null;
    }

    /** @return array{ok:bool,message:string,data:array} */
    private function executeTool(ComposioClient $client, string $tool, array $arguments, $channel): array
    {
        $result = $client->execute(
            $tool,
            $arguments,
            data_get($channel->settings, 'composio_connected_account_id'),
            data_get($channel->settings, 'composio_user_id'),
        );

        return $result;
    }

    /** @return array<int,array<string,mixed>> */
    private function rows(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;
            if (is_array($value) && array_is_list($value)) {
                return array_values(array_filter($value, 'is_array'));
            }
        }
        return array_is_list($data) ? array_values(array_filter($data, 'is_array')) : [];
    }

    private function messagePayload(array $message, string $conversationId): array
    {
        $sender = (array) ($message['from'] ?? $message['sender'] ?? []);
        $attachments = [];
        foreach ((array) ($message['attachments'] ?? []) as $attachment) {
            $attachments[] = ['type' => (string) ($attachment['type'] ?? 'file'), 'url' => data_get($attachment, 'payload.url') ?: ($attachment['url'] ?? null)];
        }

        return [
            'type' => 'dm',
            'id' => (string) ($message['id'] ?? $message['message_id'] ?? ''),
            'sender' => ['id' => (string) ($sender['id'] ?? ''), 'username' => $sender['username'] ?? null, 'name' => $sender['name'] ?? null],
            'text' => $message['message'] ?? $message['text'] ?? null,
            'timestamp' => $message['created_time'] ?? $message['timestamp'] ?? now()->toIso8601String(),
            'attachments' => $attachments,
            'conversation_id' => $conversationId,
            'account_id' => config('services.composio.instagram_user_id', 'me'),
            '_source' => 'composio',
        ];
    }

    private function commentPayload(array $comment, string $mediaId): array
    {
        $sender = (array) ($comment['from'] ?? []);

        return [
            'type' => 'comment',
            'id' => (string) ($comment['id'] ?? $comment['comment_id'] ?? ''),
            'sender' => ['id' => (string) ($sender['id'] ?? $comment['username'] ?? ''), 'username' => $sender['username'] ?? $comment['username'] ?? null, 'name' => $sender['name'] ?? null],
            'text' => $comment['text'] ?? null,
            'timestamp' => $comment['timestamp'] ?? now()->toIso8601String(),
            'media_id' => $mediaId,
            'parent_id' => $comment['parent_id'] ?? null,
            '_source' => 'composio',
        ];
    }

    private function storeEvent(array $payload): string
    {
        $type = (string) ($payload['type'] ?? 'dm');
        $id = trim((string) ($payload['id'] ?? ''));
        $senderId = trim((string) data_get($payload, 'sender.id'));
        if ($id === '' || $senderId === '') {
            return 'failed';
        }

        $externalId = Str::limit($type.':'.$id, 180, '');
        if (MarketingEvent::query()->where('event_type', 'instagram.normalized')->where('external_id', $externalId)->exists()) {
            return 'duplicate';
        }

        $event = MarketingEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'event_type' => 'instagram.normalized',
            'channel' => 'instagram',
            'processing_status' => 'received',
            'external_id' => $externalId,
            'actor_ref' => $senderId,
            'payload' => $payload,
            'occurred_at' => $this->occurredAt($payload['timestamp'] ?? null),
        ]);
        ProcessInstagramEvent::dispatch($event->id)->onQueue(config('smart_instagram.queues.ingest', 'default'));

        return 'stored';
    }

    private function occurredAt(mixed $value): Carbon
    {
        if (is_numeric($value) && (int) $value > 100000000000) {
            return Carbon::createFromTimestampMs((int) $value);
        }
        return $value ? Carbon::parse($value) : now();
    }
}
