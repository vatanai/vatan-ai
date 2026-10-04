<?php

namespace App\Console\Commands;

use App\Jobs\SmartInstagram\ProcessInstagramEvent;
use App\Models\MarketingEvent;
use App\Models\SmartInstagram\AutomationRule;
use App\Models\SmartInstagram\PostFlowSession;
use App\Services\ComposioClient;
use App\Services\SmartInstagram\ChannelService;
use App\Services\SmartInstagram\ComposioMessageMapper;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/** همگام‌سازی خواندنی دایرکت‌ها، رسانه‌ها و کامنت‌های حساب متصل Composio. */
class SmartInstagramSyncComposio extends Command
{
    protected $signature = 'smart-instagram:sync-composio {--limit=25 : تعداد گفتگوها و رسانه‌های هر اجرا} {--comments : دریافت کامنت‌های رسانه‌ها هم انجام شود} {--comments-only : فقط کامنت‌ها دریافت شوند} {--full-history : همه‌ی صفحه‌های پیام هر گفتگو هم خوانده شوند} {--fast : چرخه‌ی سریع (هر ۱۰ ثانیه) فقط برای پست‌های فعال و جریان‌های منتظر کلیک؛ با وب‌هوک فعال کاری نمی‌کند}';

    protected $description = 'دریافت خواندنی داده‌های اینستاگرام از Composio و ورود آن به اینستاگرام هوشمند';

    public function handle(ComposioClient $client, WorkspaceContext $context, ChannelService $channels, ComposioMessageMapper $mapper): int
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

        if ($this->option('fast')) {
            return $this->fast($client, $channel);
        }

        $limit = max(1, min((int) $this->option('limit'), 100));
        $stored = 0;
        $duplicates = 0;
        $failed = 0;
        $historyComplete = true;
        $fullHistory = !$this->option('comments-only') && (
            (bool) $this->option('full-history')
            || !filled(data_get($channel->settings, 'composio_message_backfill_completed_at'))
        );

        if (!$this->option('comments-only')) {
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
                $after = null;
                $page = 0;
                do {
                    $arguments = ['conversation_id' => $conversationId, 'limit' => 100];
                    if ($after) {
                        $arguments['after'] = $after;
                    }
                    $messages = $this->executeTool($client, 'INSTAGRAM_LIST_ALL_MESSAGES', $arguments, $channel);
                    if (!$messages['ok']) {
                        $failed++;
                        break;
                    }
                    foreach ($this->rows($messages['data'], ['messages', 'data']) as $message) {
                        $event = $this->storeEvent($mapper->map($message, $conversationId, $channel));
                        $event === 'stored' ? $stored++ : ($event === 'duplicate' ? $duplicates++ : $failed++);
                    }
                    $nextAfter = data_get($messages['data'], 'paging.cursors.after');
                    $after = $fullHistory && $nextAfter && $nextAfter !== $after ? (string) $nextAfter : null;
                    $page++;
                } while ($after && $page < 20);
                if ($after) {
                    $historyComplete = false;
                }
            }
        }

        if ($fullHistory && $historyComplete && $failed === 0) {
            $settings = (array) $channel->settings;
            $settings['composio_message_backfill_completed_at'] = now()->toIso8601String();
            $channel->forceFill(['settings' => $settings])->save();
        }

        if ($this->option('comments') || $this->option('comments-only')) {
            $this->syncComments($client, $channel, $limit, $stored, $duplicates, $failed);
        }

        $this->info("stored: {$stored} · duplicates: {$duplicates} · failed: {$failed}");

        return $failed > 0 && $stored === 0 ? self::FAILURE : self::SUCCESS;
    }

    /** کامنت‌های رسانه‌های هدف؛ با قفل مشترک تا چرخه‌ی سریع و دقیقه‌ای هم‌زمان یک کامنت را دو بار وارد نکنند. */
    private function syncComments(ComposioClient $client, $channel, int $limit, int &$stored, int &$duplicates, int &$failed): void
    {
        $lock = Cache::lock('smart-instagram:comment-sync', 120);
        if (!$lock->get()) {
            return;
        }

        try {
            $mediaResult = $this->executeTool($client, 'INSTAGRAM_GET_IG_USER_MEDIA', [
                'ig_user_id' => config('services.composio.instagram_user_id', 'me'),
                'limit' => $limit,
                'fields' => 'id,caption,media_type,permalink,timestamp,username',
            ], $channel);
            if (!$mediaResult['ok']) {
                $failed++;

                return;
            }
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
        } finally {
            $lock->release();
        }
    }

    /**
     * چرخه‌ی سریع: جایگزین موقت وب‌هوک برای رسیدن به پاسخ زیر ۱۰ ثانیه.
     * فقط کامنت‌های پست‌های دارای سناریوی فعال و (در صورت وجود جریان منتظر کلیک/فالو) چند گفتگوی اخیر
     * خوانده می‌شود تا مصرف API پایین بماند. اگر وب‌هوک رسمی Meta فعال باشد، کاری انجام نمی‌شود.
     */
    private function fast(ComposioClient $client, $channel): int
    {
        if ($channel->hasLiveWebhook()) {
            $this->line('وب‌هوک Meta فعال است؛ همگام‌سازی سریع لازم نیست.');

            return self::SUCCESS;
        }

        $stored = 0;
        $duplicates = 0;
        $failed = 0;

        $rules = AutomationRule::query()
            ->where('workspace_id', $channel->workspace_id)
            ->whereIn('status', ['active', 'test'])
            ->where('trigger', 'comment_keyword')
            ->get(['scope_ref', 'conditions']);
        $mediaIds = $rules->pluck('scope_ref')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
        $needsListing = $rules->contains(fn ($rule) => !$rule->scope_ref);

        $lock = Cache::lock('smart-instagram:comment-sync', 60);
        if ($rules->isNotEmpty() && $lock->get()) {
            try {
                if ($needsListing) {
                    $mediaResult = $this->executeTool($client, 'INSTAGRAM_GET_IG_USER_MEDIA', [
                        'ig_user_id' => config('services.composio.instagram_user_id', 'me'),
                        'limit' => 10,
                        'fields' => 'id,permalink,timestamp',
                    ], $channel);
                    if ($mediaResult['ok']) {
                        $rows = $this->rows($mediaResult['data'], ['media', 'data']);
                        $targets = $this->targetMediaIds($channel, $rows);
                        $mediaIds = array_values(array_unique(array_merge($mediaIds, $targets !== [] ? $targets : array_filter(array_map(fn ($m) => (string) ($m['id'] ?? ''), $rows)))));
                    } else {
                        $failed++;
                    }
                }
                foreach (array_slice($mediaIds, 0, 10) as $mediaId) {
                    $this->ingestMediaComments($client, $channel, (string) $mediaId, 50, $stored, $duplicates, $failed);
                }
            } finally {
                $lock->release();
            }
        }

        // کلیک دکمه‌ی پاسخ سریع در دایرکت به‌صورت پیام متنی می‌آید؛ فقط وقتی جریانی منتظر آن است خوانده می‌شود.
        $awaiting = PostFlowSession::query()
            ->where('workspace_id', $channel->workspace_id)
            ->whereIn('stage', ['awaiting_click', 'awaiting_follow'])
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->exists();
        $dmLock = Cache::lock('smart-instagram:dm-fast-sync', 60);
        if ($awaiting && $dmLock->get()) {
            try {
                $conversations = $this->executeTool($client, 'INSTAGRAM_LIST_ALL_CONVERSATIONS', ['limit' => 5], $channel);
                if ($conversations['ok']) {
                    foreach ($this->rows($conversations['data'], ['conversations', 'data']) as $conversation) {
                        $conversationId = (string) ($conversation['id'] ?? $conversation['conversation_id'] ?? '');
                        if ($conversationId === '') {
                            continue;
                        }
                        $messages = $this->executeTool($client, 'INSTAGRAM_LIST_ALL_MESSAGES', ['conversation_id' => $conversationId, 'limit' => 10], $channel);
                        if (!$messages['ok']) {
                            $failed++;
                            continue;
                        }
                        foreach ($this->rows($messages['data'], ['messages', 'data']) as $message) {
                            $event = $this->storeEvent(app(ComposioMessageMapper::class)->map($message, $conversationId, $channel));
                            $event === 'stored' ? $stored++ : ($event === 'duplicate' ? $duplicates++ : $failed++);
                        }
                    }
                } else {
                    $failed++;
                }
            } finally {
                $dmLock->release();
            }
        }

        $this->info("fast · stored: {$stored} · duplicates: {$duplicates} · failed: {$failed}");

        return self::SUCCESS;
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
            // پاسخ‌هایی که خود پیج زیر کامنت‌ها گذاشته رویداد ورودی نیستند.
            if ($channel->isOwnActor((string) data_get($comment, 'from.id', ''), (string) (data_get($comment, 'from.username') ?? $comment['username'] ?? ''))) {
                continue;
            }
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
