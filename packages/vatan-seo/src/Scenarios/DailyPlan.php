<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Services\AutoPublisher;
use Vatan\Seo\Services\Notifier;
use Vatan\Seo\Services\Reporter;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Telegram\SeoBot;

/** استراتژیست صبحگاهی: به‌روزرسانی تسک‌های دوره‌ای، اجرای خودکارهای سررسید، ارسال خلاصه */
class DailyPlan implements Handler
{
    public function __construct(private Installer $installer, private AuditRunner $runner, private Reporter $reporter, private SeoBot $bot, private AutoPublisher $publisher, private Notifier $notifier) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $this->installer->syncRecurring($site); // افق ۱۳ هفته‌ای + منقضی کردن روزانه‌های گذشته
        $this->publisher->retryUndeliveredReports($site);
        $this->notifier->retryUndelivered($site);
        $due = Task::where('site_id', $site->id)->open()->whereIn('automation', ['auto', 'assisted'])->whereNotNull('check')
            ->where('due_on', '<=', now()->toDateString())->orderByDesc('priority')->limit(15)->get();
        foreach ($due as $task) {
            $this->runner->apply($site, $task);
        }
        $needsHumanAction = Task::where('site_id', $site->id)->open()->whereNotNull('due_on')
            ->where('due_on', '<=', now()->toDateString())->where('automation', '!=', 'auto')->exists();
        $sent = $this->bot->configured() ? $this->bot->broadcast($this->reporter->daily($site), null, $needsHumanAction) : 0;
        $open = Task::where('site_id', $site->id)->open()->count();
        return ['success', sprintf('%s تسک سررسید بررسی شد؛ %s تسک باز. خلاصه برای %s مدیر ارسال شد.', Fa::n($due->count()), Fa::n($open), Fa::n($sent))];
    }
}
