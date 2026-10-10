<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\Installer;
use Vatan\Seo\Services\SiteManager;

class InstallCommand extends Command
{
    protected $signature = 'seo:install {--budget= : سقف ماهانه‌ی هوش مصنوعی به دلار (مثلاً 20 یا 30)} {--url= : آدرس سایت}';
    protected $description = 'ساخت سایت پیش‌فرض و همگام‌سازی تسک‌ها و سناریوها از پلی‌بوک (ایدمپوتنت)';

    public function handle(SiteManager $sites, Installer $installer): int
    {
        $site = Site::query()->orderBy('id')->first() ?? $sites->createDefault();
        if ($url = $this->option('url')) {
            $site->update(['base_url' => rtrim($url, '/'), 'domain' => preg_replace('/^www\./', '', (string) parse_url($url, PHP_URL_HOST))]);
        }
        if ($this->option('budget') !== null) {
            $site->update(['monthly_budget_usd' => (float) $this->option('budget')]);
        }
        $r = $installer->install($site);
        $this->info("سایت: {$site->name} ({$site->domain}) — پروفایل: ".data_get($site->profile(), 'label'));
        $this->info("تسک‌های جدید: {$r['tasks']} — سناریوهای جدید: {$r['scenarios']} — مجموع: ".$site->tasks()->count().' تسک، '.$site->scenarios()->count().' سناریو');
        return self::SUCCESS;
    }
}
