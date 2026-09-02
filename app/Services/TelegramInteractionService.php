<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramInteractionService
{
    public function sendResponse(array $response): void
    {
        if (($response['type'] ?? null) !== 'send_message') {
            return;
        }

        $chatId = $response['chat_id'] ?? null;
        $text = (string) ($response['text'] ?? '');
        if (! $chatId || $text === '') {
            return;
        }

        $replyMarkup = $this->replyMarkup((array) ($response['buttons'] ?? []));
        $media = (array) ($response['media'] ?? []);

        if (($media['type'] ?? null) === 'photo' && ! empty($media['url'])) {
            $payload = [
                'chat_id' => $chatId,
                'photo' => $media['url'],
                'caption' => $text,
            ];
            if ($replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }
            $this->call('sendPhoto', $payload);
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }
        $this->call('sendMessage', $payload);
    }

    public function answerCallbackQuery(?string $callbackQueryId): void
    {
        if (! $callbackQueryId) {
            return;
        }

        $this->call('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'cache_time' => 0,
        ]);
    }

    public function deleteMessage(?string $chatId, ?string $messageId): void
    {
        if (! $chatId || ! $messageId) {
            return;
        }

        $this->call('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    private function call(string $method, array $payload): void
    {
        $token = trim((string) config('services.telegram.bot_token'));
        if ($token === '') {
            return;
        }

        try {
            Http::acceptJson()
                ->timeout(3)
                ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
        } catch (\Throwable) {
            // پاسخ اصلی بات نباید به‌خاطر خطای جانبی تلگرام متوقف شود.
        }
    }

    private function replyMarkup(array $buttons): ?array
    {
        $buttons = array_values(array_filter($buttons, 'is_array'));
        if ($buttons === []) {
            return null;
        }

        $isReplyKeyboard = collect($buttons)->contains(fn (array $button): bool => (bool) ($button['request_contact'] ?? false));
        $rows = array_map(static fn (array $button): array => [$button], $buttons);

        return $isReplyKeyboard
            ? [
                'keyboard' => $rows,
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
            : ['inline_keyboard' => $rows];
    }
}
