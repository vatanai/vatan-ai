<?php

namespace App\Services\ProductImports;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Discovery and extraction for product prompt pages. Site-specific parsers can be added here. */
class ProductImportSourceService
{
    private const MAX_PAGE_BYTES = 5_000_000;
    private const MAX_IMAGE_BYTES = 15_000_000;

    public function discover(string $url): array
    {
        $url = $this->normalizeUrl($url);
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($host === 'shootkits.com' || str_ends_with($host, '.shootkits.com')) {
            return ['adapter' => 'shootkits', 'items' => $this->discoverShootKits($url)];
        }

        return ['adapter' => 'generic', 'items' => [$this->extractGenericPage($url)]];
    }

    public function fetchImage(string $url): string
    {
        $url = $this->normalizeUrl($url);
        try {
            $response = $this->request()->timeout(12)->connectTimeout(3)->get($url);
        } catch (\Throwable) {
            $response = null;
        }

        // Some hosting networks cannot reach ShootKits' image CDN directly.
        // A public image relay is a fallback for this fixed, public source only.
        if ((! $response || ! $response->successful()) && $this->isShootKitsImageUrl($url)) {
            $parts = parse_url($url);
            $publicImageUrl = 'shootkits.com' . (string) ($parts['path'] ?? '/');
            if (! empty($parts['query'])) $publicImageUrl .= '?' . $parts['query'];
            try {
                $response = $this->request()->timeout(20)->connectTimeout(6)
                    ->get('https://images.weserv.nl/', ['url' => $publicImageUrl]);
            } catch (\Throwable) {
                $response = null;
            }
        }

        if (! $response || ! $response->successful()) {
            throw new RuntimeException('دریافت تصویر نمونه ناموفق بود.');
        }
        $type = strtolower((string) $response->header('Content-Type'));
        $bytes = $response->body();
        $imageInfo = @getimagesizefromstring($bytes);
        if (! str_starts_with($type, 'image/') || strlen($bytes) > self::MAX_IMAGE_BYTES || ! $imageInfo || ($imageInfo[0] * $imageInfo[1]) > 40_000_000) {
            throw new RuntimeException('نشانی نمونه، تصویر معتبر و مجاز نیست.');
        }

        return $bytes;
    }

    private function isShootKitsImageUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        return $host === 'shootkits.com' || str_ends_with($host, '.shootkits.com');
    }

    private function discoverShootKits(string $inputUrl): array
    {
        $path = trim((string) parse_url($inputUrl, PHP_URL_PATH), '/');
        if (str_starts_with($path, 'kits/')) {
            return [$this->extractShootKitsPage($inputUrl)];
        }

        $feedUrl = 'https://shootkits.com/rss.xml';
        $rss = $this->request()->get($feedUrl);
        if (! $rss->successful() || strlen($rss->body()) > self::MAX_PAGE_BYTES) {
            throw new RuntimeException('فهرست محصولات منبع دریافت نشد.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rss->body(), \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        if (! $xml || ! isset($xml->channel->item)) {
            throw new RuntimeException('فهرست محصولات منبع قابل خواندن نیست.');
        }

        $urls = [];
        foreach ($xml->channel->item as $item) {
            $itemUrl = trim((string) $item->link);
            if ($itemUrl === '') continue;
            $parts = parse_url($itemUrl);
            if (strtolower((string) ($parts['host'] ?? '')) !== 'shootkits.com'
                || ! str_starts_with((string) ($parts['path'] ?? ''), '/kits/')) {
                continue;
            }
            $urls[$itemUrl] = $itemUrl;
        }
        if ($urls === []) {
            throw new RuntimeException('در فهرست منبع هیچ صفحه‌ی کیت یا تک‌شات پیدا نشد.');
        }

        $items = [];
        foreach (array_chunk(array_values($urls), 10) as $batch) {
            $responses = Http::pool(fn ($pool) => array_map(
                fn (string $pageUrl) => $pool->as($pageUrl)->timeout(24)->withUserAgent($this->userAgent())->withoutRedirecting()->get($pageUrl),
                $batch
            ));
            foreach ($batch as $pageUrl) {
                $response = $responses[$pageUrl] ?? null;
                if ($response instanceof \Throwable) {
                    throw new RuntimeException('ارتباط امن با یکی از صفحه‌های منبع برقرار نشد.');
                }
                if (! $response || ! $response->successful() || strlen($response->body()) > self::MAX_PAGE_BYTES) {
                    throw new RuntimeException('یکی از صفحه‌های منبع دریافت نشد؛ چیزی از آن صفحه به وطن وارد نشده است.');
                }
                $items[] = $this->parseShootKitsHtml($pageUrl, $response->body());
            }
        }

        return $items;
    }

    private function extractShootKitsPage(string $url): array
    {
        $response = $this->request()->get($url);
        if (! $response->successful() || strlen($response->body()) > self::MAX_PAGE_BYTES) {
            throw new RuntimeException('صفحه‌ی کیت منبع دریافت نشد.');
        }

        return $this->parseShootKitsHtml($url, $response->body());
    }

    private function parseShootKitsHtml(string $url, string $html): array
    {
        $dom = $this->loadHtml($html);
        $xpath = new \DOMXPath($dom);
        $metas = [];
        foreach ($xpath->query('//meta[@property or @name]') ?: [] as $meta) {
            $key = strtolower((string) ($meta->getAttribute('property') ?: $meta->getAttribute('name')));
            if ($key !== '') $metas[$key] = trim($meta->getAttribute('content'));
        }

        $h1 = trim((string) ($xpath->query('//main//h1')->item(0)?->textContent ?? ''));
        $mainPrompt = trim((string) ($xpath->query('//*[@id="main-prompt"]')->item(0)?->getAttribute('data-template') ?? ''));
        $shots = [];
        foreach ($xpath->query('//div[starts-with(@id,"shot-") and @data-template]') ?: [] as $node) {
            $li = $xpath->query('ancestor::li[1]', $node)->item(0);
            $name = trim((string) ($xpath->query('.//summary//strong', $li)->item(0)?->textContent ?? ''));
            $detail = trim((string) ($xpath->query('.//summary//span[contains(@class,"muted")]', $li)->item(0)?->textContent ?? ''));
            $shots[] = [
                'name' => $name,
                'description' => $detail,
                'prompt' => trim((string) $node->getAttribute('data-template')),
            ];
        }

        $image = $metas['og:image'] ?? $metas['twitter:image'] ?? '';
        $image = $image !== '' ? $this->absoluteUrl($url, $image) : '';
        $sector = '';
        if (preg_match('#/kits/([^/]+)/#', (string) parse_url($url, PHP_URL_PATH), $m)) $sector = $m[1];
        $payload = [
            'kind' => $shots !== [] ? 'campaign' : 'single',
            'title' => $h1 ?: ($metas['og:title'] ?? ''),
            'description' => $metas['og:description'] ?? '',
            'sector' => $sector,
            'image_url' => $image,
            'main_prompt' => $mainPrompt,
            'shots' => $shots !== [] ? $shots : [['name' => $h1, 'description' => '', 'prompt' => $mainPrompt]],
            'last_tested' => $this->findLastTested($dom, $xpath),
        ];

        if ($payload['title'] === '' || $payload['image_url'] === '' || $payload['shots'][0]['prompt'] === '') {
            throw new RuntimeException('اطلاعات عنوان، تصویر یا پرامپت این صفحه کامل نیست.');
        }

        return ['url' => $url, 'title' => $payload['title'], 'payload' => $payload];
    }

    /** Generic fallback: one product page using public metadata/schema and its main product image. */
    private function extractGenericPage(string $url): array
    {
        $response = $this->request()->get($url);
        if (! $response->successful() || strlen($response->body()) > self::MAX_PAGE_BYTES) {
            throw new RuntimeException('صفحه‌ی منبع دریافت نشد.');
        }
        $dom = $this->loadHtml($response->body());
        $xpath = new \DOMXPath($dom);
        $meta = [];
        foreach ($xpath->query('//meta[@property or @name]') ?: [] as $node) {
            $key = strtolower((string) ($node->getAttribute('property') ?: $node->getAttribute('name')));
            if ($key !== '') $meta[$key] = trim($node->getAttribute('content'));
        }
        $schema = [];
        foreach ($xpath->query('//script[@type="application/ld+json"]') ?: [] as $script) {
            $parsed = json_decode((string) $script->textContent, true);
            foreach (($parsed['@graph'] ?? [$parsed]) as $record) {
                if (is_array($record) && in_array('Product', (array) ($record['@type'] ?? []), true)) {
                    $schema = $record;
                    break 2;
                }
            }
        }
        $title = (string) ($schema['name'] ?? $meta['og:title'] ?? $xpath->query('//h1')->item(0)?->textContent ?? '');
        $description = (string) ($schema['description'] ?? $meta['og:description'] ?? '');
        $image = $schema['image'] ?? $meta['og:image'] ?? $meta['twitter:image'] ?? '';
        if (is_array($image)) $image = $image[0] ?? '';
        $image = $image !== '' ? $this->absoluteUrl($url, (string) $image) : '';
        if (trim($title) === '' || trim($description) === '' || $image === '') {
            throw new RuntimeException('این صفحه اطلاعات محصول استاندارد و تصویر اصلی قابل تشخیص ندارد.');
        }

        $payload = [
            'kind' => 'generic', 'title' => trim($title), 'description' => trim(strip_tags($description)),
            'sector' => (string) ($schema['category'] ?? ''), 'image_url' => $image,
            'main_prompt' => '', 'shots' => [], 'last_tested' => null,
        ];

        return ['url' => $url, 'title' => $payload['title'], 'payload' => $payload];
    }

    private function findLastTested(\DOMDocument $dom, \DOMXPath $xpath): ?string
    {
        $text = $dom->textContent;
        return preg_match('/Last tested\s+([^\n\r<]{1,40})/i', $text, $m) ? trim($m[1]) : null;
    }

    private function request(): PendingRequest
    {
        return Http::timeout(28)
            ->connectTimeout(8)
            ->withUserAgent($this->userAgent())
            ->accept('text/html,application/xhtml+xml,application/rss+xml,application/xml,image/avif,image/webp,image/png,image/jpeg')
            ->withoutRedirecting();
    }

    private function userAgent(): string
    {
        return 'VatanProductCatalog/1.0';
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('فقط نشانی عمومی امن با https پذیرفته می‌شود.');
        }
        if (filter_var($host, FILTER_VALIDATE_IP) && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new RuntimeException('نشانی شبکه‌ی داخلی قابل دریافت نیست.');
        }
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            foreach ($records ?: [] as $record) {
                $ip = $record['ip'] ?? $record['ipv6'] ?? null;
                if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    throw new RuntimeException('نشانی شبکه‌ی داخلی قابل دریافت نیست.');
                }
            }
        }

        return $url;
    }

    private function absoluteUrl(string $base, string $value): string
    {
        $value = trim($value);
        if (str_starts_with($value, '//')) return 'https:' . $value;
        if (preg_match('#^https://#i', $value)) return $value;
        $parts = parse_url($base);
        if (str_starts_with($value, '/')) return 'https://' . $parts['host'] . $value;
        $path = (string) ($parts['path'] ?? '/');
        return 'https://' . $parts['host'] . rtrim(dirname($path), '/') . '/' . $value;
    }

    private function loadHtml(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        return $dom;
    }
}
