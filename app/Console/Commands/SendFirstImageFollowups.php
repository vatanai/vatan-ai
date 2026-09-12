<?php

namespace App\Console\Commands;

use App\Services\SmsEventService;
use Illuminate\Console\Command;

class SendFirstImageFollowups extends Command
{
    protected $signature = 'sms:send-first-image-followups {--limit=100}';
    protected $description = 'ارسال پیام پیگیری سی دقیقه بعد از اولین ساخت تصویر در بازه مجاز';

    public function handle(SmsEventService $sms): int
    {
        $result = $sms->sendDueFirstImageFollowups((int) $this->option('limit'));
        $this->info(sprintf(
            '%d پیام ارسال شد؛ %d مورد ناموفق بود؛ %d مورد رد شد.',
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
