<?php

use Tests\TestCase;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\ContentOperationLock;

class ContentOperationLockTest extends TestCase
{
    public function test_only_one_generation_or_redraft_for_a_keyword_can_run_at_a_time(): void
    {
        $site = new Site(['id' => 7]);
        $first = ContentOperationLock::forKeyword($site, 42);
        $duplicate = ContentOperationLock::forKeyword($site, 42);
        $otherKeyword = ContentOperationLock::forKeyword($site, 43);

        self::assertTrue($first->get());
        self::assertFalse($duplicate->get());
        self::assertTrue($otherKeyword->get());

        $first->release();
        self::assertTrue($duplicate->get());
        $duplicate->release();
        $otherKeyword->release();
    }
}
