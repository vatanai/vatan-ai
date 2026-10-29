<?php

namespace Vatan\Seo\Agents;

use Vatan\Seo\Connectors\BlocksToHtml;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Fa;

/** Hard checks required before unattended publication. */
class ContentQualityGate
{
    public function evaluate(Site $site, ContentItem $item): array
    {
        $blocks = collect((array) $item->blocks);
        $brief = (array) $item->brief;
        $keyword = trim((string) $item->keyword?->keyword);
        $normalizedKeyword = Fa::normalizeKeyword($keyword);
        $lead = (string) ($blocks->firstWhere('type', 'lead')['content'] ?? '');
        $headings = $blocks->where('type', 'heading')->where('level', 2)->pluck('content');
        $wordCount = (int) ($item->word_count ?: $this->words($blocks->all()));
        $target = max(900, min(2200, (int) data_get($brief, 'word_target', 1200)));
        $minimumWords = (int) ceil($target * 0.8);
        $maximumWords = (int) ceil($target * 1.3);
        $knownUrls = array_values(array_filter(array_merge(
            [(string) ($item->target_url ?: '')],
            (array) data_get($brief, 'internal_link_catalog', []),
            (array) data_get($brief, 'product_links', []),
            array_map(fn ($link) => (string) ($link['url'] ?? ''), (array) data_get($brief, 'internal_links', [])),
        )));
        $known = array_fill_keys(array_filter(array_map(fn ($url) => $this->normalizeUrl($site, $url), $knownUrls)), true);
        $links = $this->links($blocks->all());
        $validInternal = array_values(array_filter($links, function ($link) use ($site, $known) {
            $normalized = $this->normalizeUrl($site, $link['url']);
            return $normalized !== null && isset($known[$normalized]) && $this->descriptiveAnchor($link['anchor']);
        }));
        $distinctInternal = collect($validInternal)->unique(fn ($link) => $this->normalizeUrl($site, $link['url']))->values();
        $allLinksTrusted = count(array_filter($links, fn ($link) => ! isset($known[$this->normalizeUrl($site, $link['url']) ?? '']))) === 0;
        $productUrls = array_fill_keys(array_filter(array_map(fn ($url) => $this->normalizeUrl($site, (string) $url), (array) data_get($brief, 'product_links', []))), true);
        $validProductLinks = array_values(array_filter($validInternal, fn ($link) => isset($productUrls[$this->normalizeUrl($site, $link['url'])])));
        $targetUrl = $this->normalizeUrl($site, (string) ($item->target_url ?: ''));
        $targetLinked = $targetUrl && collect($validInternal)->contains(fn ($link) => $this->normalizeUrl($site, $link['url']) === $targetUrl);
        $leadWords = $this->words([['content' => $lead]]);
        $ai = (array) data_get($item->quality, 'ai', []);

        $checks = [
            'حداقل ۴ سرفصل H2' => $headings->count() >= 4,
            'طول مقاله در بازه‌ی بریف' => $wordCount >= $minimumWords && $wordCount <= $maximumWords,
            'پاسخ مستقیم در ابتدای مقاله' => $leadWords >= 35 && $leadWords <= 75,
            'کلمه‌ی هدف در عنوان' => $normalizedKeyword !== '' && str_contains(Fa::normalizeKeyword((string) $item->title), $normalizedKeyword),
            'کلمه‌ی هدف در مقدمه و یک H2' => $normalizedKeyword !== ''
                && str_contains(Fa::normalizeKeyword($lead), $normalizedKeyword)
                && $headings->contains(fn ($heading) => str_contains(Fa::normalizeKeyword((string) $heading), $normalizedKeyword)),
            'عنوان و توضیحات متا معتبر' => mb_strlen((string) $item->meta_title) >= 30 && mb_strlen((string) $item->meta_title) <= 65
                && mb_strlen((string) $item->meta_description) >= 110 && mb_strlen((string) $item->meta_description) <= 160,
            'حداقل ۳ لینک داخلی یکتا و توصیفی' => $distinctInternal->count() >= 3,
            'همه‌ی لینک‌ها داخلی و از فهرست واقعی' => $allLinksTrusted,
            'حداقل یک لینک به محصول واقعی' => count($validProductLinks) >= 1,
            'لینک به صفحه‌ی هدف کلمه' => (bool) $targetLinked,
            'حداقل ۳ پرسش متداول' => count((array) $item->faq) >= 3,
            'فراخوان اقدام موجود' => $blocks->contains('type', 'cta'),
            'ممیزی هوش مصنوعی قابل‌قبول' => ($ai['verdict'] ?? null) === 'publish'
                && (int) ($ai['quality'] ?? 0) >= 80 && (int) ($ai['eeat'] ?? 0) >= 70 && (int) ($ai['ai_likeness'] ?? 100) <= 40,
        ];
        $errors = array_keys(array_filter($checks, fn ($pass) => ! $pass));

        return [
            'passed' => $errors === [], 'checks' => $checks, 'errors' => $errors,
            'word_count' => $wordCount, 'word_range' => [$minimumWords, $maximumWords],
            'valid_internal_links' => $distinctInternal->count(), 'valid_product_links' => count($validProductLinks),
            'rendered_html' => BlocksToHtml::render($blocks->all()),
        ];
    }

    private function links(array $blocks): array
    {
        $links = [];
        foreach ($blocks as $block) {
            $text = (string) ($block['content'] ?? '');
            if (($block['type'] ?? '') === 'cta') $text .= ' ['.($block['button_label'] ?? '').']('.($block['button_url'] ?? '').')';
            preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^)\s]+|\/[^)\s]*)\)/u', $text, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) $links[] = ['anchor' => trim($match[1]), 'url' => trim($match[2])];
        }
        return $links;
    }

    private function normalizeUrl(Site $site, string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
        if ($url === '' || str_starts_with($url, '//')) return null;
        if (str_starts_with($url, '/')) $url = rtrim($site->base_url, '/').$url;
        $parts = parse_url($url);
        if (! $parts || empty($parts['host']) || ! in_array(strtolower((string) ($parts['scheme'] ?? 'https')), ['http', 'https'], true)) return null;
        if (strtolower($parts['host']) !== strtolower((string) $site->domain)) return null;
        return strtolower($parts['host']).'/'.trim(rawurldecode($parts['path'] ?? ''), '/');
    }

    private function descriptiveAnchor(string $anchor): bool
    {
        $anchor = Fa::normalizeKeyword($anchor);
        return mb_strlen($anchor) >= 3 && ! in_array($anchor, ['اینجا', 'بیشتر', 'کلیک کنید', 'مشاهده', 'ادامه'], true);
    }

    private function words(array $blocks): int
    {
        $text = collect($blocks)->map(fn ($block) => ($block['content'] ?? '').' '.implode(' ', (array) ($block['items'] ?? [])))->implode(' ');
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
