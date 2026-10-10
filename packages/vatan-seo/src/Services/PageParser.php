<?php

namespace Vatan\Seo\Services;

/** استخراج سیگنال‌های سئوی یک صفحه‌ی HTML */
class PageParser
{
    public function parse(string $html, string $url): array
    {
        $out = [
            'title' => null, 'description' => null, 'h1' => [], 'h2_count' => 0, 'canonical' => null, 'robots' => null,
            'lang' => null, 'dir' => null, 'viewport' => false, 'og_title' => false, 'og_image' => false,
            'schema_types' => [], 'images' => 0, 'images_no_alt' => 0, 'words' => 0, 'links' => [], 'hreflang' => 0,
        ];
        if (trim($html) === '') {
            return $out;
        }
        $prev = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $x = new \DOMXPath($dom);

        $out['title'] = trim((string) $x->evaluate('string(//title)')) ?: null;
        $out['description'] = trim((string) $x->evaluate('string(//meta[translate(@name,"DESCRIPTION","description")="description"]/@content)')) ?: null;
        foreach ($x->query('//h1') as $h) {
            $out['h1'][] = trim(preg_replace('/\s+/u', ' ', $h->textContent));
        }
        $out['h2_count'] = $x->query('//h2')->length;
        $out['canonical'] = trim((string) $x->evaluate('string(//link[@rel="canonical"]/@href)')) ?: null;
        $out['robots'] = mb_strtolower(trim((string) $x->evaluate('string(//meta[translate(@name,"ROBOTS","robots")="robots"]/@content)'))) ?: null;
        $out['lang'] = trim((string) $x->evaluate('string(//html/@lang)')) ?: null;
        $out['dir'] = trim((string) $x->evaluate('string(//html/@dir)')) ?: null;
        $out['viewport'] = $x->query('//meta[@name="viewport"]')->length > 0;
        $out['og_title'] = $x->query('//meta[@property="og:title"]')->length > 0;
        $out['og_image'] = $x->query('//meta[@property="og:image"]')->length > 0;
        $out['hreflang'] = $x->query('//link[@rel="alternate"][@hreflang]')->length;

        foreach ($x->query('//script[@type="application/ld+json"]') as $s) {
            $json = json_decode(trim($s->textContent), true);
            if (is_array($json)) {
                $this->collectTypes($json, $out['schema_types']);
            }
        }
        $out['schema_types'] = array_values(array_unique($out['schema_types']));

        foreach ($x->query('//img') as $img) {
            $out['images']++;
            if (! $img->hasAttribute('alt') || trim($img->getAttribute('alt')) === '') {
                $out['images_no_alt']++;
            }
        }

        $host = parse_url($url, PHP_URL_HOST);
        $fetcher = new Fetcher();
        foreach ($x->query('//a[@href]') as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || str_starts_with($href, '#') || preg_match('/^(mailto|tel|javascript|data):/i', $href)) {
                continue;
            }
            $abs = strtok($fetcher->absolute($href, $url), '#');
            $linkHost = parse_url($abs, PHP_URL_HOST);
            if ($linkHost && preg_replace('/^www\./', '', $linkHost) === preg_replace('/^www\./', '', (string) $host)) {
                $out['links'][] = ['url' => $abs, 'anchor' => mb_substr(trim(preg_replace('/\s+/u', ' ', $a->textContent)), 0, 80), 'nofollow' => str_contains(mb_strtolower($a->getAttribute('rel')), 'nofollow')];
            }
        }

        // شمارش کلمات متن اصلی (بدون اسکریپت/استایل/ناوبری)
        foreach (['//script', '//style', '//noscript', '//nav', '//footer', '//header'] as $q) {
            foreach (iterator_to_array($x->query($q)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $text = preg_replace('/\s+/u', ' ', (string) $dom->textContent);
        $out['words'] = count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));

        return $out;
    }

    private function collectTypes(array $node, array &$types): void
    {
        if (isset($node['@type'])) {
            foreach ((array) $node['@type'] as $t) {
                $types[] = (string) $t;
            }
        }
        foreach ($node as $v) {
            if (is_array($v)) {
                $this->collectTypes($v, $types);
            }
        }
    }
}
