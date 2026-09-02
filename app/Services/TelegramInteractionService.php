<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramInteractionService
{
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
}
