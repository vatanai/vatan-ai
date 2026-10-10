<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Facades\Http;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;

/** انتشار مقاله‌ی تأییدشده در سایت + اعلام فوری به موتورها (IndexNow) */
class Publisher
{
    public function publish(Site $site, ContentItem $item, string $by): ContentItem
    {
        if (! in_array($item->status, ['review', 'approved', 'failed'], true) || empty($item->blocks)) {
            throw new \RuntimeException('این محتوا آماده‌ی انتشار نیست.');
        }
        [$ref, $url] = ConnectorFactory::for($site)->publish($item);
        $item->update([
            'status' => 'published',
            'published_ref' => $ref,
            'published_url' => $url,
            'published_at' => now(),
            'approved_by' => $item->approved_by ?: $by,
            'approved_at' => $item->approved_at ?: now(),
        ]);
        $this->indexNow($site, [$url]);

        if ($item->keyword_id) {
            Task::where('site_id', $site->id)->where('keyword_id', $item->keyword_id)->where('playbook_key', 'kw.article')->update([
                'status' => 'done', 'completed_at' => now(), 'completed_by' => $by, 'last_message' => 'مقاله منتشر شد: '.urldecode($url),
            ]);
        }
        return $item;
    }

    public function indexNow(Site $site, array $urls): bool
    {
        $key = (string) config('seo-engine.indexnow.key');
        if ($key === '' || ! $urls) {
            return false;
        }
        try {
            return Http::timeout(15)->post('https://api.indexnow.org/indexnow', [
                'host' => parse_url($site->base_url, PHP_URL_HOST),
                'key' => $key,
                'keyLocation' => $site->url('/'.$key.'.txt'),
                'urlList' => array_values($urls),
            ])->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
