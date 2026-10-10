<?php

namespace Vatan\Seo\Services;

use Illuminate\Support\Facades\Http;

/** دریافت صفحه با ثبت زنجیره‌ی ریدایرکت، زمان پاسخ و هدرها */
class Fetcher
{
    public function get(string $url, int $maxRedirects = 5): array
    {
        $chain = [];
        $current = $url;
        $started = microtime(true);
        for ($i = 0; $i <= $maxRedirects; $i++) {
            try {
                $res = Http::withHeaders(['User-Agent' => config('seo-engine.crawler.user_agent'), 'Accept-Language' => 'fa,en;q=0.5'])
                    ->withoutRedirecting()
                    ->connectTimeout(8)
                    ->timeout((int) config('seo-engine.crawler.timeout', 15))
                    ->get($current);
            } catch (\Throwable $e) {
                return ['url' => $url, 'final_url' => $current, 'status' => 0, 'error' => $e->getMessage(), 'chain' => $chain, 'headers' => [], 'body' => '', 'ms' => (int) ((microtime(true) - $started) * 1000)];
            }
            $status = $res->status();
            if ($status >= 300 && $status < 400 && ($loc = $res->header('Location'))) {
                $chain[] = ['url' => $current, 'status' => $status];
                $current = $this->absolute($loc, $current);
                continue;
            }
            return [
                'url' => $url,
                'final_url' => $current,
                'status' => $status,
                'chain' => $chain,
                'headers' => array_change_key_case(array_map(fn ($v) => is_array($v) ? implode(', ', $v) : $v, $res->headers()), CASE_LOWER),
                'body' => str_contains((string) $res->header('Content-Type'), 'html') || str_contains((string) $res->header('Content-Type'), 'xml') || str_contains((string) $res->header('Content-Type'), 'text') ? $res->body() : '',
                'bytes' => strlen($res->body()),
                'ms' => (int) ((microtime(true) - $started) * 1000),
            ];
        }
        return ['url' => $url, 'final_url' => $current, 'status' => 310, 'error' => 'ریدایرکت بیش از حد', 'chain' => $chain, 'headers' => [], 'body' => '', 'ms' => 0];
    }

    public function absolute(string $href, string $base): string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }
        $p = parse_url($base);
        $root = ($p['scheme'] ?? 'https').'://'.($p['host'] ?? '').(isset($p['port']) ? ':'.$p['port'] : '');
        if (str_starts_with($href, '//')) {
            return ($p['scheme'] ?? 'https').':'.$href;
        }
        if (str_starts_with($href, '/')) {
            return $root.$href;
        }
        $dir = isset($p['path']) ? preg_replace('#/[^/]*$#', '/', $p['path']) : '/';
        return $root.$dir.$href;
    }
}
