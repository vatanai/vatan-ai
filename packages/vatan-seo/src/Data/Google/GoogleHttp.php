<?php

namespace Vatan\Seo\Data\Google;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * لایه‌ی HTTP گوگل. اگر سرور به گوگل دسترسی مستقیم نداشته باشد، با تنظیم
 * SEO_GOOGLE_GATEWAY_URL همه‌ی درخواست‌ها از Worker کلادفلر (cloudflare/seo-gateway)
 * عبور می‌کنند: https://www.googleapis.com/x → {gateway}/www.googleapis.com/x
 */
class GoogleHttp
{
    public static function url(string $url): string
    {
        $gateway = rtrim((string) config('seo-engine.google.gateway_url'), '/');
        if ($gateway === '') {
            return $url;
        }
        return $gateway.'/'.preg_replace('#^https?://#', '', $url);
    }

    public static function request(int $timeout = 30): PendingRequest
    {
        $req = Http::connectTimeout(10)->timeout($timeout)->acceptJson();
        $secret = (string) config('seo-engine.google.gateway_secret');
        if ($secret !== '' && config('seo-engine.google.gateway_url')) {
            $req = $req->withHeaders(['X-Vatan-Gateway-Key' => $secret]);
        }
        return $req;
    }
}
