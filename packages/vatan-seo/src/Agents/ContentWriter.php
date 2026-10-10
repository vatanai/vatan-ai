<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Str;
use Vatan\Seo\Ai\Ai;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Support\Budget;
use Vatan\Seo\Support\Fa;
use Vatan\Seo\Support\Prompt;

/**
 * خط تولید مقاله‌ی یونیک:
 *  ۱. بریف (پژوهش زنده‌ی SERP با Grok در صورت مجاز بودن + محصولات و مقالات موجود)
 *  ۲. پیش‌نویس (مدل نویسنده)
 *  ۳. ممیزی: امتیاز سئوی قاعده‌محور + ممیزی کیفیت با مدل سریع
 *  ۴. «منتظر تأیید» ← تلگرام / داشبورد ← انتشار
 */
class ContentWriter
{
    public function __construct(private Ai $ai) {}

    public function brief(Site $site, Keyword $kw, ?ContentItem $item = null): ContentItem
    {
        $connector = ConnectorFactory::for($site);
        $existing = collect($connector->articles(80))->map(fn ($a) => '- '.$a['title'].' | '.$a['url'])->implode("\n");
        $products = collect($connector->products(200))
            ->sortByDesc(fn ($p) => similar_text(Fa::normalizeKeyword($p['title']), $kw->normalized))
            ->take(6)->map(fn ($p) => '- '.$p['title'].' | '.$p['url'])->implode("\n");

        $research = '';
        if ($this->ai->available($site, 'research') && Budget::limit($site, 'research_calls_per_month') > Budget::researchCallsThisMonth($site)) {
            try {
                $research = "یافته‌های پژوهش زنده از نتایج فعلی گوگل:\n".$this->ai->text($site, 'research', 'serp-research',
                    'You are an SEO researcher. Search the live web (Google Iran results in Persian) and summarize in Persian, as concise bullet points: what the top 5 ranking pages cover, their angles, gaps none of them cover well, and common user questions. No preamble.',
                    'کلمه‌ی جستجو: '.$kw->keyword, ['web' => true, 'max_tokens' => 1200, 'temperature' => 0.2]);
            } catch (\Throwable $e) {
                $research = '';
            }
        }

        $brief = $this->ai->json($site, 'strategist', 'content-brief', Prompt::get('content-brief', [
            'brand' => Prompt::brand($site),
            'keyword' => $kw->keyword,
            'intent' => $kw->intent ?: 'informational',
            'target_url' => $kw->target_url ?: data_get($kw->meta, 'product_url', $site->url('/')),
            'existing' => $existing ?: '—',
            'products' => $products ?: '—',
            'research' => $research,
        ]), 'بریف را بساز.', ['max_tokens' => 3000, 'temperature' => 0.5]);

        $item ??= new ContentItem(['site_id' => $site->id, 'keyword_id' => $kw->id, 'type' => 'article']);
        $item->fill([
            'status' => 'brief',
            'title' => $brief['title_options'][0] ?? $kw->keyword,
            'target_url' => $kw->target_url,
            'brief' => $brief + ['research_used' => $research !== ''],
        ]);
        $item->cost_usd = (float) $item->cost_usd;
        $item->save();

        return $item;
    }

    public function draft(Site $site, ContentItem $item): ContentItem
    {
        $item->update(['status' => 'drafting']);
        $json = $this->ai->json($site, 'writer', 'content-draft', Prompt::get('content-draft', [
            'brand' => Prompt::brand($site),
            'brief' => array_merge(['keyword' => $item->keyword?->keyword, 'title' => $item->title], (array) $item->brief),
        ]), 'مقاله را کامل بنویس.', ['max_tokens' => 9000, 'temperature' => 0.65]);

        $blocks = $this->normalizeBlocks((array) ($json['blocks'] ?? []));
        if (count($blocks) < 4) {
            $item->update(['status' => 'failed', 'reviewer_note' => 'پیش‌نویس ناقص بود.']);
            return $item;
        }
        $faq = array_values(array_filter((array) ($json['faq'] ?? []), fn ($f) => ! empty($f['q']) && ! empty($f['a'])));
        if ($faq) {
            $blocks[] = ['type' => 'heading', 'content' => 'سؤالات متداول', 'level' => 2];
            foreach ($faq as $f) {
                $blocks[] = ['type' => 'heading', 'content' => (string) $f['q'], 'level' => 3];
                $blocks[] = ['type' => 'paragraph', 'content' => (string) $f['a']];
            }
        }
        $slug = Str::slug((string) ($json['slug'] ?? ''), '-') ?: Str::slug(Str::ascii((string) $item->title)) ?: 'article-'.$item->id;

        $item->fill([
            'title' => $json['title'] ?? $item->title,
            'slug' => Str::limit($slug, 80, ''),
            'meta_title' => Str::limit((string) ($json['meta_title'] ?? $json['title'] ?? ''), 65, ''),
            'meta_description' => Str::limit((string) ($json['meta_description'] ?? ''), 160, ''),
            'blocks' => $blocks,
            'faq' => $faq,
            'word_count' => $this->words($blocks),
        ]);
        $item->save();

        return $this->review($site, $item);
    }

    public function review(Site $site, ContentItem $item): ContentItem
    {
        [$score, $checks] = $this->seoScore($item);
        $quality = ['checks' => $checks];
        try {
            $text = collect((array) $item->blocks)->map(fn ($b) => $b['content'] ?? implode("\n", (array) ($b['items'] ?? [])))->implode("\n\n");
            $quality['ai'] = $this->ai->json($site, 'fast', 'content-review', Prompt::get('content-review', ['keyword' => $item->keyword?->keyword, 'article' => mb_substr($text, 0, 14000)]), 'ممیزی کن.', ['max_tokens' => 1200, 'temperature' => 0.1]);
        } catch (\Throwable) {
            // ممیزی AI اختیاری است
        }
        $item->update([
            'seo_score' => $score,
            'quality' => $quality,
            'status' => 'review',
            'cost_usd' => (float) \Vatan\Seo\Models\AiCall::where('site_id', $site->id)->whereIn('purpose', ['content-brief', 'content-draft', 'content-review', 'serp-research'])->where('created_at', '>=', $item->created_at)->sum('cost_usd'),
        ]);
        return $item;
    }

    /** امتیاز سئوی قاعده‌محور (بدون هزینه) */
    public function seoScore(ContentItem $item): array
    {
        $kw = $item->keyword?->normalized ?? '';
        $blocks = (array) $item->blocks;
        $lead = Fa::normalizeKeyword(collect($blocks)->firstWhere('type', 'lead')['content'] ?? '');
        $h2 = collect($blocks)->where('type', 'heading')->where('level', 2)->pluck('content')->map(fn ($t) => Fa::normalizeKeyword((string) $t));
        $text = collect($blocks)->map(fn ($b) => $b['content'] ?? implode(' ', (array) ($b['items'] ?? [])))->implode(' ');
        $links = preg_match_all('/\[[^\]]+\]\((https?:\/\/[^)]+|\/[^)]*)\)/u', $text);
        $words = $item->word_count ?: $this->words($blocks);
        $target = (int) data_get($item->brief, 'word_target', 1200);

        $checks = [
            'کلمه در عنوان' => $kw !== '' && str_contains(Fa::normalizeKeyword((string) $item->title), $kw),
            'کلمه در پاراگراف اول' => $kw !== '' && str_contains($lead, $kw),
            'کلمه در یک H2' => $kw !== '' && $h2->contains(fn ($h) => str_contains($h, $kw)),
            'طول عنوان متا ۳۰ تا ۶۵' => mb_strlen((string) $item->meta_title) >= 30 && mb_strlen((string) $item->meta_title) <= 65,
            'توضیحات متا ۱۱۰ تا ۱۶۰' => mb_strlen((string) $item->meta_description) >= 110 && mb_strlen((string) $item->meta_description) <= 160,
            'حداقل ۴ سرفصل H2' => $h2->count() >= 4,
            'حداقل ۳ لینک داخلی' => $links >= 3,
            'طول متن نزدیک به هدف بریف' => $words >= $target * 0.8,
            'بخش سؤالات متداول' => ! empty($item->faq),
            'فراخوان (CTA)' => collect($blocks)->contains('type', 'cta'),
        ];
        $score = (int) round(count(array_filter($checks)) / count($checks) * 100);

        return [$score, $checks];
    }

    protected function normalizeBlocks(array $blocks): array
    {
        $allowed = ['lead', 'paragraph', 'heading', 'note', 'quote', 'list', 'cta'];
        $out = [];
        foreach ($blocks as $b) {
            $type = $b['type'] ?? null;
            if (! in_array($type, $allowed, true)) {
                continue;
            }
            $n = ['type' => $type];
            foreach (['content', 'title', 'button_label', 'button_url'] as $k) {
                if (isset($b[$k]) && is_scalar($b[$k])) {
                    $n[$k] = trim(strip_tags((string) $b[$k]));
                }
            }
            if ($type === 'heading') {
                $n['level'] = in_array((int) ($b['level'] ?? 2), [2, 3, 4], true) ? (int) $b['level'] : 2;
            }
            if ($type === 'list') {
                $n['items'] = array_values(array_filter(array_map(fn ($i) => trim(strip_tags((string) $i)), (array) ($b['items'] ?? []))));
                if (! $n['items']) continue;
                // سازگاری با ArticleContentService وطن (فهرست را از content خط‌به‌خط می‌سازد)
                $n['content'] = implode("\n", $n['items']);
            } elseif (empty($n['content']) && $type !== 'cta') {
                continue;
            }
            $out[] = $n;
        }
        return $out;
    }

    protected function words(array $blocks): int
    {
        $text = collect($blocks)->map(fn ($b) => ($b['content'] ?? '').' '.implode(' ', (array) ($b['items'] ?? [])))->implode(' ');
        return count(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
    }
}
