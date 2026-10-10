<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Agents\ContentWriter;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Reporter;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Telegram\SeoBot;

/** تا سقف پروفایل: بریف و پیش‌نویس برای کلمات هدفی که هنوز محتوا ندارند ← ارسال برای تأیید */
class ContentPipeline implements Handler
{
    public function __construct(private ContentWriter $writer, private Ai $ai, private SeoBot $bot, private Reporter $reporter) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $perRun = (int) ($scenario->config['per_run'] ?? 2);
        $articleLeft = Budget::limit($site, 'articles_per_month') - Budget::articlesThisMonth($site);
        $briefsLeft = Budget::limit($site, 'briefs_per_month') - ContentItem::where('site_id', $site->id)->whereNotNull('brief')->where('created_at', '>=', now()->startOfMonth())->count();
        if ($briefsLeft <= 0) {
            return ['skipped', 'سقف بریف ماهانه‌ی پروفایل پر شده است.'];
        }
        if (! $this->ai->available($site, 'strategist')) {
            return ['skipped', 'مدل هوش مصنوعی در دسترس نیست (کلید OpenRouter یا پروفایل بودجه).'];
        }

        // ادامه‌ی بریف‌های قبلی که پیش‌نویس ندارند، سپس کلمات هدف بدون محتوا
        $pendingBriefs = ContentItem::where('site_id', $site->id)->where('status', 'brief')->where('type', 'article')->oldest()->limit($perRun)->get();
        $taken = ContentItem::where('site_id', $site->id)->whereNotNull('keyword_id')->pluck('keyword_id');
        // طبق موج‌بندی برنامه‌ی ۹۰ روزه: کلماتی که تسک «بریف»شان تا ۳ روز آینده سررسید دارد
        $dueIds = \Vatan\Seo\Models\Task::where('site_id', $site->id)->where('playbook_key', 'kw.brief')->whereNotIn('status', ['done', 'skipped'])
            ->where('due_on', '<=', now()->addDays(3)->toDateString())->orderBy('due_on')->pluck('keyword_id');
        $keywords = $site->keywords()->targets()->whereNotIn('id', $taken)->whereIn('id', $dueIds)->get()
            ->sortBy(fn ($k) => $dueIds->search($k->id))->take(max(0, $perRun - $pendingBriefs->count()))->values();

        $made = ['briefs' => 0, 'drafts' => 0, 'sent' => 0];
        foreach ($keywords as $kw) {
            $pendingBriefs->push($this->writer->brief($site, $kw));
            $made['briefs']++;
        }
        foreach ($pendingBriefs as $item) {
            if ($articleLeft <= 0) {
                break;
            }
            $item = $this->writer->draft($site, $item);
            if ($item->status === 'review') {
                $made['drafts']++;
                $articleLeft--;
                if ($this->bot->configured()) {
                    [$text, $buttons] = $this->reporter->approvalCard($item);
                    $made['sent'] += $this->bot->broadcast($text, $buttons) > 0 ? 1 : 0;
                }
            }
        }
        if (! $made['briefs'] && ! $made['drafts']) {
            return ['skipped', 'کلمه‌ی هدف بدون محتوا یا سهمیه‌ی مقاله باقی نمانده است.'];
        }
        return ['success', sprintf('%s بریف و %s پیش‌نویس ساخته شد؛ %s مورد برای تأیید به تلگرام رفت.', Fa::n($made['briefs']), Fa::n($made['drafts']), Fa::n($made['sent'])), $made];
    }
}
