<?php

namespace App\Services\TelegramProduct;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** فراخوانی کم‌حجم Telegram Bot API برای بات ثبت محصول (با توکن همان بات). */
class TelegramProductBotClient
{
    public function token(): string
    {
        return trim((string) config('services.telegram_product.bot_token'));
    }

    public function configured(): bool
    {
        return $this->token() !== '';
    }

    /** @return array<string,mixed>|null نتیجه‌ی موفق (result) یا null */
    public function call(string $method, array $payload, int $timeout = 15): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            /** @var Response $response */
            $response = Http::timeout($timeout)->asJson()->post("https://api.telegram.org/bot{$this->token()}/{$method}", $payload);
            $json = (array) $response->json();
            if (! ($json['ok'] ?? false)) {
                $description = (string) ($json['description'] ?? $response->status());
                // «message is not modified» خطا نیست؛ همان پیام از قبل درست است.
                if (! str_contains($description, 'message is not modified')) {
                    Log::info('telegram product bot api refused', ['method' => $method, 'error' => $description]);
                }

                return null;
            }

            return is_array($json['result'] ?? null) ? $json['result'] : ['value' => $json['result'] ?? true];
        } catch (\Throwable $exception) {
            Log::warning('telegram product bot api failed', ['method' => $method, 'error' => $exception->getMessage()]);

            return null;
        }
    }
}
