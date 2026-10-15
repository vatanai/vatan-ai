<?php

namespace Vatan\Content\Console;

use Illuminate\Console\Command;
use Vatan\Content\Support\Seed;

class SeedCommand extends Command
{
    protected $signature = 'content:seed';
    protected $description = 'بارگذاری دوباره‌ی داده‌ی پایه‌ی کارخانه (بدون بازنویسی ویرایش‌های ادمین)';

    public function handle(): int
    {
        foreach (Seed::run((int) config('content-factory.workspace_id', 1)) as $l) {
            $this->line(' • '.$l);
        }

        return self::SUCCESS;
    }
}
