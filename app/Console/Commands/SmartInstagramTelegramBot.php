<?php

namespace App\Console\Commands;

use App\Services\SmartInstagram\Posts\PostSyncService;
use App\Services\SmartInstagram\Telegram\InstagramTelegramBot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * بات تلگرام «ثبت پست»:
 *  setup  — ثبت وب‌هوک، دستورها، دکمه‌ی منو و توضیح بات (یک بار بعد از دیپلوی/تغییر دامنه)
 *  notify — دریافت پست‌های تازه و اعلان آن‌ها با دکمه‌ی «تنظیم» (زمان‌بندی‌شده)
 */
class SmartInstagramTelegramBot extends Command
{
    protected $signature = 'smart-instagram:telegram-bot {action=setup : setup|notify} {--no-sync : اعلان بدون دریافت دوباره‌ی پست‌ها}';

    protected $description = 'راه‌اندازی بات تلگرام ثبت پست و اعلان پست‌های تازه‌ی اینستاگرام';

    public function handle(InstagramTelegramBot $bot, PostSyncService $sync): int
    {
        if (!$bot->configured()) {
            $this->warn('TELEGRAM_INSTAGRAM_BOT_TOKEN در .env تنظیم نشده است.');

            return self::SUCCESS;
        }

        if ($this->argument('action') === 'notify') {
            if (!Schema::hasTable('instagram_telegram_admins') || \App\Models\SmartInstagram\TelegramAdmin::query()->where('is_active', true)->doesntExist()) {
                return self::SUCCESS; // هنوز کسی وصل نشده؛ تماس بی‌مورد با اینستاگرام لازم نیست.
            }
            if (!$this->option('no-sync')) {
                $result = $sync->syncRecent(10);
                $this->line($result['message']);
            }
            $this->info($bot->notifyNewPosts().' پست تازه اعلان شد.');

            return self::SUCCESS;
        }

        foreach ($bot->setup() as $method => $result) {
            ($result['ok'] ?? false)
                ? $this->info('✔ '.$method)
                : $this->error('✘ '.$method.': '.($result['description'] ?? 'نامشخص'));
        }
        $this->line('آدرس مینی‌اپ: '.$bot->appUrl());

        return self::SUCCESS;
    }
}
