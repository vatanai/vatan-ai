<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Agents\Publisher;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\TelegramAdmin;
use Vatan\Seo\Telegram\SeoBot;

/** Shared unattended publication + concise delivery report. */
class AutoPublisher
{
    public function __construct(private Publisher $publisher, private Reporter $reporter, private SeoBot $bot, private Notifier $notifier) {}

    public function publish(Site $site, ContentItem $item, string $source = 'seo-engine'): ContentItem
    {
        if ($item->status === 'published') return $item;
        if (! $this->bot->configured() || ! TelegramAdmin::where('is_active', true)->exists()) {
            throw new \RuntimeException('برای انتشار خودکار، مقصد تلگرام باید فعال و متصل باشد تا لینک مقاله گزارش شود.');
        }
        $item = $this->publisher->publish($site, $item, $source);
        if ($item->status !== 'published') return $item;

        $text = $this->reporter->published($item);
        if (! $this->bot->configured() || $this->bot->broadcast($text) < 1) {
            $this->notifier->alert($site, 'danger', 'article_report_failed', 'گزارش انتشار مقاله به تلگرام نرسید',
                'مقاله منتشر شده است، اما ارسال گزارش به هیچ مقصد متصل‌شده‌ای تأیید نشد. بررسی اتصال بات/گروه لازم است.',
                ['content_item_id' => $item->id, 'url' => $item->published_url]);
            throw new \RuntimeException('مقاله منتشر شد اما گزارش آن به مقصد تلگرام تحویل نشد؛ هشدار در داشبورد ثبت شد.');
        }
        return $item;
    }

    public function retryUndeliveredReports(Site $site): int
    {
        if (! $this->bot->configured()) return 0;
        $retried = 0;
        $alerts = Alert::where('site_id', $site->id)->where('type', 'article_report_failed')->whereNull('telegram_sent_at')
            ->where('created_at', '>=', now()->subDays(14))->oldest()->limit(10)->get();
        foreach ($alerts as $alert) {
            $item = ContentItem::where('site_id', $site->id)->find((int) data_get($alert->data, 'content_item_id'));
            if ($item?->status === 'published' && $this->bot->broadcast($this->reporter->published($item)) > 0) {
                $alert->update(['telegram_sent_at' => now()]);
                $retried++;
            }
        }
        return $retried;
    }
}
