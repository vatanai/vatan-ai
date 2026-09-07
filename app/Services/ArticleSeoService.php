<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Support\Str;

class ArticleSeoService
{
    public function prepare(array $data, ?Article $article = null): array
    {
        $auto = (bool) ($data['seo_auto_fill'] ?? false);
        $title = trim((string) ($data['title'] ?? ''));
        $excerpt = trim((string) ($data['excerpt'] ?? ''));
        $tags = $this->parseList($data['tags'] ?? '');

        $requestedSlug = trim((string) ($data['slug'] ?? ''));
        if ($requestedSlug === '' || ($auto && ! $article)) {
            $requestedSlug = Str::slug($title);
        }
        if ($requestedSlug === '') {
            $requestedSlug = 'article-' . now()->format('Ymd-His');
        }
        $data['slug'] = $this->uniqueSlug(Str::limit(Str::slug($requestedSlug), 180, ''), $article);

        $category = ! empty($data['article_category_id'])
            ? ArticleCategory::query()->find($data['article_category_id'])
            : null;

        if ($auto || blank($data['meta_title'] ?? null)) {
            $data['meta_title'] = Str::limit($title . ' | وطن', 180, '');
        }
        if ($auto || blank($data['meta_description'] ?? null)) {
            $data['meta_description'] = Str::limit($excerpt, 300, '');
        }

        $keywords = $this->parseList($data['seo_keywords_text'] ?? '');
        if ($auto || $keywords === []) {
            $titleWords = collect(preg_split('/[\s،,:؛\-]+/u', $title) ?: [])
                ->map(fn ($word) => trim($word))
                ->filter(fn ($word) => mb_strlen($word) >= 3)
                ->take(6)
                ->all();
            $keywords = collect($tags)
                ->merge($category?->name ? [$category->name] : [])
                ->merge($titleWords)
                ->unique()
                ->take(12)
                ->values()
                ->all();
        }
        $data['seo_keywords'] = $keywords;

        $hashtags = $this->parseList($data['hashtags_text'] ?? '');
        if ($auto || $hashtags === []) {
            $hashtags = collect($tags ?: $keywords)
                ->map(fn ($tag) => '#' . str_replace([' ', '-', '#'], ['_', '_', ''], trim($tag)))
                ->filter(fn ($tag) => $tag !== '#')
                ->unique()
                ->take(8)
                ->values()
                ->all();
        } else {
            $hashtags = collect($hashtags)
                ->map(fn ($tag) => '#' . ltrim(str_replace(' ', '_', $tag), '#'))
                ->unique()
                ->values()
                ->all();
        }
        $data['hashtags'] = $hashtags;
        $data['tags_list'] = $tags;
        $data['reading_minutes'] = $this->readingMinutes($data['content_blocks'] ?? []);

        unset($data['tags'], $data['seo_keywords_text'], $data['hashtags_text']);

        return $data;
    }

    private function uniqueSlug(string $base, ?Article $article): string
    {
        $base = $base ?: 'article';
        $slug = $base;
        $suffix = 2;

        while (Article::withTrashed()
            ->when($article, fn ($query) => $query->whereKeyNot($article->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = Str::limit($base, 170, '') . '-' . $suffix++;
        }

        return $slug;
    }

    public function parseList(array|string|null $value): array
    {
        $items = is_array($value) ? $value : preg_split('/[,،\n]+/u', (string) $value);

        return collect($items ?: [])
            ->map(fn ($item) => trim(strip_tags((string) $item)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function readingMinutes(array $blocks): int
    {
        $text = collect($blocks)
            ->flatMap(fn ($block) => [data_get($block, 'title'), data_get($block, 'content'), data_get($block, 'caption')])
            ->filter()
            ->implode(' ');
        $words = count(preg_split('/\s+/u', trim(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, (int) ceil($words / 180));
    }
}
