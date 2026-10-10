<?php

namespace Vatan\Seo\Console;

use Illuminate\Console\Command;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Ai\ModelRouter;
use Vatan\Seo\Ai\OpenRouterClient;
use Vatan\Seo\Models\AiCall;
use Vatan\Seo\Services\SiteManager;

/** تست واقعی اتصال هوش مصنوعی: کلید، اعتبار، فهرست مدل‌ها و یک تماس کوچک برای هر نقش (کمتر از ۱ سنت) */
class AiTestCommand extends Command
{
    protected $signature = 'seo:ai-test {--role=* : فقط نقش‌های مشخص (fast, strategist, writer, research)}';
    protected $description = 'تست اتصال OpenRouter و مدل هر نقش با یک تماس کوچک فارسی';

    public function handle(SiteManager $sites, OpenRouterClient $client, ModelRouter $router, Ai $ai): int
    {
        $site = $sites->current();
        if (! $client->configured()) {
            $this->error('کلید OpenRouter تنظیم نشده (OPENROUTER_API_KEY یا SEO_OPENROUTER_API_KEY).');
            return self::FAILURE;
        }
        $this->line('Endpointها: '.implode(' → ', $client->baseUrls()));
        $info = $client->keyInfo();
        $this->line($info ? sprintf('کلید: %s · مصرف کل $%s · سقف کلید %s', $info['label'] ?? '—', number_format((float) ($info['usage'] ?? 0), 2), isset($info['limit']) ? '$'.$info['limit'] : 'ندارد') : 'اطلاعات کلید دریافت نشد (پل کلادفلر قدیمی یا دسترسی مسدود).');
        $models = $client->models(true);
        $this->line('مدل‌های زنده: '.(count($models) ?: 'دریافت نشد (کاندیدها به‌ترتیب امتحان می‌شوند)'));

        $roles = $this->option('role') ?: ['fast', 'strategist', 'writer', 'research'];
        foreach ($roles as $role) {
            $candidates = $router->candidates($site, $role);
            if (! $candidates) {
                $this->warn("{$role}: در پروفایل بودجه‌ی فعلی خاموش است.");
                continue;
            }
            $before = AiCall::max('id') ?? 0;
            try {
                $text = $ai->text($site, $role, 'connection-test', 'به فارسی و فقط در یک جمله‌ی کوتاه جواب بده.', $role === 'research' ? 'امروز در ایران چه تاریخی است؟ (با جستجوی وب)' : 'سئو را در یک جمله تعریف کن.', ['max_tokens' => 80, 'critical' => true, 'web' => $role === 'research']);
                $call = AiCall::where('id', '>', $before)->latest('id')->first();
                $this->info(sprintf('✔ %s ← %s ($%s): %s', $role, $call?->model, number_format((float) $call?->cost_usd, 5), mb_substr(trim($text), 0, 90)));
            } catch (\Throwable $e) {
                $this->error("✘ {$role} (".implode(', ', array_slice($candidates, 0, 2))."): ".mb_substr($e->getMessage(), 0, 200));
            }
        }
        return self::SUCCESS;
    }
}
