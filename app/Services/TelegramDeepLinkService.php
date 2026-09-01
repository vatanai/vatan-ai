<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ReferralSetting;
use App\Models\TelegramDeepLink;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TelegramDeepLinkService
{
    public function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $value = 'v1_' . $encoded;

        if (strlen($value) > 64) {
            throw ValidationException::withMessages(['payload' => 'اطلاعات لینک تلگرام بیش از حد مجاز طولانی است.']);
        }

        return $value;
    }

    public function decode(?string $payload): array
    {
        $payload = trim((string) $payload);
        if ($payload === '') {
            return [];
        }

        if (str_starts_with($payload, 'v1_')) {
            $encoded = strtr(substr($payload, 3), '-_', '+/');
            $encoded .= str_repeat('=', (4 - strlen($encoded) % 4) % 4);
            $decoded = base64_decode($encoded, true);
            $data = is_string($decoded) ? json_decode($decoded, true) : null;
            return is_array($data) ? $data : [];
        }

        if (str_starts_with($payload, 'tl_')) {
            $link = TelegramDeepLink::query()->with('product')
                ->where('token', substr($payload, 3))
                ->where('is_active', true)
                ->first();
            if (! $link) return [];

            $link->increment('click_count');
            $link->forceFill(['last_clicked_at' => now()])->save();
            return [
                'product' => $link->product?->route_slug,
                'source' => $link->source,
                'channel' => $link->source_channel,
                'campaign' => $link->source_campaign,
                'message' => $link->message_id,
                'deep_link_id' => $link->id,
            ];
        }

        if (str_starts_with($payload, 'product_')) {
            return ['product' => substr($payload, 8), 'source' => 'channel'];
        }

        return ['source' => Str::limit($payload, 120, '')];
    }

    public function productLink(Product $product, string $source = 'channel', array $context = []): string
    {
        $username = ltrim((string) (
            config('services.telegram.bot_username')
            ?: ReferralSetting::current()->telegram_bot_username
            ?: data_get(config('services.telegram'), 'bot_username')
        ), '@');
        if ($username === '') {
            throw ValidationException::withMessages(['bot_username' => 'نام کاربری بات تلگرام تنظیم نشده است.']);
        }

        $link = TelegramDeepLink::query()->create([
            'token' => Str::lower(Str::random(16)),
            'product_id' => $product->id,
            'source' => Str::limit(trim($source) ?: 'channel', 120, ''),
            'source_channel' => isset($context['channel']) ? Str::limit((string) $context['channel'], 120, '') : null,
            'source_campaign' => isset($context['campaign']) ? Str::limit((string) $context['campaign'], 160, '') : null,
            'message_id' => isset($context['message']) ? Str::limit((string) $context['message'], 100, '') : null,
            'metadata' => $context,
            'is_active' => true,
        ]);

        return 'https://t.me/' . $username . '?start=tl_' . $link->token;
    }
}
