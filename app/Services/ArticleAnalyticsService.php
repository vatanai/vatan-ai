<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleEvent;
use Illuminate\Http\Request;

class ArticleAnalyticsService
{
    public const EVENTS = ['view', 'scroll_50', 'scroll_90', 'toc_click', 'prompt_copy', 'product_cta_click', 'share_click', 'comment_submit'];

    public function record(Article $article, string $eventType, Request $request, array $metadata = []): void
    {
        if (! in_array($eventType, self::EVENTS, true)) return;

        $sessionHash = hash('sha256', $request->session()->getId());
        $isUniqueView = $eventType === 'view' && ! ArticleEvent::query()
            ->where('article_id', $article->id)
            ->where('event_type', 'view')
            ->where('session_hash', $sessionHash)
            ->exists();

        $referrer = parse_url((string) $request->headers->get('referer'));
        $userAgent = mb_strtolower((string) $request->userAgent());
        $device = str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')
            ? 'tablet'
            : (str_contains($userAgent, 'mobile') || str_contains($userAgent, 'android') ? 'mobile' : 'desktop');
        $safeMetadata = collect($metadata)->only([
            'product_id', 'block', 'position', 'destination', 'percent', 'heading',
            'method', 'length', 'progress', 'target',
        ])->all();
        $safeMetadata += array_filter([
            'referrer_host' => $referrer['host'] ?? null,
            'referrer_path' => $referrer['path'] ?? null,
            'device' => $device,
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
            'authenticated' => $request->user() !== null,
        ], fn ($value) => $value !== null && $value !== '');

        ArticleEvent::query()->create([
            'article_id' => $article->id,
            'user_id' => $request->user()?->id,
            'session_hash' => $sessionHash,
            'event_type' => $eventType,
            'metadata' => $safeMetadata,
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip() . '|' . config('app.key')) : null,
            'created_at' => now(),
        ]);

        $increments = [];
        if ($eventType === 'view') $increments[] = 'view_count';
        if ($isUniqueView) $increments[] = 'unique_view_count';
        if ($eventType === 'share_click') $increments[] = 'share_count';
        if ($eventType === 'product_cta_click') $increments[] = 'cta_click_count';
        foreach ($increments as $column) $article->increment($column);
        if ($eventType === 'view') $article->forceFill(['last_viewed_at' => now()])->saveQuietly();
    }
}
