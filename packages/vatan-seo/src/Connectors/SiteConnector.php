<?php

namespace Vatan\Seo\Connectors;

use Vatan\Seo\Models\ContentItem;

/**
 * قرارداد اتصال موتور به یک سایت. هر پلتفرم (لاراول داخلی، وردپرس، ...) یک پیاده‌سازی دارد.
 */
interface SiteConnector
{
    public function label(): string;

    /** آیا اتصال برای کار آماده است؟ [bool, پیام] */
    public function health(): array;

    /**
     * محصولات/خدمات سایت برای کشف کلمه.
     * @return array<int, array{id:mixed,title:string,description:string,category:?string,keywords:array,url:?string}>
     */
    public function products(int $limit = 200): array;

    /**
     * مقالات موجود (برای لینک‌سازی داخلی و جلوگیری از تکرار موضوع).
     * @return array<int, array{id:mixed,title:string,url:string,keywords:array}>
     */
    public function articles(int $limit = 300): array;

    /** انتشار مقاله؛ خروجی: [ref, url] */
    public function publish(ContentItem $item): array;
}
