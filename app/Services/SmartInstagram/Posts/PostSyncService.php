<?php

namespace App\Services\SmartInstagram\Posts;

use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostStatDaily;
use App\Services\SmartInstagram\Gateways\GatewayManager;
use App\Services\SmartInstagram\Gateways\RichInstagramGateway;
use App\Services\SmartInstagram\OperationLogger;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * همگام‌سازی پست‌ها، کاور و آمار از اتصال واقعی (Composio یا Meta).
 * کاور روی دیسک عمومی ذخیره می‌شود تا هم در پنل سریع لود شود و هم برای کارت دایرکت لینک پایدار داشته باشد.
 */
class PostSyncService
{
    public function __construct(
        private readonly WorkspaceContext $context,
        private readonly GatewayManager $gateways,
        private readonly OperationLogger $logger,
    ) {
    }

    /** کانال مرجع پست‌ها: اول اتصال‌های واقعی متصل، بعد هر کانال دیگر. */
    public function channel(): ?Channel
    {
        return Channel::query()->where('workspace_id', $this->context->id())
            ->orderByRaw("CASE gateway WHEN 'composio' THEN 0 WHEN 'meta' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE status WHEN 'connected' THEN 0 ELSE 1 END")
            ->orderBy('id')->first();
    }

    /** @return array{ok:bool,message:string,synced:int} */
    public function syncRecent(int $limit = 30): array
    {
        $channel = $this->channel();
        if (!$channel) {
            return ['ok' => false, 'message' => 'هنوز کانال اینستاگرامی متصل نیست؛ از «اتصال‌ها و تیم» اتصال را برقرار کنید.', 'synced' => 0];
        }
        $gateway = $this->gateways->for($channel);
        if (!$gateway instanceof RichInstagramGateway) {
            return ['ok' => false, 'message' => 'اتصال‌دهنده‌ی این کانال دریافت پست را پشتیبانی نمی‌کند.', 'synced' => 0];
        }

        $result = $gateway->listMedia($channel, max(1, min($limit, 50)));
        if (!$result->ok) {
            $this->logger->log('posts.sync_failed', 'همگام‌سازی پست‌ها ناموفق بود: '.$result->message, $channel, [], 'warning');

            return ['ok' => false, 'message' => 'دریافت پست‌ها ناموفق بود: '.$result->message, 'synced' => 0];
        }

        $count = 0;
        foreach ((array) ($result->data['items'] ?? []) as $item) {
            if (is_array($item) && !empty($item['id'])) {
                $this->upsert($channel, $item);
                $count++;
            }
        }
        $this->logger->log('posts.synced', $count.' پست همگام شد.', $channel);

        return ['ok' => true, 'message' => $count ? $count.' پست همگام شد.' : 'پستی در حساب پیدا نشد.', 'synced' => $count];
    }

    /** همگام‌سازی آمار و کاور یک پست؛ برای پست دستی، همین‌جا اتصال تأیید می‌شود. */
    public function syncOne(Post $post): array
    {
        $channel = $post->channel ?: $this->channel();
        $gateway = $channel ? $this->gateways->for($channel) : null;
        if (!$channel || !$gateway instanceof RichInstagramGateway) {
            $post->forceFill(['sync_error' => 'اتصال قابل‌استفاده برای همگام‌سازی پیدا نشد.'])->save();

            return ['ok' => false, 'message' => 'اتصال قابل‌استفاده برای همگام‌سازی پیدا نشد.'];
        }

        $media = $gateway->getMedia($channel, $post->media_id);
        if (!$media->ok || empty($media->data['item']['id'])) {
            $post->forceFill(['sync_error' => $media->message, 'connection_status' => $post->source === 'manual' ? 'needs_check' : $post->connection_status])->save();

            return ['ok' => false, 'message' => 'این پست از اتصال فعلی قابل دریافت نیست: '.$media->message];
        }
        $this->upsert($channel, (array) $media->data['item'], $post);

        $insights = $gateway->mediaInsights($channel, $post->media_id);
        if ($insights->ok) {
            $post->forceFill([
                'saved_count' => $insights->data['saved'] ?? null,
                'shares_count' => $insights->data['shares'] ?? null,
                'reach_count' => $insights->data['reach'] ?? null,
            ])->save();
            $this->snapshot($post);
        }

        return ['ok' => true, 'message' => 'آمار و کاور پست به‌روز شد.'.($insights->ok ? '' : ' (آمار ذخیره/اشتراک از Meta دریافت نشد)')];
    }

    /** ثبت دستی شناسه‌ی رسانه وقتی اتصال در دسترس نیست — تا تأیید اتصال، فعال نمی‌شود. */
    public function registerManual(string $mediaId, ?string $permalink = null): Post
    {
        $post = Post::query()->firstOrNew(['workspace_id' => $this->context->id(), 'media_id' => trim($mediaId)]);
        if (!$post->exists) {
            $post->fill([
                'channel_id' => $this->channel()?->id,
                'permalink' => $permalink,
                'shortcode' => $this->shortcode((string) $permalink),
                'source' => 'manual',
                'connection_status' => 'needs_check',
            ])->save();
        }

        return $post;
    }

    public function upsert(Channel $channel, array $item, ?Post $post = null): Post
    {
        $permalink = (string) ($item['permalink'] ?? '');
        if (!$post) {
            $post = Post::query()->where('workspace_id', $channel->workspace_id)->where('media_id', (string) $item['id'])->first();
            // پستی که فقط با لینک ثبت شده بود، با شناسه‌ی واقعی رسانه یکی می‌شود.
            $code = $item['shortcode'] ?? $this->shortcode($permalink);
            if (!$post && $code) {
                $post = Post::query()->where('workspace_id', $channel->workspace_id)->where('shortcode', $code)->where('media_id', 'like', 'link:%')->first();
                $post?->forceFill(['media_id' => (string) $item['id'], 'source' => 'manual'])->save();
                if ($post?->campaign) {
                    app(PostCampaignService::class)->compile($post->campaign->fresh(['keywords', 'post', 'rule']));
                }
            }
            $post ??= new Post(['workspace_id' => $channel->workspace_id, 'media_id' => (string) $item['id']]);
        }
        $permalink = $permalink ?: (string) ($post->permalink ?? '');
        $coverSource = (string) ($item['thumbnail_url'] ?? (($item['media_type'] ?? '') === 'VIDEO' ? '' : ($item['media_url'] ?? '')));

        $post->fill([
            'channel_id' => $channel->id,
            'permalink' => $permalink ?: null,
            'shortcode' => $item['shortcode'] ?? $this->shortcode($permalink) ?? $post->shortcode,
            'media_type' => $item['media_type'] ?? $post->media_type,
            'product_type' => $item['media_product_type'] ?? $post->product_type,
            'caption' => array_key_exists('caption', $item) ? (string) $item['caption'] : $post->caption,
            'published_at' => isset($item['timestamp']) ? Carbon::parse($item['timestamp']) : $post->published_at,
            'like_count' => isset($item['like_count']) && is_numeric($item['like_count']) ? (int) $item['like_count'] : $post->like_count,
            'comments_count' => isset($item['comments_count']) && is_numeric($item['comments_count']) ? (int) $item['comments_count'] : $post->comments_count,
            'stats_synced_at' => (isset($item['like_count']) || isset($item['comments_count'])) ? now() : $post->stats_synced_at,
            'connection_status' => 'verified',
            'sync_error' => null,
        ]);
        if (!$post->source) {
            $post->source = 'sync';
        }
        $post->save();

        if ($coverSource !== '' && ($coverSource !== $post->cover_source_url || !$post->cover_path)) {
            $this->storeCover($post, $coverSource);
        }
        $this->snapshot($post);

        return $post;
    }

    private function storeCover(Post $post, string $url): void
    {
        $post->forceFill(['cover_source_url' => $url])->save();
        if (!str_starts_with($url, 'https://')) {
            return;
        }
        try {
            $response = Http::connectTimeout(8)->timeout(25)->get($url);
            $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
            if ($response->failed() || !str_starts_with($mime, 'image/') || strlen($response->body()) > 8 * 1024 * 1024) {
                return;
            }
            $extension = ['image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
            $path = sprintf('smart-instagram/posts/%d/%s.%s', $post->workspace_id, preg_replace('/\W/', '', $post->media_id), $extension);
            Storage::disk('public')->put($path, $response->body());
            $post->forceFill(['cover_path' => $path])->save();
        } catch (\Throwable) {
            // کاور اختیاری است؛ نبودش جلوی همگام‌سازی را نمی‌گیرد.
        }
    }

    private function snapshot(Post $post): void
    {
        if ($post->like_count === null && $post->comments_count === null) {
            return;
        }
        PostStatDaily::query()->updateOrCreate(
            ['post_id' => $post->id, 'day' => now('Asia/Tehran')->toDateString()],
            ['like_count' => $post->like_count, 'comments_count' => $post->comments_count, 'saved_count' => $post->saved_count, 'shares_count' => $post->shares_count]
        );
    }

    public function shortcode(string $url): ?string
    {
        return preg_match('~instagram\.com/(?:p|reel|reels|tv)/([^/?#]+)~i', $url, $m) === 1 ? $m[1] : null;
    }
}
