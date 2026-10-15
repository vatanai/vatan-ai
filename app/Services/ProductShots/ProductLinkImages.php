<?php

namespace App\Services\ProductShots;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * «عکس از لینک محصول» در صفحه‌ی ساخت (/app/crepro):
 *  ۱) images(): صفحه‌ی محصول را می‌خواند و عکس‌های اصلی‌اش را پیدا می‌کند
 *     (og:image، JSON-LD محصول، twitter:image، <img> و srcset)؛
 *  ۲) download(): یکی از آن عکس‌ها را با محدودیت حجم و نوع، دانلود می‌کند.
 *
 * امنیت (SSRF): فقط http/https، فقط آدرس‌های عمومی اینترنت (نه شبکه‌ی داخلی/لوکال)،
 * IP بررسی‌شده هنگام اتصال پین می‌شود، ریدایرکت‌ها دستی و حداکثر ۳ بار و هر بار دوباره بررسی می‌شوند.
 */
class ProductLinkImages
{
    private const MAX_HTML_BYTES = 3 * 1024 * 1024;
    private const MAX_IMAGES = 24;
    private const MAX_REDIRECTS = 3;
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    /**
     * @return array{title:?string, images:array<int,string>}
     */
    public function images(string $url): array
    {
        [$body, $finalUrl, $type] = $this->fetch($url, self::MAX_HTML_BYTES);

        // لینک مستقیم یک عکس
        if (str_starts_with($type, 'image/')) {
            return ['title' => null, 'images' => [$finalUrl]];
        }
        if ($body === '' || ! str_contains($type, 'html')) {
            throw new RuntimeException('این لینک صفحه‌ی وب نیست؛ لینک صفحه‌ی محصول را بگذار.');
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $body, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);

        $base = $finalUrl;
        $baseNode = $xpath->query('//base[@href]')->item(0);
        if ($baseNode instanceof \DOMElement) {
            $base = $this->absolute($baseNode->getAttribute('href'), $finalUrl) ?? $finalUrl;
        }

        $found = [];
        $add = function (?string $candidate, int $priority) use (&$found, $base) {
            $abs = $candidate ? $this->absolute(trim($candidate), $base) : null;
            if (! $abs || ! $this->looksLikeProductImage($abs)) {
                return;
            }
            // نسخه‌ی کوچک (بندانگشتی) CDNها را به نسخه‌ی بزرگ تبدیل کن: ?width=100 → ?width=1200
            $abs = preg_replace_callback('/([?&](?:width|w)=)(\d+)/i', fn ($m) => (int) $m[2] < 800 ? $m[1] . '1200' : $m[0], $abs) ?? $abs;
            $key = $this->dedupeKey($abs);
            if (! isset($found[$key]) || $found[$key]['p'] > $priority) {
                $found[$key] = ['url' => $abs, 'p' => $priority, 'o' => count($found)];
            }
        };

        // ۱) متادیتای اشتراک‌گذاری
        foreach (['og:image', 'og:image:secure_url', 'og:image:url', 'twitter:image', 'twitter:image:src'] as $prop) {
            foreach ($xpath->query("//meta[@property='{$prop}' or @name='{$prop}']") as $meta) {
                $add($meta->getAttribute('content'), 1);
            }
        }
        foreach ($xpath->query("//link[@rel='image_src']") as $link) {
            $add($link->getAttribute('href'), 1);
        }

        // ۲) داده‌ی ساخت‌یافته (JSON-LD) محصول
        foreach ($xpath->query("//script[@type='application/ld+json']") as $script) {
            $json = json_decode(trim($script->textContent), true);
            foreach ($this->jsonLdImages($json) as $image) {
                $add($image, 0);
            }
        }

        // ۳) عکس‌های داخل صفحه (src، lazy-load و بزرگ‌ترین srcset)
        foreach ($xpath->query('//img') as $img) {
            /** @var \DOMElement $img */
            $width = (int) $img->getAttribute('width');
            if ($width > 0 && $width < 120) {
                continue;
            }
            $hint = Str::lower($img->getAttribute('class') . ' ' . $img->getAttribute('alt') . ' ' . $img->getAttribute('id'));
            $priority = Str::contains($hint, ['product', 'gallery', 'zoom', 'main', 'محصول']) ? 2 : 3;
            foreach (['data-zoom-image', 'data-large_image', 'data-src', 'data-original', 'data-lazy-src', 'src'] as $attr) {
                if ($img->getAttribute($attr) !== '') {
                    $add($img->getAttribute($attr), $priority);
                    break;
                }
            }
            foreach (['data-srcset', 'srcset'] as $attr) {
                if (($set = $img->getAttribute($attr)) !== '') {
                    $add($this->largestFromSrcset($set), $priority);
                }
            }
        }

        uasort($found, fn ($a, $b) => [$a['p'], $a['o']] <=> [$b['p'], $b['o']]);
        $title = null;
        $titleNode = $xpath->query("//meta[@property='og:title']")->item(0);
        if ($titleNode instanceof \DOMElement) {
            $title = trim($titleNode->getAttribute('content')) ?: null;
        }
        $title ??= trim((string) $xpath->query('//title')->item(0)?->textContent) ?: null;

        return [
            'title' => $title ? Str::limit($title, 120) : null,
            'images' => array_values(array_slice(array_column($found, 'url'), 0, self::MAX_IMAGES)),
        ];
    }

    /**
     * دانلود یک عکس؛ خروجی: مسیر فایل موقت + نوع.
     *
     * @return array{path:string, mime:string}
     */
    public function download(string $url, int $maxBytes): array
    {
        [$body, , $type] = $this->fetch($url, $maxBytes, 'image/avif,image/webp,image/png,image/jpeg,image/*;q=0.8');
        $info = @getimagesizefromstring($body);
        if ($body === '' || ! $info || ! in_array($info['mime'] ?? '', ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
            throw new RuntimeException('این عکس قابل استفاده نیست (فقط JPG، PNG یا WEBP).');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'pslink');
        file_put_contents($tmp, $body);

        return ['path' => $tmp, 'mime' => (string) $info['mime']];
    }

    /** @return array{0:string,1:string,2:string} [body, finalUrl, content-type] */
    private function fetch(string $url, int $maxBytes, string $accept = 'text/html,application/xhtml+xml,image/*;q=0.8,*/*;q=0.5'): array
    {
        $current = $this->normalizeUrl($url);
        for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
            [$host, $port, $ip] = $this->assertPublic($current);
            try {
                $response = Http::withHeaders([
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => $accept,
                    'Accept-Language' => 'fa-IR,fa;q=0.9,en;q=0.8',
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                        'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$ip}"]],
                        'on_headers' => function ($r) use ($maxBytes) {
                            if ((int) $r->getHeaderLine('Content-Length') > $maxBytes) {
                                throw new RuntimeException('too_large');
                            }
                        },
                    ])
                    ->connectTimeout(8)
                    ->timeout(15)
                    ->get($current);
            } catch (\Throwable $e) {
                throw new RuntimeException(str_contains($e->getMessage(), 'too_large')
                    ? 'حجم فایل این لینک خیلی زیاد است.'
                    : 'این لینک باز نشد؛ مطمئن شو آدرس درست است و صفحه بدون ورود به حساب باز می‌شود.');
            }

            if ($response->status() >= 300 && $response->status() < 400 && $response->header('Location')) {
                $next = $this->absolute($response->header('Location'), $current);
                if (! $next) {
                    break;
                }
                $current = $next;
                continue;
            }
            if (! $response->successful()) {
                throw new RuntimeException($response->status() === 403 || $response->status() === 401
                    ? 'این سایت اجازه‌ی خواندن صفحه را نمی‌دهد؛ عکس‌ها را دستی بارگذاری کن.'
                    : 'این لینک باز نشد (خطای ' . $response->status() . ')؛ آدرس را بررسی کن.');
            }
            $body = (string) $response->body();
            if (strlen($body) > $maxBytes) {
                throw new RuntimeException('حجم فایل این لینک خیلی زیاد است.');
            }

            return [$body, $current, Str::lower((string) $response->header('Content-Type'))];
        }

        throw new RuntimeException('این لینک بیش از حد به آدرس‌های دیگر منتقل می‌شود.');
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            $url = 'https://' . $url;
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('لینک معتبر نیست؛ آدرس کامل صفحه‌ی محصول را بگذار.');
        }

        return $url;
    }

    /** @return array{0:string,1:int,2:string} [host, port, ip] */
    private function assertPublic(string $url): array
    {
        $parts = parse_url($url);
        $scheme = Str::lower((string) ($parts['scheme'] ?? ''));
        $host = Str::lower(trim((string) ($parts['host'] ?? ''), '[]'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('فقط لینک‌های http و https قابل قبول است.');
        }
        $port = (int) ($parts['port'] ?? ($scheme === 'https' ? 443 : 80));
        if (! in_array($port, [80, 443, 8080, 8443], true)) {
            throw new RuntimeException('این لینک قابل قبول نیست.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(
            (array) @gethostbynamel($host),
            array_column((array) @dns_get_record($host, DNS_AAAA), 'ipv6'),
        );
        $ips = array_values(array_filter($ips));
        if ($ips === []) {
            throw new RuntimeException('این سایت پیدا نشد؛ آدرس را بررسی کن.');
        }
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('این لینک قابل قبول نیست.');
            }
        }

        return [$host, $port, $ips[0]];
    }

    private function absolute(string $candidate, string $base): ?string
    {
        $candidate = html_entity_decode(trim($candidate), ENT_QUOTES | ENT_HTML5);
        if ($candidate === '' || str_starts_with($candidate, 'data:') || str_starts_with($candidate, 'javascript:')) {
            return null;
        }
        if (str_starts_with($candidate, '//')) {
            return (parse_url($base, PHP_URL_SCHEME) ?: 'https') . ':' . $candidate;
        }
        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }
        $b = parse_url($base);
        if (empty($b['scheme']) || empty($b['host'])) {
            return null;
        }
        $origin = $b['scheme'] . '://' . $b['host'] . (isset($b['port']) ? ':' . $b['port'] : '');
        if (str_starts_with($candidate, '/')) {
            return $origin . $candidate;
        }
        $dir = preg_replace('#/[^/]*$#', '/', $b['path'] ?? '/') ?: '/';

        return $origin . $dir . $candidate;
    }

    private function looksLikeProductImage(string $url): bool
    {
        $path = Str::lower((string) parse_url($url, PHP_URL_PATH));
        if (preg_match('#\.(svg|gif|ico)$#', $path)) {
            return false;
        }

        return ! Str::contains($path, ['logo', 'icon', 'sprite', 'favicon', 'avatar', 'placeholder', 'banner', 'badge', 'payment', 'enamad', 'samandehi', 'loading', 'blank.']);
    }

    /** نسخه‌های مختلف اندازه‌ی یک عکس یکی حساب شوند. */
    private function dedupeKey(string $url): string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        $path = preg_replace('#-\d{2,4}x\d{2,4}(?=\.\w+$)#', '', $path);

        return Str::lower((string) parse_url($url, PHP_URL_HOST) . $path);
    }

    private function largestFromSrcset(string $set): ?string
    {
        $best = null;
        $bestW = -1;
        foreach (explode(',', $set) as $part) {
            $bits = preg_split('/\s+/', trim($part));
            if (! $bits || $bits[0] === '') {
                continue;
            }
            $w = isset($bits[1]) ? (int) $bits[1] : 0;
            if ($w >= $bestW) {
                $best = $bits[0];
                $bestW = $w;
            }
        }

        return $best;
    }

    /** @return array<int,string> */
    private function jsonLdImages(mixed $json): array
    {
        if (! is_array($json)) {
            return [];
        }
        $out = [];
        $nodes = array_is_list($json) ? $json : [$json];
        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }
            if (isset($node['@graph'])) {
                $out = array_merge($out, $this->jsonLdImages($node['@graph']));
            }
            $type = (array) ($node['@type'] ?? []);
            if (in_array('Product', $type, true) || in_array('ProductGroup', $type, true)) {
                foreach ((array) ($node['image'] ?? []) as $image) {
                    $out[] = is_array($image) ? (string) ($image['url'] ?? $image['contentUrl'] ?? '') : (string) $image;
                }
                foreach ((array) ($node['hasVariant'] ?? []) as $variant) {
                    $out = array_merge($out, $this->jsonLdImages(is_array($variant) ? $variant + ['@type' => 'Product'] : null));
                }
            }
        }

        return array_values(array_filter($out));
    }
}
