<?php

namespace Tests\Unit;

use Tests\TestCase;

class SitePageConfigurationTest extends TestCase
{
    public function test_public_home_is_managed_and_the_root_route_uses_site_page_middleware(): void
    {
        $page = config('site_pages.pages.landing');

        $this->assertSame('صفحه نخست سایت', $page['name_fa']);
        $this->assertSame('/', $page['path']);
        $this->assertContains('site.home.root', $page['route_names']);

        $route = app('router')->getRoutes()->getByName('site.home.root');
        $this->assertNotNull($route);
        $this->assertContains('site.page', $route->middleware());
    }
}
