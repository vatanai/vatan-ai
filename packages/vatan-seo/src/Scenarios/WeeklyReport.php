<?php

namespace Vatan\Seo\Scenarios;

use Vatan\Seo\Models\Scenario;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;
use Vatan\Seo\Services\Reporter;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Telegram\SeoBot;

class WeeklyReport implements Handler
{
    public function __construct(private Reporter $reporter, private SeoBot $bot) {}

    public function handle(Site $site, Scenario $scenario): array
    {
        $text = $this->reporter->weekly($site);
        $needsHumanAction = Task::where('site_id', $site->id)->open()->whereNotNull('due_on')
            ->where('due_on', '<=', now()->toDateString())->where('automation', '!=', 'auto')->exists();
        $sent = $this->bot->configured() ? $this->bot->broadcast($text, null, $needsHumanAction) : 0;
        return [$sent ? 'success' : 'warning', $sent ? 'گزارش هفتگی برای '.Fa::n($sent).' مدیر ارسال شد.' : 'گزارش ساخته شد ولی هیچ مدیری به بات متصل نیست.', ['text' => strip_tags($text)]];
    }
}
