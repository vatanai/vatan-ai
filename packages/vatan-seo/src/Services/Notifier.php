<?php

namespace Vatan\Seo\Services;

use Vatan\Seo\Models\Alert;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Telegram\SeoBot;

/** هشدار در داشبورد + ارسال به بات تلگرام سئو (در صورت اتصال) */
class Notifier
{
    public function __construct(private SeoBot $bot) {}

    public function alert(Site $site, string $level, string $type, string $title, ?string $body = null, array $data = [], bool $telegram = true, ?array $buttons = null): Alert
    {
        $alert = Alert::create(compact('level', 'type', 'title', 'body', 'data') + ['site_id' => $site->id]);
        if ($telegram && $this->bot->configured()) {
            $icon = ['danger' => '🔴', 'warning' => '🟠', 'success' => '🟢'][$level] ?? '🔵';
            $text = "{$icon} <b>".e($title).'</b>'.($body ? "\n\n".e($body) : '')."\n\n<i>".e($site->name).'</i>';
            if ($this->bot->broadcast($text, $buttons) > 0) {
                $alert->update(['telegram_sent_at' => now()]);
            }
        }
        return $alert;
    }

    /** Retry recent alerts that could not be delivered while Telegram was unavailable. */
    public function retryUndelivered(Site $site, int $limit = 20): int
    {
        if (! $this->bot->configured()) return 0;
        $sent = 0;
        $alerts = Alert::where('site_id', $site->id)->whereNull('telegram_sent_at')
            ->where('created_at', '>=', now()->subDays(14))->oldest()->limit(max(1, min(50, $limit)))->get();
        foreach ($alerts as $alert) {
            $icon = ['danger' => '🔴', 'warning' => '🟠', 'success' => '🟢'][$alert->level] ?? '🔵';
            $text = "{$icon} <b>".e($alert->title).'</b>'.($alert->body ? "\n\n".e($alert->body) : '')."\n\n<i>".e($site->name).'</i>';
            if ($this->bot->broadcast($text, null, $alert->level === 'danger') > 0) {
                $alert->update(['telegram_sent_at' => now()]);
                $sent++;
            }
        }
        return $sent;
    }
}
