<?php

namespace App\Services\TelegramProduct;

use App\Services\TelegramProductDraftService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * نمایش پاسخ‌های بات ثبت محصول در تلگرام، در اتصال مستقیم (کار n8n را انجام می‌دهد).
 *
 * اصول: یک «پنل» زنده در هر چت؛ کلیک دکمه‌ها همان پیام را ویرایش می‌کند و پیام تازه،
 * پنل قبلی را پاک می‌کند تا چت مرتب بماند. پیام نهایی ثبت محصول هیچ‌وقت پاک نمی‌شود.
 */
class TelegramProductBotRenderer
{
    private const TEXT_LIMIT = 4000;
    private const CAPTION_LIMIT = 1000;
    private const MENU_STATUSES = ['welcome', 'cancelled', 'no_draft', 'education', 'settings_saved', 'settings'];

    public function __construct(
        private readonly TelegramProductBotClient $telegram,
        private readonly TelegramProductDraftService $drafts,
    ) {
    }

    /**
     * @param array{chat_id:string, event?:string, callback_query_id?:?string, callback_message_id?:?string} $context
     */
    public function deliver(array $response, array $context): void
    {
        $chatId = (string) ($response['chat_id'] ?? $context['chat_id'] ?? '');
        $callbackId = $context['callback_query_id'] ?? null;
        if ($callbackId) {
            $this->telegram->call('answerCallbackQuery', array_filter([
                'callback_query_id' => $callbackId,
                'text' => Str::limit((string) ($response['callback_notice'] ?? ''), 180, '…') ?: null,
            ]), 5);
        }
        // پاسخ تکراری (update_id تکراری از سمت تلگرام): قبلاً نمایش داده شده است.
        if ($chatId === '' || ! empty($response['duplicate_update']) || trim((string) ($response['text'] ?? '')) === '') {
            return;
        }

        // آلبوم نمونه‌ها (حداکثر ۱۰ عکس) قبل از پنل
        if (! empty($response['media'])) {
            $this->sendAlbum($chatId, (array) $response['media']);
        }

        $markup = $this->markup($response);
        $text = Str::limit((string) $response['text'], self::TEXT_LIMIT, '…');
        $parseMode = ($response['parse_mode'] ?? null) === 'HTML' ? 'HTML' : null;
        $photo = $this->publicPhoto($response['photo_url'] ?? null);
        $callbackMessageId = $context['callback_message_id'] ?? null;
        $previousPanel = Cache::get($this->panelKey($chatId));

        $messageId = null;
        $edited = false;
        // به‌روزرسانی پیشرفت (از صف): همان پنل فعلی ویرایش می‌شود، نه پیام تازه.
        if (! $callbackMessageId && ! empty($response['edit_panel']) && $previousPanel && ! $photo && empty($response['media'])) {
            $result = $this->telegram->call('editMessageText', array_filter([
                'chat_id' => $chatId,
                'message_id' => (int) $previousPanel,
                'text' => $text,
                'parse_mode' => $parseMode,
                'reply_markup' => $markup,
                'disable_web_page_preview' => true,
            ], fn ($v) => $v !== null));
            if ($result !== null) {
                $messageId = (string) $previousPanel;
                $edited = true;
            }
        }
        // کلیک دکمه بدون عکس و بدون آلبوم: ویرایش همان پیام.
        if (! $edited && $callbackMessageId && ! $photo && empty($response['media']) && ($markup === null || isset($markup['inline_keyboard']))) {
            $result = $this->telegram->call('editMessageText', array_filter([
                'chat_id' => $chatId,
                'message_id' => (int) $callbackMessageId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'reply_markup' => $markup,
                'disable_web_page_preview' => true,
            ], fn ($v) => $v !== null));
            if ($result !== null) {
                $messageId = $callbackMessageId;
                $edited = true;
            }
        }

        if (! $edited) {
            $messageId = $this->send($chatId, $text, $parseMode, $markup, $photo);
            // پنل قبلی (یا پیامی که روی آن کلیک شد) دیگر لازم نیست.
            foreach (array_unique(array_filter([$previousPanel, $callbackMessageId])) as $old) {
                if ((string) $old !== (string) $messageId) {
                    $this->telegram->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => (int) $old], 5);
                }
            }
        }

        // پیام خود مدیر (عکس/متن) مصرف شد؛ چت فقط پنل بات را نگه می‌دارد.
        $incoming = $context['incoming_message_id'] ?? null;
        if ($incoming && ! empty($response['consume_input']) && (string) $incoming !== (string) $messageId) {
            $this->telegram->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => (int) $incoming], 5);
        }

        // پیام‌های قدیمی‌ای که خود فرآیند خواسته پاک شوند (همان قرارداد n8n).
        foreach ((array) ($response['delete_message_ids'] ?? []) as $old) {
            if ((string) $old !== (string) $messageId && (string) $old !== (string) $callbackMessageId) {
                $this->telegram->call('deleteMessage', ['chat_id' => $chatId, 'message_id' => (int) $old], 5);
            }
        }

        if ($messageId === null) {
            return;
        }
        if (! empty($response['final_product']) || in_array($response['status'] ?? null, ['welcome', 'cancelled'], true)) {
            // پیام پایانی و خوش‌آمد ماندگارند.
            Cache::forget($this->panelKey($chatId));
        } else {
            Cache::put($this->panelKey($chatId), (string) $messageId, now()->addDays(2));
        }
        if (! empty($response['draft_id']) && empty($response['final_product'])) {
            $this->drafts->rememberBotMessage((string) $response['draft_id'], (string) $messageId);
        }
    }

    private function send(string $chatId, string $text, ?string $parseMode, ?array $markup, ?string $photo): ?string
    {
        if ($photo) {
            $fits = mb_strlen($text) <= self::CAPTION_LIMIT;
            $result = $this->telegram->call('sendPhoto', array_filter([
                'chat_id' => $chatId,
                'photo' => $photo,
                'caption' => $fits ? $text : null,
                'parse_mode' => $fits ? $parseMode : null,
                'reply_markup' => $fits ? $markup : null,
            ], fn ($v) => $v !== null), 20);
            if ($result !== null && $fits) {
                return (string) ($result['message_id'] ?? '');
            }
            // متن بلند (یا عکس در دسترس نبود): متن جدا با دکمه‌ها.
        }

        $payload = array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode,
            'reply_markup' => $markup,
            'disable_web_page_preview' => true,
        ], fn ($v) => $v !== null);
        $result = $this->telegram->call('sendMessage', $payload);
        if ($result === null && $parseMode) {
            // HTML نامعتبر نباید پیام را گم کند.
            $payload['text'] = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            unset($payload['parse_mode']);
            $result = $this->telegram->call('sendMessage', $payload);
        }

        return $result !== null ? (string) ($result['message_id'] ?? '') : null;
    }

    private function sendAlbum(string $chatId, array $media): void
    {
        $items = [];
        foreach (array_slice($media, 0, 10) as $item) {
            $url = $this->publicPhoto($item['url'] ?? null);
            if ($url) {
                $items[] = ['type' => 'photo', 'media' => $url, 'caption' => Str::limit((string) ($item['caption'] ?? ''), 200, '…')];
            }
        }
        if (count($items) === 1) {
            $this->telegram->call('sendPhoto', ['chat_id' => $chatId, 'photo' => $items[0]['media'], 'caption' => $items[0]['caption']], 20);
        } elseif ($items !== []) {
            $this->telegram->call('sendMediaGroup', ['chat_id' => $chatId, 'media' => $items], 30);
        }
    }

    /**
     * کیبورد پیام: کیبورد شیشه‌ای صریح > دکمه‌های پاسخ (به شکل شیشه‌ای) > منوی اصلی.
     */
    private function markup(array $response): ?array
    {
        $explicit = $response['reply_markup'] ?? null;
        if (is_array($explicit) && isset($explicit['inline_keyboard'])) {
            return $explicit;
        }
        $isMenu = is_array($explicit) && isset($explicit['keyboard']);
        if ($isMenu && (in_array($response['status'] ?? null, self::MENU_STATUSES, true) || empty($response['buttons']))) {
            return $explicit;
        }

        $buttons = array_values(array_filter((array) ($response['buttons'] ?? []), fn ($b) => is_array($b) && (filled($b['callback_data'] ?? null) || filled($b['url'] ?? null))));
        if ($buttons === []) {
            return null;
        }
        $rows = [];
        $row = [];
        foreach ($buttons as $button) {
            $clean = array_filter([
                'text' => (string) ($button['text'] ?? ''),
                'callback_data' => $button['callback_data'] ?? null,
                'url' => $button['url'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
            $wide = Str::contains($clean['text'], ['لغو']) || isset($clean['url']) || mb_strlen($clean['text']) > 18;
            if ($wide) {
                if ($row !== []) {
                    $rows[] = $row;
                    $row = [];
                }
                $rows[] = [$clean];
                continue;
            }
            $row[] = $clean;
            if (count($row) === 2) {
                $rows[] = $row;
                $row = [];
            }
        }
        if ($row !== []) {
            $rows[] = $row;
        }

        return ['inline_keyboard' => $rows];
    }

    /** تلگرام فقط آدرس عمومی https را می‌پذیرد (نه localhost). */
    private function publicPhoto(mixed $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! Str::startsWith($url, 'https://')) {
            return null;
        }
        $host = (string) parse_url($url, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true) || Str::endsWith($host, ['.test', '.localhost']) ? null : $url;
    }

    private function panelKey(string $chatId): string
    {
        return 'telegram-product-bot:panel:' . $chatId;
    }
}
