<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramMembershipService
{
    /**
     * Return true/false when Telegram can answer, and null when verification failed.
     */
    public function check(int $telegramId, ?string $channel = null): ?bool
    {
        $token = trim((string) config('services.telegram.bot_token'));
        $chat = trim((string) ($channel ?: config('services.telegram.channel_id') ?: config('services.telegram.channel_username')));

        if ($token === '' || $telegramId < 1 || $chat === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->timeout(8)
                ->retry(1, 200)
                ->get("https://api.telegram.org/bot{$token}/getChatMember", [
                    'chat_id' => $chat,
                    'user_id' => $telegramId,
                ]);

            if (! $response->successful() || ! (bool) data_get($response->json(), 'ok')) {
                return null;
            }

            $result = (array) $response->json('result', []);
            $status = (string) ($result['status'] ?? '');

            return match ($status) {
                'creator', 'administrator', 'member' => true,
                'restricted' => (bool) ($result['is_member'] ?? false),
                'left', 'kicked' => false,
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}
