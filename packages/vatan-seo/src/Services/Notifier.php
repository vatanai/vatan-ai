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
}
