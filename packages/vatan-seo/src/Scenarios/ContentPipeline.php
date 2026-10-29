<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\ContentWriter;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\AutoPublisher;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;

/** Builds, quality-gates, publishes, and reports scheduled articles. */
class ContentPipeline implements Handler
{
    public function __construct(private ContentWriter $writer, private Ai $ai, private AutoPublisher $publisher) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $perRun = max(1, min(3, (int) ($scenario->config['per_run'] ?? 2)));
        $articleLeft = Budget::limit($site, 'articles_per_month') - Budget::articlesThisMonth($site);
        $briefsLeft = Budget::limit($site, 'briefs_per_month') - ContentItem::where('site_id', $site->id)->whereNotNull('brief')->where('created_at', '>=', now()->startOfMonth())->count();
        if (! $this->ai->available($site, 'strategist')) return ['failed', 'تولید مقاله متوقف شد: نقش استراتژیست در هوش مصنوعی در دسترس نیست.'];

        $stats = ['briefs' => 0, 'drafts' => 0, 'published' => 0, 'blocked' => 0];

        // Retry only quality-approved legacy drafts; do not silently publish old unreviewed material.
        $ready = ContentItem::where('site_id', $site->id)->where('status', 'review')->oldest()->limit(min($perRun, max(0, $articleLeft)))->get();
        foreach ($ready as $item) {
            if (! data_get($item->quality, 'gate.passed')) {
                $stats['blocked']++;
                continue;
            }
            $this->publisher->publish($site, $item, 'seo-auto');
            $stats['published']++;
            $articleLeft--;
        }

        if ($articleLeft > 0 && $briefsLeft > 0) {
            $pendingBriefs = ContentItem::where('site_id', $site->id)->where('status', 'brief')->where('type', 'article')->oldest()->limit($perRun)->get();
            $taken = ContentItem::where('site_id', $site->id)->whereNotNull('keyword_id')->pluck('keyword_id');
            $dueIds = Task::where('site_id', $site->id)->where('playbook_key', 'kw.brief')->whereNotIn('status', ['done', 'skipped'])
                ->where('due_on', '<=', now()->addDays(3)->toDateString())->orderBy('due_on')->pluck('keyword_id');
            $keywords = $site->keywords()->targets()->whereNotIn('id', $taken)->whereIn('id', $dueIds)->get()
                ->sortBy(fn ($keyword) => $dueIds->search($keyword->id))->take(min($perRun - $pendingBriefs->count(), $briefsLeft))->values();

            foreach ($keywords as $keyword) {
                $pendingBriefs->push($this->writer->brief($site, $keyword));
                $stats['briefs']++;
            }
            foreach ($pendingBriefs as $item) {
                if ($articleLeft <= 0) break;
                $item = $this->writer->draft($site, $item);
                if ($item->status === 'review' && data_get($item->quality, 'gate.passed')) {
                    $stats['drafts']++;
                    $this->publisher->publish($site, $item, 'seo-auto');
                    $stats['published']++;
                    $articleLeft--;
                } else {
                    $stats['blocked']++;
                }
            }
        }

        if ($stats['blocked'] > 0) {
            return ['failed', sprintf('%s محتوا از کنترل کیفیت عبور نکرد؛ چیزی منتشر نشد. جزئیات در صف محتواست.', Fa::n($stats['blocked'])), $stats];
        }
        if (! array_sum($stats)) return ['skipped', 'مقاله‌ی سررسیدشده‌ای برای تولید نبود یا سقف ماهانه/هوش مصنوعی اجازه نمی‌داد.', $stats];

        return ['success', sprintf('%s بریف، %s پیش‌نویس و %s مقاله پس از کنترل کیفیت منتشر و به تلگرام گزارش شد.', Fa::n($stats['briefs']), Fa::n($stats['drafts']), Fa::n($stats['published'])), $stats];
    }
}
