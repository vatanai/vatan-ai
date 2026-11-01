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

        // Existing briefs and interrupted drafts do not consume new-brief quota.
        if ($articleLeft > 0) {
            $pendingBriefs = ContentItem::where('site_id', $site->id)->where('status', 'brief')->where('type', 'article')->oldest()->limit($perRun)->get();

            // Recover interrupted drafts and one-time transient/quality failures.
            // Without this, a single timeout leaves an item in `drafting` or
            // `failed`; its keyword is then permanently excluded by `taken`.
            $recoverable = ContentItem::where('site_id', $site->id)
                ->where('type', 'article')
                ->whereIn('status', ['drafting', 'failed'])
                ->whereNotNull('brief')
                ->whereNotNull('keyword_id')
                ->where('updated_at', '<=', now()->subMinutes(30))
                ->oldest()
                ->limit($perRun)
                ->get()
                ->filter(fn (ContentItem $item) => (int) data_get($item->brief, 'pipeline_retry_count', 0) < 1)
                ->take(max(0, $perRun - $pendingBriefs->count()))
                ->values();

            foreach ($recoverable as $item) {
                $brief = (array) $item->brief;
                $brief['pipeline_retry_count'] = (int) ($brief['pipeline_retry_count'] ?? 0) + 1;
                $item->update(['brief' => $brief, 'status' => 'brief', 'reviewer_note' => null]);
                $pendingBriefs->push($item->fresh());
            }

            $retryKeywordIds = $recoverable->pluck('keyword_id')->all();
            $taken = ContentItem::where('site_id', $site->id)
                ->whereNotNull('keyword_id')
                ->when($recoverable->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $recoverable->pluck('id')))
                ->pluck('keyword_id');
            $dueIds = Task::where('site_id', $site->id)->where('playbook_key', 'kw.brief')->whereNotIn('status', ['done', 'skipped'])
                ->where('due_on', '<=', now()->addDays(3)->toDateString())->orderBy('due_on')->pluck('keyword_id');
            $newBriefCapacity = max(0, min($perRun - $pendingBriefs->count(), $briefsLeft));
            $keywords = $site->keywords()->targets()->whereNotIn('id', $taken)
                ->when($retryKeywordIds, fn ($q) => $q->whereNotIn('id', $retryKeywordIds))
                ->whereIn('id', $dueIds)->get()
                ->sortBy(fn ($keyword) => $dueIds->search($keyword->id))->take($newBriefCapacity)->values();

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
        if (! array_sum($stats)) {
            $nextBrief = Task::where('site_id', $site->id)->where('playbook_key', 'kw.brief')
                ->whereNotIn('status', ['done', 'skipped'])->whereNotNull('due_on')->min('due_on');
            $reason = $articleLeft <= 0
                ? 'سقف مقاله‌ی ماهانه مصرف شده است.'
                : ($briefsLeft <= 0 ? 'سقف بریف ماهانه مصرف شده است.'
                    : 'هنوز تسک بریفِ سررسیدشده‌ای برای کلمات هدف وجود ندارد.');
            if ($nextBrief) $reason .= ' نوبت بعدی: '.Fa::n($nextBrief).'.';
            return ['skipped', $reason, $stats + ['next_brief_due' => $nextBrief]];
        }

        return ['success', sprintf('%s بریف، %s پیش‌نویس و %s مقاله پس از کنترل کیفیت منتشر و به تلگرام گزارش شد.', Fa::n($stats['briefs']), Fa::n($stats['drafts']), Fa::n($stats['published'])), $stats];
    }
}
