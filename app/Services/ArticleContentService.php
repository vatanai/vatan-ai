<?php

namespace App\Services;

class ArticleContentService
{
    private const TYPES = ['lead', 'paragraph', 'heading', 'note', 'quote', 'prompt', 'image', 'video', 'list', 'cta'];

    public function normalize(array $blocks): array
    {
        return collect($blocks)
            ->filter(fn ($block) => is_array($block) && in_array($block['type'] ?? null, self::TYPES, true))
            ->map(function (array $block) {
                $type = $block['type'];
                $normalized = ['type' => $type];

                foreach (['title', 'content', 'caption', 'url', 'alt', 'button_label', 'button_url'] as $key) {
                    if (array_key_exists($key, $block)) {
                        $normalized[$key] = trim(strip_tags((string) $block[$key]));
                    }
                }

                foreach (['url', 'button_url'] as $urlKey) {
                    if (array_key_exists($urlKey, $normalized)) {
                        $normalized[$urlKey] = $this->safeUrl($normalized[$urlKey]);
                    }
                }

                if ($type === 'heading') {
                    $normalized['level'] = in_array((int) ($block['level'] ?? 2), [2, 3, 4], true)
                        ? (int) $block['level']
                        : 2;
                }

                if ($type === 'list') {
                    $normalized['items'] = collect(preg_split('/\r\n|\r|\n/u', (string) ($block['content'] ?? '')) ?: [])
                        ->map(fn ($item) => trim(strip_tags($item)))
                        ->filter()
                        ->values()
                        ->all();
                    unset($normalized['content']);
                }

                return $normalized;
            })
            ->filter(function (array $block) {
                if ($block['type'] === 'list') return ! empty($block['items']);
                if (in_array($block['type'], ['image', 'video'], true)) return ! empty($block['url']);
                if ($block['type'] === 'cta') return ! empty($block['title']) || ! empty($block['button_url']);

                return ! empty($block['content']) || ! empty($block['title']);
            })
            ->values()
            ->all();
    }

    private function safeUrl(string $url): string
    {
        if ($url === '') return '';
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) return $url;
        if (filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) return $url;

        return '';
    }
}
