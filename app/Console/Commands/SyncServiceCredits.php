<?php

namespace App\Console\Commands;

use App\Services\ServiceCreditSynchronizer;
use Illuminate\Console\Command;

class SyncServiceCredits extends Command
{
    protected $signature = 'credits:sync';
    protected $description = 'موجودی آنلاین سرویس‌ها را همگام و تغییرات را به تراکنش تبدیل می‌کند';

    public function handle(ServiceCreditSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync();
        $this->info("{$result['synced']} سرویس همگام شد؛ {$result['transactions_created']} تراکنش ساخته شد.");
        return self::SUCCESS;
    }
}
