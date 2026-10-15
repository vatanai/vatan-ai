<?php

namespace App\Console\Commands;

use App\Http\Controllers\TelegramProductDirectWebhookController;
use App\Models\TelegramProductSetting;
use App\Services\TelegramProduct\TelegramProductBotClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * جابه‌جایی وبهوک بات ثبت محصول بین n8n و اتصال مستقیم لاراول.
 *
 *   php artisan telegram:product-bot-webhook info     وضعیت فعلی
 *   php artisan telegram:product-bot-webhook direct   اتصال مستقیم (منوی پرتره/پروداکتی فعال می‌شود)
 *   php artisan telegram:product-bot-webhook n8n      برگشت به n8n (آدرس ذخیره‌شده)
 */
class TelegramProductBotWebhook extends Command
{
    protected $signature = 'telegram:product-bot-webhook {action=info : info|direct|n8n} {--url= : آدرس دلخواه} {--force : بدون پرسش}';

    protected $description = 'نمایش یا جابه‌جایی وبهوک بات ثبت محصول بین n8n و اتصال مستقیم';

    private const SAVED_KEY = 'n8n_webhook_url';

    public function handle(TelegramProductBotClient $telegram): int
    {
        if (! $telegram->configured()) {
            $this->error('TELEGRAM_PRODUCT_BOT_TOKEN تنظیم نشده است.');

            return self::FAILURE;
        }
        $info = (array) ($telegram->call('getWebhookInfo', []) ?? []);
        $current = (string) ($info['url'] ?? '');
        $direct = route('telegram.product.direct');

        return match ((string) $this->argument('action')) {
            'info' => $this->info_($info, $current, $direct),
            'direct' => $this->toDirect($telegram, $current, $direct),
            'n8n' => $this->toN8n($telegram, $current, $direct),
            default => $this->invalid(),
        };
    }

    private function info_(array $info, string $current, string $direct): int
    {
        $this->line('وبهوک فعلی: ' . ($current ?: '— (تنظیم نشده)'));
        $this->line('حالت: ' . ($current === $direct ? 'اتصال مستقیم ✅' : 'n8n / دیگر'));
        $this->line('آپدیت‌های در صف: ' . (int) ($info['pending_update_count'] ?? 0));
        if (! empty($info['last_error_message'])) {
            $this->warn('آخرین خطا: ' . $info['last_error_message'] . ' (' . date('Y-m-d H:i', (int) ($info['last_error_date'] ?? 0)) . ')');
        }
        $this->line('آدرس n8n ذخیره‌شده: ' . ($this->saved() ?: '—'));

        return self::SUCCESS;
    }

    private function toDirect(TelegramProductBotClient $telegram, string $current, string $direct): int
    {
        $url = (string) ($this->option('url') ?: $direct);
        $secret = TelegramProductDirectWebhookController::secret();
        if (! preg_match('/^[A-Za-z0-9_-]{8,256}$/', $secret)) {
            $this->error('برای اتصال مستقیم، TELEGRAM_PRODUCT_DIRECT_SECRET را در .env بگذارید (حداقل ۸ کاراکتر، فقط حروف انگلیسی، عدد، _ و -).');

            return self::FAILURE;
        }
        if (! str_starts_with($url, 'https://')) {
            $this->error("آدرس باید https باشد: {$url}");

            return self::FAILURE;
        }
        if ($current !== '' && $current !== $url) {
            $this->saveN8n($current);
            $this->line("آدرس فعلی n8n برای برگشت ذخیره شد: {$current}");
        }
        if (! $this->option('force') && ! $this->confirm("وبهوک بات به {$url} منتقل شود؟", true)) {
            return self::FAILURE;
        }
        $ok = $telegram->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => false,
        ]);
        if ($ok === null) {
            $this->error('تلگرام درخواست را رد کرد؛ لاگ را بررسی کنید.');

            return self::FAILURE;
        }
        $this->info('✅ بات ثبت محصول حالا مستقیم به سایت وصل است.');
        $this->warn('نکته: اگر ورک‌فلو n8n این بات دوباره فعال/ذخیره شود، ممکن است وبهوک را پس بگیرد؛ در آن صورت همین فرمان را دوباره بزنید.');

        return self::SUCCESS;
    }

    private function toN8n(TelegramProductBotClient $telegram, string $current, string $direct): int
    {
        $url = (string) ($this->option('url') ?: $this->saved());
        if ($url === '') {
            $this->error('آدرس n8n ذخیره نشده است. راه ساده: در n8n ورک‌فلو بات را یک بار غیرفعال و دوباره فعال کنید تا خودش وبهوک را ثبت کند؛ یا --url بدهید.');

            return self::FAILURE;
        }
        if (! $this->option('force') && ! $this->confirm("وبهوک بات به n8n ({$url}) برگردد؟", true)) {
            return self::FAILURE;
        }
        $ok = $telegram->call('setWebhook', ['url' => $url, 'drop_pending_updates' => false]);
        if ($ok === null) {
            $this->error('تلگرام درخواست را رد کرد.');

            return self::FAILURE;
        }
        $this->info('✅ وبهوک به n8n برگشت.');
        $this->warn('اگر تریگر n8n از secret استفاده می‌کند، ورک‌فلو را در n8n یک بار غیرفعال و دوباره فعال کنید تا وبهوک با secret خودش ثبت شود.');

        return self::SUCCESS;
    }

    private function invalid(): int
    {
        $this->error('action باید یکی از info، direct یا n8n باشد.');

        return self::FAILURE;
    }

    private function saved(): string
    {
        return Schema::hasTable('telegram_product_settings') ? (string) TelegramProductSetting::value(self::SAVED_KEY, '') : '';
    }

    private function saveN8n(string $url): void
    {
        if (Schema::hasTable('telegram_product_settings')) {
            TelegramProductSetting::put(self::SAVED_KEY, $url);
        }
    }
}
