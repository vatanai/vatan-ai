<?php

namespace App\Services\SmartInstagram\Telegram;

use App\Models\Admin;
use App\Models\SmartInstagram\Post;
use App\Models\SmartInstagram\PostCampaign;
use App\Models\SmartInstagram\TelegramAdmin;
use App\Services\SmartInstagram\Posts\PostCampaignService;
use App\Services\SmartInstagram\WorkspaceContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * بات تلگرام «ثبت پست» (@vatan_instagram_dashbord_bot):
 * اتصال حساب تلگرام ادمین، اعلان پست تازه با دکمه‌ی باز کردن مینی‌اپ و پاسخ به دستورهای ساده.
 * تنظیم پست خودش داخل مینی‌اپ (TelegramAppController) انجام می‌شود.
 */
class InstagramTelegramBot
{
    private const LINK_PREFIX = 'smart-ig-tg-link:';

    public function __construct(private readonly WorkspaceContext $context)
    {
    }

    public function token(): string
    {
        return trim((string) config('services.telegram_instagram.bot_token'));
    }

    public function configured(): bool
    {
        return $this->token() !== '';
    }

    public function username(): string
    {
        return ltrim(trim((string) config('services.telegram_instagram.bot_username', 'vatan_instagram_dashbord_bot')), '@');
    }

    /** سکرت وب‌هوک؛ اگر در .env نباشد از روی توکن ساخته می‌شود تا تنظیم دستی لازم نباشد. */
    public function webhookSecret(): string
    {
        $secret = trim((string) config('services.telegram_instagram.webhook_secret'));

        return $secret !== '' ? $secret : substr(hash_hmac('sha256', 'vatan-instagram-webhook', $this->token()), 0, 48);
    }

    public function appUrl(?Post $post = null): string
    {
        return route('telegram.instagram.app', $post ? ['post' => $post->id] : []);
    }

    /** لینک یک‌بارمصرف (۱۵ دقیقه) برای وصل‌کردن حساب تلگرام ادمین فعلی. */
    public function linkUrl(Admin $admin): string
    {
        $token = Str::random(32);
        Cache::put(self::LINK_PREFIX.$token, (int) $admin->id, now()->addMinutes(15));

        return 'https://t.me/'.$this->username().'?start=link_'.$token;
    }

    /** @return array{ok:bool,result?:mixed,description?:string} */
    public function call(string $method, array $params = []): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'description' => 'توکن بات تنظیم نشده است.'];
        }
        try {
            $response = Http::connectTimeout(8)->timeout(20)->asJson()
                ->post('https://api.telegram.org/bot'.$this->token().'/'.$method, $params);
            $json = (array) $response->json();

            return $json + ['ok' => false, 'description' => 'HTTP '.$response->status()];
        } catch (\Throwable $e) {
            Log::warning('smart-instagram telegram bot call failed', ['method' => $method, 'error' => $e->getMessage()]);

            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /** پردازش آپدیت وب‌هوک (فقط پیام خصوصی). */
    public function handleUpdate(array $update): void
    {
        $message = (array) ($update['message'] ?? []);
        $chat = (array) ($message['chat'] ?? []);
        $from = (array) ($message['from'] ?? []);
        if (($chat['type'] ?? '') !== 'private' || empty($from['id'])) {
            return;
        }
        $chatId = (int) $chat['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $command = strtolower((string) Str::before(Str::before($text, ' '), '@'));
        $arg = trim(Str::after($text, ' '));
        if ($arg === $text) {
            $arg = '';
        }

        if ($command === '/start' && str_starts_with($arg, 'link_')) {
            $this->link($chatId, $from, substr($arg, 5));

            return;
        }

        $account = $this->account((int) $from['id']);
        if (!$account) {
            $this->send($chatId, "🔒 این بات مخصوص تیم وطن است.\n\nشناسه‌ی تلگرام شما: <code>".$from['id']."</code>\nاین شناسه را برای مدیر بفرستید تا در پنل (اینستاگرام هوشمند › اتصال‌ها و تیم › «کارمندان بات تلگرام») اضافه‌تان کند.");

            return;
        }
        $account->forceFill([
            'last_seen_at' => now(),
            'username' => $from['username'] ?? $account->username,
            'first_name' => $account->first_name ?: Str::limit((string) ($from['first_name'] ?? ''), 110, ''),
        ])->save();

        match ($command) {
            '/posts' => $this->sendRecentPosts($chatId),
            '/mute' => $this->toggleNotify($account, $chatId, false),
            '/unmute' => $this->toggleNotify($account, $chatId, true),
            default => $this->welcome($chatId, $account),
        };
    }

    public function account(int $telegramId): ?TelegramAdmin
    {
        $account = TelegramAdmin::query()->where('telegram_id', $telegramId)->where('is_active', true)->with('admin')->first();
        if (!$account || ($account->admin_id && !$account->admin?->is_active)) {
            return null;
        }

        return $account;
    }

    /** اعلان پست‌های تازه‌ی بدون سناریو به همه‌ی ادمین‌های وصل‌شده. خروجی: تعداد پست اعلان‌شده. */
    public function notifyNewPosts(): int
    {
        if (!$this->configured() || !config('services.telegram_instagram.notify_new_posts', true)) {
            return 0;
        }
        $ws = $this->context->id();
        $since = now()->subHours(max(1, (int) config('services.telegram_instagram.notify_max_age_hours', 48)));

        // پست‌های قدیمی یا دارای سناریو بی‌صدا علامت می‌خورند تا هیچ‌وقت سیل اعلان نیاید.
        Post::query()->where('workspace_id', $ws)->whereNull('telegram_notified_at')
            ->where(fn ($q) => $q->where('published_at', '<', $since)->orWhereNull('published_at')->orWhereHas('campaign')->orWhere('source', 'manual'))
            ->update(['telegram_notified_at' => now()]);

        $posts = Post::query()->where('workspace_id', $ws)->whereNull('telegram_notified_at')
            ->where('connection_status', 'verified')->where('published_at', '>=', $since)
            ->orderBy('published_at')->limit(5)->get();
        if ($posts->isEmpty()) {
            return 0;
        }
        $recipients = TelegramAdmin::query()->where('workspace_id', $ws)->where('is_active', true)->where('notify_new_posts', true)
            ->with('admin')->get()
            ->filter(fn (TelegramAdmin $a) => (!$a->admin_id || $a->admin?->is_active) && $a->allows('manage_automation'));

        foreach ($posts as $post) {
            $post->forceFill(['telegram_notified_at' => now()])->save();
            foreach ($recipients as $recipient) {
                $this->sendPostCard((int) $recipient->telegram_id, $post);
            }
        }

        return $posts->count();
    }

    public function sendPostCard(int $chatId, Post $post, ?string $headline = null): void
    {
        $headline ??= '📸 '.$post->kindLabel().' تازه منتشر شد';
        $caption = '<b>'.e($headline)."</b>\n\n".e($post->shortCaption(220))."\n\nکامنت و دایرکت هوشمندش هنوز تنظیم نشده؛ با دکمه‌ی زیر در کمتر از یک دقیقه آماده‌اش کن 👇";
        $keyboard = ['inline_keyboard' => array_values(array_filter([
            [['text' => '⚙️ تنظیم کامنت و دایرکت', 'web_app' => ['url' => $this->appUrl($post)]]],
            $post->permalink ? [['text' => 'مشاهده در اینستاگرام ↗', 'url' => $post->permalink]] : null,
        ]))];
        $cover = $post->coverUrl();
        if ($cover && str_starts_with($cover, 'https://')) {
            $sent = $this->call('sendPhoto', ['chat_id' => $chatId, 'photo' => $cover, 'caption' => $caption, 'parse_mode' => 'HTML', 'reply_markup' => $keyboard]);
            if ($sent['ok'] ?? false) {
                return;
            }
        }
        $this->send($chatId, $caption, $keyboard);
    }

    /** ثبت دستورها، دکمه‌ی منو و وب‌هوک بات. @return array<string,array> */
    public function setup(): array
    {
        $appUrl = $this->appUrl();

        return [
            'setWebhook' => $this->call('setWebhook', [
                'url' => route('webhooks.telegram.instagram'),
                'secret_token' => $this->webhookSecret(),
                'allowed_updates' => ['message'],
                'drop_pending_updates' => true,
            ]),
            'setMyCommands' => $this->call('setMyCommands', ['commands' => [
                ['command' => 'start', 'description' => 'باز کردن پنل ثبت پست'],
                ['command' => 'posts', 'description' => 'آخرین پست‌ها و وضعیتشان'],
                ['command' => 'mute', 'description' => 'خاموش‌کردن اعلان پست تازه'],
                ['command' => 'unmute', 'description' => 'روشن‌کردن اعلان پست تازه'],
            ]]),
            'setChatMenuButton' => $this->call('setChatMenuButton', ['menu_button' => ['type' => 'web_app', 'text' => 'پست‌ها', 'web_app' => ['url' => $appUrl]]]),
            'setMyShortDescription' => $this->call('setMyShortDescription', ['short_description' => 'تنظیم کامنت و دایرکت هوشمند پست‌های اینستاگرام وطن']),
            'setMyDescription' => $this->call('setMyDescription', ['description' => "هر پست تازه‌ی اینستاگرام وطن اینجا اعلام می‌شود.\nبا یک لمس، کلمه‌ی کلیدی، پاسخ کامنت، شرط فالو و کارت دایرکتش را تنظیم و فعال کنید.\n\nمخصوص تیم وطن."]),
        ];
    }

    private function link(int $chatId, array $from, string $token): void
    {
        $adminId = Cache::pull(self::LINK_PREFIX.$token);
        $admin = $adminId ? Admin::query()->whereKey($adminId)->where('is_active', true)->first() : null;
        if (!$admin) {
            $this->send($chatId, '⛔️ لینک اتصال نامعتبر است یا منقضی شده (۱۵ دقیقه).'."\nاز پنل دوباره «اتصال تلگرام من» را بزنید.");

            return;
        }
        TelegramAdmin::query()->updateOrCreate(['telegram_id' => (int) $from['id']], [
            'workspace_id' => $this->context->id(),
            'admin_id' => $admin->id,
            'name' => $admin->name,
            'username' => $from['username'] ?? null,
            'first_name' => Str::limit((string) ($from['first_name'] ?? ''), 110, ''),
            'is_active' => true,
            'linked_at' => now(),
            'last_seen_at' => now(),
        ]);
        $this->send($chatId, '✅ <b>'.e($admin->name)."</b> عزیز، تلگرام شما به پنل وطن وصل شد.\n\nاز این به بعد هر پست تازه‌ی اینستاگرام اینجا اعلام می‌شود تا با یک لمس تنظیمش کنید.", $this->openKeyboard());
    }

    private function welcome(int $chatId, TelegramAdmin $account): void
    {
        $pending = PostCampaign::query()->where('workspace_id', $this->context->id())->count();
        $this->send($chatId, '👋 سلام '.e($account->displayName())."\n\nپنل «ثبت پست» اینستاگرام وطن آماده است.\nسناریوهای ثبت‌شده: <b>".$pending."</b>\n\n/posts — آخرین پست‌ها\n/mute — خاموش‌کردن اعلان پست تازه", $this->openKeyboard());
    }

    private function sendRecentPosts(int $chatId): void
    {
        $posts = Post::query()->where('workspace_id', $this->context->id())->with('campaign:id,post_id,status')
            ->orderByDesc('published_at')->orderByDesc('id')->limit(6)->get();
        if ($posts->isEmpty()) {
            $this->send($chatId, 'هنوز پستی همگام نشده است. داخل پنل «همگام‌سازی» را بزنید.', $this->openKeyboard());

            return;
        }
        $rows = $posts->map(function (Post $post): array {
            $status = $post->campaign ? (PostCampaignService::STATUSES[$post->campaign->status] ?? $post->campaign->status) : 'تنظیم‌نشده';
            $dot = match ($post->campaign?->status) { 'active' => '🟢', 'test' => '🟡', 'paused' => '⏸', 'draft' => '📝', default => '⚪️' };

            return [['text' => $dot.' '.Str::limit($post->shortCaption(40), 34).' · '.$status, 'web_app' => ['url' => $this->appUrl($post)]]];
        })->all();
        $this->send($chatId, '🗂 آخرین پست‌ها — برای تنظیم روی هر کدام بزنید:', ['inline_keyboard' => $rows]);
    }

    private function toggleNotify(TelegramAdmin $account, int $chatId, bool $on): void
    {
        $account->forceFill(['notify_new_posts' => $on])->save();
        $this->send($chatId, $on ? '🔔 اعلان پست تازه روشن شد.' : '🔕 اعلان پست تازه خاموش شد. برای روشن‌کردن: /unmute');
    }

    private function openKeyboard(): array
    {
        return ['inline_keyboard' => [[['text' => '📲 باز کردن پنل پست‌ها', 'web_app' => ['url' => $this->appUrl()]]]]];
    }

    private function send(int $chatId, string $text, ?array $keyboard = null): array
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => $keyboard,
        ], fn ($v) => $v !== null));
    }
}
