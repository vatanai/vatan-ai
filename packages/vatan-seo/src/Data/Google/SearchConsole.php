<?php

namespace Vatan\Seo\Data\Google;

use RuntimeException;

/** Google Search Console API — گزارش جستجو، نقشه‌ی سایت و بازرسی URL */
class SearchConsole
{
    private const BASE = 'https://www.googleapis.com/webmasters/v3';
    private const INSPECT = 'https://searchconsole.googleapis.com/v1/urlInspection/index:inspect';

    public function configured(): bool
    {
        return ServiceAccount::configured();
    }

    protected function client(int $timeout = 60)
    {
        return GoogleHttp::request($timeout)->withToken(ServiceAccount::token());
    }

    /** فهرست propertyهایی که سرویس‌اکانت به آن‌ها دسترسی دارد */
    public function sites(): array
    {
        $res = $this->client(20)->get(GoogleHttp::url(self::BASE.'/sites'));
        $this->ensure($res);
        return collect($res->json('siteEntry', []))->map(fn ($s) => ['url' => $s['siteUrl'], 'permission' => $s['permissionLevel']])->all();
    }

    /**
     * searchAnalytics.query با صفحه‌بندی خودکار.
     * @param string[] $dimensions مثل ['date'] یا ['query','page'] یا ['date','query','page']
     */
    public function query(string $property, string $start, string $end, array $dimensions, int $maxRows = 25000, array $filters = []): array
    {
        $rows = [];
        $startRow = 0;
        do {
            $body = [
                'startDate' => $start,
                'endDate' => $end,
                'dimensions' => $dimensions,
                'rowLimit' => min(25000, $maxRows - count($rows)),
                'startRow' => $startRow,
                'dataState' => 'all',
            ];
            if ($filters) {
                $body['dimensionFilterGroups'] = [['filters' => $filters]];
            }
            $res = $this->client()->post(GoogleHttp::url(self::BASE.'/sites/'.rawurlencode($property).'/searchAnalytics/query'), $body);
            $this->ensure($res);
            $batch = (array) $res->json('rows', []);
            foreach ($batch as $row) {
                $item = ['clicks' => (int) $row['clicks'], 'impressions' => (int) $row['impressions'], 'ctr' => (float) $row['ctr'], 'position' => (float) $row['position']];
                foreach ($dimensions as $i => $dim) {
                    $item[$dim] = $row['keys'][$i] ?? null;
                }
                $rows[] = $item;
            }
            $startRow += count($batch);
        } while (count($batch) === 25000 && count($rows) < $maxRows);

        return $rows;
    }

    public function sitemaps(string $property): array
    {
        $res = $this->client(20)->get(GoogleHttp::url(self::BASE.'/sites/'.rawurlencode($property).'/sitemaps'));
        $this->ensure($res);
        return (array) $res->json('sitemap', []);
    }

    public function submitSitemap(string $property, string $sitemapUrl): void
    {
        $res = $this->client(20)->put(GoogleHttp::url(self::BASE.'/sites/'.rawurlencode($property).'/sitemaps/'.rawurlencode($sitemapUrl)));
        $this->ensure($res);
    }

    public function inspect(string $property, string $url): array
    {
        $res = $this->client(30)->post(GoogleHttp::url(self::INSPECT), ['inspectionUrl' => $url, 'siteUrl' => $property, 'languageCode' => 'fa']);
        $this->ensure($res);
        $r = (array) $res->json('inspectionResult.indexStatusResult', []);
        return [
            'verdict' => $r['verdict'] ?? 'UNKNOWN',
            'coverage' => $r['coverageState'] ?? null,
            'indexing' => $r['indexingState'] ?? null,
            'last_crawl' => $r['lastCrawlTime'] ?? null,
            'canonical' => $r['googleCanonical'] ?? null,
            'robots' => $r['robotsTxtState'] ?? null,
        ];
    }

    protected function ensure($res): void
    {
        if (! $res->successful()) {
            $msg = (string) data_get($res->json(), 'error.message', $res->body());
            if ($res->status() === 403) {
                $msg = 'سرویس‌اکانت به این property دسترسی ندارد. ایمیل '.ServiceAccount::email().' را در سرچ کنسول به‌عنوان کاربر اضافه کنید. ('.$msg.')';
            }
            throw new RuntimeException('Search Console: '.mb_substr($msg, 0, 400));
        }
    }
}
