<?php

namespace App\Services;

use App\Models\AiProviderSetting;

class AiProviderCredentials
{
    public function for(string $provider): array
    {
        $provider = strtolower(trim($provider));
        try {
            $setting = AiProviderSetting::forProvider($provider);
        } catch (\Throwable) {
            // در فاصله‌ی deploy کد تا اجرای migration، باید همچنان env قابل استفاده باشد.
            $setting = null;
        }
        $config = (array) config("services.{$provider}", []);

        return [
            'api_key' => $setting?->api_key ?: ($config['api_key'] ?? $config['api_token'] ?? ''),
            'base_url' => rtrim((string) ($setting?->base_url ?: ($config['base_url'] ?? '')), '/'),
            'webhook_secret' => $setting?->webhook_secret ?: (string) config('services.ai.webhook_secret', ''),
            'timeout' => (int) ($setting?->timeout ?: ($config['timeout'] ?? config('services.ai.timeout', 180))),
            'max_retries' => (int) ($setting?->max_retries ?: ($config['max_retries'] ?? config('services.ai.max_retries', 2))),
            'webhook_enabled' => $setting?->webhook_enabled ?? true,
        ];
    }

    public function webhookUrl(string $provider): ?string
    {
        $base = rtrim((string) config('services.ai.webhook_base_url', ''), '/');
        if ($base === '') {
            return null;
        }

        return $base . '/webhooks/ai/' . strtolower(trim($provider));
    }
}
