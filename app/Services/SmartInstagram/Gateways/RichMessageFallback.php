<?php

namespace App\Services\SmartInstagram\Gateways;

/**
 * نسخه‌ی جایگزین پیام دکمه‌دار وقتی اینستاگرام «قالب دکمه‌ای» را برای یک گیرنده نمی‌پذیرد:
 * همان متن با پاسخ سریع (دکمه‌های postback) و لینک‌ها زیر متن. اگر قالب دیگری بود null.
 */
final class RichMessageFallback
{
    public static function quickReplies(array $message): ?array
    {
        $payload = (array) data_get($message, 'attachment.payload', []);
        if (($payload['template_type'] ?? null) !== 'button') {
            return null;
        }
        $buttons = (array) ($payload['buttons'] ?? []);
        $links = collect($buttons)->where('type', 'web_url')->pluck('url')->filter()->implode("\n");
        $replies = collect($buttons)->where('type', 'postback')
            ->map(fn ($b) => ['content_type' => 'text', 'title' => mb_substr((string) ($b['title'] ?? ''), 0, 20), 'payload' => (string) ($b['payload'] ?? '')])
            ->filter(fn ($b) => $b['title'] !== '')->values()->all();
        $text = trim((string) ($payload['text'] ?? '').($links !== '' ? "\n".$links : ''));
        if ($text === '') {
            return null;
        }

        return $replies !== [] ? ['text' => $text, 'quick_replies' => $replies] : ['text' => $text];
    }
}
