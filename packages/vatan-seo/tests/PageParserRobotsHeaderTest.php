<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;
use Vatan\Seo\Services\PageParser;

class PageParserRobotsHeaderTest extends TestCase
{
    public function test_x_robots_tag_is_combined_with_meta_robots(): void
    {
        $html = '<html><head><meta name="robots" content="follow"></head><body></body></html>';

        $parsed = (new PageParser())->parse($html, 'https://aivatan.com/page', ['x-robots-tag' => 'noindex']);

        self::assertSame('follow, noindex', $parsed['robots']);
    }
}
