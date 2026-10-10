<?php

namespace Vatan\Seo\Data\Google;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * احراز هویت سرویس‌اکانت گوگل (JWT با امضای RS256) — بدون نیاز به کتابخانه‌ی گوگل.
 * کاربر فقط ایمیل سرویس‌اکانت را در سرچ کنسول (و GA4) به‌عنوان کاربر اضافه می‌کند.
 */
class ServiceAccount
{
    public const SCOPES = [
        'https://www.googleapis.com/auth/webmasters',
        'https://www.googleapis.com/auth/analytics.readonly',
        'https://www.googleapis.com/auth/indexing',
    ];

    public static function path(): ?string
    {
        $path = (string) config('seo-engine.google.service_account_path');
        if ($path === '') {
            return null;
        }
        if (is_file($path)) {
            return $path;
        }
        $local = Storage::disk('local')->path($path);
        return is_file($local) ? $local : null;
    }

    public static function credentials(): ?array
    {
        $path = self::path();
        if (! $path) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        return is_array($json) && ! empty($json['client_email']) && ! empty($json['private_key']) ? $json : null;
    }

    public static function configured(): bool
    {
        return self::credentials() !== null;
    }

    public static function email(): ?string
    {
        return self::credentials()['client_email'] ?? null;
    }

    public static function store(string $json): string
    {
        $data = json_decode($json, true);
        if (! is_array($data) || ($data['type'] ?? null) !== 'service_account' || empty($data['private_key'])) {
            throw new RuntimeException('فایل JSON معتبرِ سرویس‌اکانت گوگل نیست.');
        }
        Storage::disk('local')->put((string) config('seo-engine.google.service_account_path'), $json);
        Cache::forget('seo-engine:google-token');
        return (string) $data['client_email'];
    }

    public static function token(): string
    {
        return Cache::remember('seo-engine:google-token', now()->addMinutes(50), function () {
            $cred = self::credentials();
            if (! $cred) {
                throw new RuntimeException('سرویس‌اکانت گوگل تنظیم نشده است.');
            }
            $now = time();
            $header = self::b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = self::b64(json_encode([
                'iss' => $cred['client_email'],
                'scope' => implode(' ', self::SCOPES),
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $signature = '';
            if (! openssl_sign($header.'.'.$claims, $signature, $cred['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('امضای JWT ناموفق بود؛ کلید خصوصی معتبر نیست.');
            }
            $jwt = $header.'.'.$claims.'.'.self::b64($signature);

            $response = GoogleHttp::request(20)->asForm()->post(GoogleHttp::url('https://oauth2.googleapis.com/token'), [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            if (! $response->successful() || ! $response->json('access_token')) {
                throw new RuntimeException('دریافت توکن گوگل ناموفق بود: '.mb_substr($response->body(), 0, 200));
            }
            return (string) $response->json('access_token');
        });
    }

    private static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
