<?php

use Tests\TestCase;
use Vatan\Seo\Agents\ContentQualityGate;
use Vatan\Seo\Models\ContentItem;
use Vatan\Seo\Models\Keyword;
use Vatan\Seo\Models\Site;

class ContentQualityGateTest extends TestCase
{
    private function validItem(string $body): array
    {
        $site = new Site(['domain' => 'aivatan.com', 'base_url' => 'https://aivatan.com']);
        $keyword = new Keyword(['keyword' => 'ساخت عکس محصول با هوش مصنوعی']);
        $item = new ContentItem([
            'title' => 'ساخت عکس محصول با هوش مصنوعی؛ راهنمای فروشگاه',
            'target_url' => 'https://aivatan.com/product/p1',
            'meta_title' => 'ساخت عکس محصول با هوش مصنوعی | راهنمای فروشگاه',
            'meta_description' => 'راهنمای ساخت عکس محصول با هوش مصنوعی برای فروشگاه‌ها؛ انتخاب محصول، آماده‌سازی تصویر، بررسی کیفیت و ابزار مناسب وطن را مرحله‌به‌مرحله بشناسید.',
            'blocks' => [
                ['type' => 'lead', 'content' => 'ساخت عکس محصول با هوش مصنوعی به فروشگاه کمک می‌کند تصاویر کاربردی بسازد. انتخاب ابزار مناسب به نیاز واقعی، کیفیت ورودی و بازبینی جزئیات محصول وابسته است. در این راهنما مراحل را گام‌به‌گام بررسی می‌کنیم تا فروشگاه بتواند خروجی را آگاهانه ارزیابی و برای هر محصول انتخاب مناسبی انجام دهد.'],
                ['type' => 'heading', 'level' => 2, 'content' => 'ساخت عکس محصول با هوش مصنوعی چگونه انجام می‌شود؟'],
                ['type' => 'paragraph', 'content' => '[محصول عکس](/product/p1) [ابزار مرتبط](/product/p2) [راهنمای تکمیلی](/articles/guide) '.$body],
                ['type' => 'heading', 'level' => 2, 'content' => 'انتخاب ابزار'], ['type' => 'paragraph', 'content' => $body],
                ['type' => 'heading', 'level' => 2, 'content' => 'آماده‌سازی ورودی'], ['type' => 'paragraph', 'content' => $body],
                ['type' => 'heading', 'level' => 2, 'content' => 'ارزیابی نتیجه'], ['type' => 'paragraph', 'content' => $body],
                ['type' => 'heading', 'level' => 2, 'content' => 'جمع‌بندی'], ['type' => 'paragraph', 'content' => $body],
                ['type' => 'cta', 'title' => 'شروع', 'button_label' => 'دیدن محصول', 'button_url' => '/product/p1'],
            ],
            'faq' => [['q' => 'الف؟', 'a' => 'ب'], ['q' => 'ج؟', 'a' => 'د'], ['q' => 'ه؟', 'a' => 'و']],
            'brief' => ['word_target' => 1200, 'product_links' => ['/product/p1', '/product/p2'], 'internal_link_catalog' => ['/product/p1', '/product/p2', '/articles/guide']],
            'quality' => ['ai' => ['verdict' => 'publish', 'quality' => 88, 'eeat' => 75, 'ai_likeness' => 25]],
        ]);
        $item->word_count = 1100;
        $item->setRelation('keyword', $keyword);
        return [$site, $item];
    }

    public function test_valid_content_passes_the_unattended_publish_gate(): void
    {
        [$site, $item] = $this->validItem(str_repeat('هر فروشگاه باید تصویر و ابزار را با نیاز واقعی مشتری بسنجد. ', 180));
        $result = (new ContentQualityGate())->evaluate($site, $item);
        self::assertTrue($result['passed'], implode('؛ ', $result['errors']));
        self::assertGreaterThanOrEqual(3, $result['valid_internal_links']);
        self::assertGreaterThanOrEqual(1, $result['valid_product_links']);
    }

    public function test_gate_rejects_external_links_even_when_every_other_check_passes(): void
    {
        [$site, $item] = $this->validItem(str_repeat('هر فروشگاه باید تصویر و ابزار را با نیاز واقعی مشتری بسنجد. ', 180));
        $blocks = $item->blocks;
        $blocks[2]['content'] = '[محصول خارجی](https://example.org/product)';
        $item->blocks = $blocks;
        $result = (new ContentQualityGate())->evaluate($site, $item);
        self::assertFalse($result['passed']);
        self::assertContains('همه‌ی لینک‌ها داخلی و از فهرست واقعی', $result['errors']);
    }
}
