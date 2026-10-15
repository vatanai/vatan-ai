<?php

namespace Vatan\Content\Console;

use Illuminate\Console\Command;
use Vatan\Content\Services\LabSync;

class SyncLabCommand extends Command
{
    protected $signature = 'content:sync-lab {--only=* : styles|references|music|calendar|productions} {--path= : مسیر content-lab}';
    protected $description = 'همگام‌سازی پوشه‌ی content-lab با داشبورد کارخانه‌ی محتوا';

    public function handle(): int
    {
        $log = (new LabSync($this->option('path') ?: null))->run($this->option('only') ?: []);
        foreach ($log as $l) {
            $this->line(' • '.$l);
        }
        $this->info('همگام‌سازی انجام شد.');

        return self::SUCCESS;
    }
}
