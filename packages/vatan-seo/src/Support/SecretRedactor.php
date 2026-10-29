<?php

namespace Vatan\Seo\Support;

/** Remove credential-shaped values before exceptions enter logs or notifications. */
class SecretRedactor
{
    public static function redact(string $message): string
    {
        foreach ([
            config('seo-engine.telegram.bot_token'), config('seo-engine.telegram.webhook_secret'),
            config('seo-engine.ai.api_key'), config('seo-engine.ai.dedicated_key'),
            config('seo-engine.google.gateway_secret'), config('seo-engine.dataforseo.password'),
        ] as $secret) {
            if (is_string($secret) && $secret !== '') $message = str_replace($secret, '[REDACTED]', $message);
        }
        $message = preg_replace('/\bbot\d+:[A-Za-z0-9_-]{20,}\b/', '[REDACTED_BOT_TOKEN]', $message);
        $message = preg_replace('/\bsk-or-[A-Za-z0-9_-]{12,}\b/', '[REDACTED_API_KEY]', $message);
        $message = preg_replace('/\bBearer\s+[^\s"\']+/i', 'Bearer [REDACTED]', $message);
        $message = preg_replace('/([?&](?:key|token|secret|access_token|api_key)=)[^&\s]+/i', '$1[REDACTED]', $message);
        $message = preg_replace('/("(?:private_key|client_secret|refresh_token|access_token)"\s*:\s*")[^"]+/i', '$1[REDACTED]', $message);
        $message = preg_replace('/-----BEGIN [A-Z ]*PRIVATE KEY-----.*?-----END [A-Z ]*PRIVATE KEY-----/s', '[REDACTED_PRIVATE_KEY]', $message);
        return mb_substr((string) $message, 0, 1000);
    }

    public static function sanitize(mixed $value): mixed
    {
        if (is_string($value)) return self::redact($value);
        if (! is_array($value)) return $value;
        foreach ($value as $key => $item) $value[$key] = self::sanitize($item);
        return $value;
    }
}
