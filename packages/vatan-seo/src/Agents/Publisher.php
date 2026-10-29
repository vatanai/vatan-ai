<?php

namespace Vatan\Seo\Agents;

use Illuminate\Support\Facades\Http;
use Vatan\Seo\Connectors\ConnectorFactory;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Models\Task;

/** انتشار مقاله‌ی عبورکرده از دروازه‌ی سخت کیفیت + اعلام فوری به موتورها (IndexNow) */
class Publisher
{
    public function __construct(private ContentQualityGate $gate) {}

    public function publish(Site $site, ContentItem $item, string $by): ContentItem
    {
        if ($item->status === 'published') {
            return $item;
        }
        if (! in_array($item->status, ['review', 'approved'], true) || empty($item->blocks)) {
            throw new \RuntimeException('محتوا از تولید عبور نکرده یا بلوک محتوایی ندارد.');
        }
        $gate = $this->gate->evaluate($site, $item);
        if (! $gate['passed'] || ! data_get($item->quality, 'gate.passed')) {
            throw new \RuntimeException('دروازه‌ی کیفیت انتشار را رد کرد: '.implode('، ', $gate['errors']));
        }
        [$ref, $url] = ConnectorFactory::for($site)->publish($item);
        $item->update([
            'status' => 'published',
            'published_ref' => $ref,
            'published_url' => $url,
            'published_at' => now(),
            'approved_by' => $by,
            'approved_at' => now(),
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
