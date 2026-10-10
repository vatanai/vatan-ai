<?php

namespace Vatan\Seo\Connectors;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;

/**
 * اتصال به سایت وردپرسی مشتری با REST API و «رمز برنامه» (Application Password).
 * connector_config: { url, username, app_password, publish_status: draft|publish, category_id? }
 */
class WordPressConnector implements SiteConnector
{
    public function __construct(private Site $site) {}

    public function label(): string
    {
        return 'وردپرس (REST API)';
    }

    protected function cfg(): array
    {
        return $this->site->connectorConfig() + ['url' => $this->site->base_url, 'publish_status' => 'draft'];
    }

    protected function http()
    {
        $c = $this->cfg();
        $req = Http::connectTimeout(10)->timeout(40)->acceptJson();
        if (! empty($c['username']) && ! empty($c['app_password'])) {
            $req = $req->withBasicAuth($c['username'], str_replace(' ', '', $c['app_password']));
        }
        return $req;
    }

    protected function api(string $path): string
    {
        return rtrim($this->cfg()['url'], '/').'/wp-json/'.ltrim($path, '/');
    }

    public function health(): array
    {
        try {
            $res = $this->http()->get($this->api('wp/v2/users/me'));
            return $res->successful()
                ? [true, 'اتصال وردپرس برقرار است ('.$res->json('name').').']
                : [false, 'ورود به وردپرس ناموفق بود (HTTP '.$res->status().').'];
        } catch (\Throwable $e) {
            return [false, 'سایت وردپرس در دسترس نیست: '.$e->getMessage()];
        }
    }

    public function products(int $limit = 200): array
    {
        $out = [];
        try {
            // WooCommerce Store API (عمومی) — اگر نبود، برگه‌ها به‌عنوان «خدمات»
            $res = $this->http()->get($this->api('wc/store/v1/products'), ['per_page' => min(100, $limit)]);
            if ($res->successful()) {
                foreach ((array) $res->json() as $p) {
                    $out[] = [
                        'id' => $p['id'], 'title' => html_entity_decode((string) $p['name']),
                        'description' => mb_substr(trim(strip_tags((string) ($p['short_description'] ?: $p['description']))), 0, 600),
                        'category' => $p['categories'][0]['name'] ?? null, 'keywords' => [], 'url' => $p['permalink'] ?? null,
                    ];
                }
                return $out;
            }
            $res = $this->http()->get($this->api('wp/v2/pages'), ['per_page' => 50, '_fields' => 'id,title,excerpt,link']);
            foreach ((array) $res->json() as $p) {
                $out[] = ['id' => $p['id'], 'title' => html_entity_decode(strip_tags((string) data_get($p, 'title.rendered'))), 'description' => mb_substr(strip_tags((string) data_get($p, 'excerpt.rendered')), 0, 400), 'category' => null, 'keywords' => [], 'url' => $p['link'] ?? null];
            }
        } catch (\Throwable) {
        }
        return $out;
    }

    public function articles(int $limit = 300): array
    {
        try {
            $res = $this->http()->get($this->api('wp/v2/posts'), ['per_page' => min(100, $limit), '_fields' => 'id,title,link']);
            return collect((array) $res->json())->map(fn ($p) => ['id' => $p['id'], 'title' => html_entity_decode(strip_tags((string) data_get($p, 'title.rendered'))), 'url' => (string) $p['link'], 'keywords' => []])->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function publish(ContentItem $item): array
    {
        $c = $this->cfg();
        $payload = [
            'title' => $item->title,
            'slug' => $item->slug,
            'status' => ($c['publish_status'] ?? 'draft') === 'publish' ? 'publish' : 'draft',
            'content' => BlocksToHtml::render((array) $item->blocks),
            'excerpt' => $item->meta_description,
        ];
        if (! empty($c['category_id'])) {
            $payload['categories'] = [(int) $c['category_id']];
        }
        $res = $this->http()->post($this->api('wp/v2/posts'), $payload);
        if (! $res->successful()) {
            throw new RuntimeException('انتشار در وردپرس ناموفق بود: '.mb_substr($res->body(), 0, 300));
        }
        return [(string) $res->json('id'), (string) $res->json('link')];
    }
}
