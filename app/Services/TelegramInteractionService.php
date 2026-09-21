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

        $mediaType = (string) ($media['type'] ?? '');
        $mediaValue = trim((string) ($media['file_id'] ?? $media['url'] ?? ''));
        $mediaMethods = [
            'photo' => ['field' => 'photo', 'method' => 'sendPhoto'],
            'video' => ['field' => 'video', 'method' => 'sendVideo'],
            'animation' => ['field' => 'animation', 'method' => 'sendAnimation'],
            'document' => ['field' => 'document', 'method' => 'sendDocument'],
        ];

        if ($mediaValue !== '' && isset($mediaMethods[$mediaType])) {
            $mediaConfig = $mediaMethods[$mediaType];
            $payload = [
                'chat_id' => $chatId,
                $mediaConfig['field'] => $mediaValue,
                'caption' => $text,
            ];
            if ($replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }
            $this->call($mediaConfig['method'], $payload);
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

    /** @return array<string,mixed>|null */
    public function sendText(string $chatId, string $text, array $buttons = []): ?array
    {
        if ($chatId === '' || trim($text) === '') {
            return null;
        }

        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $this->replyMarkup($buttons),
        ], static fn ($value) => $value !== null));
    }

    /** @return array<string,mixed>|null */
    public function editText(string $chatId, int $messageId, string $text, array $buttons = []): ?array
    {
        if ($chatId === '' || $messageId < 1 || trim($text) === '') {
            return null;
        }

        return $this->call('editMessageText', array_filter([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'reply_markup' => $this->replyMarkup($buttons),
        ], static fn ($value) => $value !== null));
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

    /** @return array<string,mixed>|null */
    private function call(string $method, array $payload): ?array
    {
        $token = trim((string) config('services.telegram.bot_token'));
        if ($token === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(3)
                ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

            if (! $response->successful() || ! $response->json('ok')) {
                return null;
            }

            $result = $response->json('result');
            return is_array($result) ? $result : null;
        } catch (\Throwable) {
            // پاسخ اصلی بات نباید به‌خاطر خطای جانبی تلگرام متوقف شود.
            return null;
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
