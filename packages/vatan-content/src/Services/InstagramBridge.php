<?php

namespace Vatan\Content\Services;

use Illuminate\Support\Facades\Log;
use Vatan\Content\Models\MetricSnapshot;
use Vatan\Content\Models\Production;

/**
 * پل کارخانه ↔ «اینستاگرام هوشمند»:
 * ۱) پیدا کردن پست منتشرشده (از لینک یا تطبیق کپشن بعد از همگام‌سازی)
 * ۲) ساخت سناریوی کامنت/دایرکت با کلمه‌ی کلیدی CTA و لینک محصول
 * ۳) برداشت آمار زمان‌مند و امتیازدهی
 * همه‌ی وابستگی‌ها با class_exists؛ اگر ماژول نبود، کارخانه کار می‌کند و فقط اتصال غیرفعال است.
 */
class InstagramBridge
{
    public function available(): bool
    {
        return class_exists(\App\Models\SmartInstagram\Post::class) && class_exists(\App\Services\SmartInstagram\Posts\PostSyncService::class);
    }

    public function postFor(Production $p): ?object
    {
        if (! $this->available()) {
            return null;
        }
        $Post = \App\Models\SmartInstagram\Post::class;
        if ($p->si_post_id && ($post = $Post::find($p->si_post_id))) {
            return $post;
        }
        $sync = app(\App\Services\SmartInstagram\Posts\PostSyncService::class);
        $short = $p->ig_permalink ? $sync->shortcode($p->ig_permalink) : null;
        $find = function () use ($Post, $short, $p) {
            if ($short && ($x = $Post::where('shortcode', $short)->first())) {
                return $x;
            }
            $head = mb_substr(trim((string) $p->caption), 0, 40);
            if ($head !== '') {
                return $Post::where('caption', 'like', $head.'%')->when($p->published_at, fn ($q) => $q->where('published_at', '>=', $p->published_at->copy()->subDays(2)))->latest('published_at')->first();
            }

            return null;
        };
        $post = $find();
        if (! $post) {
            try {
                $sync->syncRecent(12);
                $post = $find();
            } catch (\Throwable $e) {
                Log::info('ContentFactory: SI sync skipped', ['e' => $e->getMessage()]);
            }
        }
        if ($post) {
            $p->update(['si_post_id' => $post->id, 'ig_permalink' => $p->ig_permalink ?: $post->permalink, 'published_at' => $p->published_at ?: $post->published_at]);
        }

        return $post;
    }

    /** سناریوی کامنت → دایرکت در اینستاگرام هوشمند (فقط اگر پاسخ خودکار روشن باشد) */
    public function campaign(Production $p, ?int $adminId = null): ?object
    {
        if (! $p->auto_reply || ! ($post = $this->postFor($p)) || ! class_exists(\App\Services\SmartInstagram\Posts\PostCampaignService::class)) {
            return null;
        }
        $kw = array_values(array_filter(array_map('trim', (array) $p->keywords)));
        if (! $kw) {
            return null;
        }
        $svc = app(\App\Services\SmartInstagram\Posts\PostCampaignService::class);
        $settings = $svc->defaults();
        $settings['card']['title'] = $p->product_label ?: 'ساخت عکس محصول با وطن';
        $settings['card']['subtitle'] = 'با اعتبار هدیه، اولین عکس محصولت رو رایگان بساز';
        $settings['card']['buttons'][0]['url'] = $p->product_url ?: url('/');
        $settings['card']['buttons'][0]['label'] = 'شروع ساخت';
        try {
            $campaign = $svc->save($post, [
                'title' => 'کارخانه · '.$p->title, 'status' => $post->isVerified() ? 'active' : 'draft',
                'follow_required' => false, 'public_reply_enabled' => true, 'dm_enabled' => true, 'settings' => $settings,
                'keywords' => array_map(fn ($k) => ['keyword' => $k, 'match_mode' => 'contains', 'is_active' => true], $kw),
            ], null, $adminId, 'ساخته‌شده خودکار از کارخانه‌ی محتوا ('.$p->code.')');
            $p->update(['si_campaign_id' => $campaign->id]);

            return $campaign;
        } catch (\Throwable $e) {
            Log::warning('ContentFactory: campaign failed', ['p' => $p->id, 'e' => $e->getMessage()]);

            return null;
        }
    }

    /** پنجره‌های سررسید آمار برای یک تولید منتشرشده */
    public function dueWindows(Production $p): array
    {
        if (! $p->published_at) {
            return [];
        }
        $age = $p->published_at->diffInMinutes(now());
        $have = $p->snapshots()->pluck('window')->all();

        return array_keys(array_filter(config('content-factory.metric_windows'), fn ($m, $w) => $age >= $m && ! in_array($w, $have, true), ARRAY_FILTER_USE_BOTH));
    }

    public function snapshot(Production $p): int
    {
        $due = $this->dueWindows($p);
        if (! $due || ! ($post = $this->postFor($p))) {
            return 0;
        }
        try {
            app(\App\Services\SmartInstagram\Posts\PostSyncService::class)->syncOne($post);
            $post->refresh();
        } catch (\Throwable $e) {
            Log::info('ContentFactory: syncOne failed', ['e' => $e->getMessage()]);
        }
        $metrics = [
            'likes' => $post->like_count, 'comments' => $post->comments_count, 'saves' => $post->saved_count,
            'shares' => $post->shares_count, 'reach' => $post->reach_count,
        ];
        // داده‌ی کسب‌وکاری از سناریوی کامنت/دایرکت (اگر بود)
        if ($p->si_campaign_id && class_exists(\App\Models\SmartInstagram\PostFlowSession::class)) {
            try {
                $metrics['dm_flows'] = \App\Models\SmartInstagram\PostFlowSession::where('campaign_id', $p->si_campaign_id)->count();
            } catch (\Throwable) {
                $metrics['dm_flows'] = null;
            }
        }
        foreach ($due as $w) {
            MetricSnapshot::updateOrCreate(['production_id' => $p->id, 'window' => $w], ['captured_at' => now(), 'metrics' => $metrics,
                'raw' => $post->only(['media_id', 'permalink', 'stats_synced_at', 'sync_error']), 'source' => 'smart_instagram']);
        }
        app(Scorer::class)->score($p->fresh());

        return count($due);
    }
}
