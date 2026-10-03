<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Models\MarketingContent;
use App\Models\SmartInstagram\Channel;
use App\Models\SmartInstagram\AutomationRule;
use App\Services\ComposioClient;
use App\Services\SmartInstagram\MetricsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * محتوا و فراخوان‌ها (پروپوزال ۵.۹): کدام پست/ریلز/استوری/تبلیغ گفتگو و فروش ساخت،
 * و کدام فراخوان (کلمه‌ی کلیدی) به کدام اتومیشن وصل است.
 */
class ContentController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics, ComposioClient $composio): View
    {
        $this->authorizeAbility('view');
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;

        $performance = $metrics->contentPerformance(now()->subDays($days), 50);
        $rulesByScope = AutomationRule::query()->where('workspace_id', $this->ws())->whereNotNull('scope_ref')
            ->get(['id', 'name', 'status', 'scope_ref', 'keywords'])->groupBy('scope_ref');
        $globalRules = AutomationRule::query()->where('workspace_id', $this->ws())->whereNull('scope_ref')
            ->whereIn('trigger', ['comment_keyword', 'dm_keyword'])->whereIn('status', ['active', 'test'])
            ->get(['id', 'name', 'status', 'keywords', 'trigger']);

        // محتوای ثبت‌شده در «تقویم و صف محتوا»ی تکنولوژی مارکتینگ — فقط خواندنی، بدون تغییر در آن بخش.
        $planned = Schema::hasTable('marketing_contents')
            ? MarketingContent::query()->where('channel', 'instagram')->latest('updated_at')->limit(12)
                ->get(['id', 'title', 'content_type', 'status', 'external_id', 'keyword', 'publish_at', 'published_at'])
            : collect();

        $composioMedia = $this->composioMedia($composio);

        return view('admin.smart-instagram.content', [
            'days' => $days,
            'performance' => $performance,
            'rulesByScope' => $rulesByScope,
            'globalRules' => $globalRules,
            'planned' => $planned,
            'composioMedia' => $composioMedia,
            'totals' => [
                'items' => $performance->count(),
                'interactions' => $performance->sum('interactions'),
                'deals' => $performance->sum('deals'),
                'value' => $performance->sum('value'),
            ],
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private function composioMedia(ComposioClient $composio): array
    {
        if (!(bool) config('smart_instagram.composio.enabled')) {
            return [];
        }

        $channel = Channel::query()->where('workspace_id', $this->ws())->where('gateway', 'composio')->latest('id')->first();
        if (!$channel) {
            return [];
        }

        // پست‌های همگام‌شده‌ی «ثبت پست» اولویت دارند تا رندر صفحه به تماس زنده‌ی Composio وابسته نباشد.
        $synced = \App\Models\SmartInstagram\Post::query()->where('workspace_id', $this->ws())->where('media_id', 'not like', 'link:%')
            ->orderByDesc('published_at')->limit(25)->get();
        if ($synced->isNotEmpty()) {
            return $synced->map(fn ($p) => [
                'id' => $p->media_id, 'caption' => $p->caption, 'media_type' => $p->media_type, 'permalink' => $p->permalink,
                'timestamp' => $p->published_at?->toIso8601String(), 'username' => $channel->username,
            ])->all();
        }

        try {
            return $this->liveComposioMedia($composio, $channel);
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    private function liveComposioMedia(ComposioClient $composio, Channel $channel): array
    {
        return Cache::remember('smart-instagram.composio-media.'.$channel->id, 60, function () use ($composio, $channel): array {
            $result = $composio->execute(
                'INSTAGRAM_GET_IG_USER_MEDIA',
                ['ig_user_id' => data_get($channel->settings, 'composio_instagram_user_id', config('services.composio.instagram_user_id', 'me')), 'limit' => 25, 'fields' => 'id,caption,media_type,permalink,timestamp,username'],
                data_get($channel->settings, 'composio_connected_account_id'),
                data_get($channel->settings, 'composio_user_id'),
            );
            if (!$result['ok']) {
                return [];
            }

            $data = $result['data'];
            foreach (['data', 'media'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    $data = $data[$key];
                    break;
                }
            }

            return array_values(array_filter($data, 'is_array'));
        });
    }
}
