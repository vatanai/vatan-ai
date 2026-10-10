<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Vatan\Seo\Telegram\SeoBot;

class TelegramCommand extends Command
{
    protected $signature = 'seo:telegram {action=setup : setup|info|code}';
    protected $description = 'بات تلگرام سئو: ثبت وب‌هوک و دستورها، وضعیت، یا ساخت کد اتصال';

    public function handle(SeoBot $bot): int
    {
        if (! $bot->configured()) {
            $this->warn('SEO_TELEGRAM_BOT_TOKEN در .env تنظیم نشده است.');
            return self::SUCCESS;
        }
        match ($this->argument('action')) {
            'info' => $this->line(json_encode($bot->webhookInfo(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)),
            'code' => $this->info('کد اتصال (۳۰ دقیقه): '.$bot->linkCode(null).' — در بات بفرستید: /start CODE'),
            default => collect($bot->setup())->each(fn ($r, $m) => ($r['ok'] ?? false) ? $this->info('✔ '.$m) : $this->error('✘ '.$m.': '.($r['description'] ?? ''))),
        };
        return self::SUCCESS;
    }
}
