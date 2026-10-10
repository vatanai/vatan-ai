<?php

namespace Vatan\Seo\Telegram;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Vatan\Seo\Models\TelegramAdmin;

/**
 * بات اختصاصی سئو (@seovatanai_bot): هشدار، گزارش، و تأیید/رد مقاله با یک دکمه.
 * فقط چت‌هایی که با «کد اتصال» از داخل پنل وصل شده‌اند پیام دریافت می‌کنند و دستور می‌دهند.
 */
class SeoBot
{
    public function token(): string
    {
        return (string) config('seo-engine.telegram.bot_token');
    }

    public function configured(): bool
    {
        return $this->token() !== '';
    }

    public function api(string $method, array $params = []): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'description' => 'توکن بات تنظیم نشده است.'];
        }
        try {
            $res = Http::connectTimeout(8)->timeout(20)
                ->post(rtrim((string) config('seo-engine.telegram.api_base', 'https://api.telegram.org'), '/').'/bot'.$this->token().'/'.$method, $params);
            return (array) $res->json() + ['ok' => $res->successful()];
        } catch (\Throwable $e) {
            Log::warning('SEO bot: telegram call failed', ['method' => $method, 'error' => $e->getMessage()]);
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /** @param array<int, array<int, array{text:string, callback_data?:string, url?:string}>>|null $buttons */
    public function send(string $chatId, string $html, ?array $buttons = null): array
    {
        $params = ['chat_id' => $chatId, 'text' => Str::limit($html, 4000), 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
        if ($buttons) {
            $params['reply_markup'] = ['inline_keyboard' => $buttons];
        }
        return $this->api('sendMessage', $params);
    }

    public function broadcast(string $html, ?array $buttons = null): int
    {
        $sent = 0;
        foreach (TelegramAdmin::where('is_active', true)->get() as $admin) {
            if (($this->send($admin->chat_id, $html, $buttons)['ok'] ?? false) === true) {
                $sent++;
            }
        }
        return $sent;
    }

    /** کد یک‌بارمصرف ۳۰ دقیقه‌ای برای اتصال چت تلگرام مدیر */
    public function linkCode(?int $adminId): string
    {
        $code = strtoupper(Str::random(6));
        Cache::put('seo-engine:tg-link:'.$code, ['admin_id' => $adminId], now()->addMinutes(30));
        return $code;
    }

    public function consumeLinkCode(string $code): ?array
    {
        $key = 'seo-engine:tg-link:'.strtoupper(trim($code));
        $data = Cache::get($key);
        Cache::forget($key);
        return is_array($data) ? $data : null;
    }

    public function webhookUrl(): string
    {
        return route(config('seo-engine.host.route_name', 'seo.').'telegram.webhook');
    }

    public function setup(): array
    {
        $secret = (string) config('seo-engine.telegram.webhook_secret');
        return [
            'setWebhook' => $this->api('setWebhook', array_filter([
                'url' => $this->webhookUrl(),
                'secret_token' => $secret ?: null,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => true,
            ])),
            'setMyCommands' => $this->api('setMyCommands', ['commands' => [
                ['command' => 'today', 'description' => 'برنامه و خلاصه‌ی امروز'],
                ['command' => 'keywords', 'description' => 'رتبه‌ی کلمات هدف'],
                ['command' => 'report', 'description' => 'گزارش هفتگی همین حالا'],
                ['command' => 'review', 'description' => 'مقاله‌های منتظر تأیید'],
                ['command' => 'budget', 'description' => 'هزینه‌ی هوش مصنوعی این ماه'],
                ['command' => 'ask', 'description' => 'سؤال از مدیر سئو (با داده‌ی واقعی)'],
                ['command' => 'write', 'description' => 'نوشتن مقاله برای یک کلمه'],
                ['command' => 'plan', 'description' => 'برنامه‌ی هفته از استراتژیست'],
                ['command' => 'discover', 'description' => 'کشف کلمات تازه'],
                ['command' => 'audit', 'description' => 'خزش و ممیزی فنی'],
                ['command' => 'help', 'description' => 'راهنما'],
            ]]),
            'setMyDescription' => $this->api('setMyDescription', ['description' => 'دستیار سئوی هوشمند وطن: هشدار رتبه، گزارش روزانه و هفتگی، و تأیید مقاله‌ها با یک دکمه.']),
        ];
    }

    public function webhookInfo(): array
    {
        return $this->api('getWebhookInfo');
    }
}
