<?php

namespace Vatan\Seo\Http\Controllers;

use Illuminate\Routing\Controller;
use Vatan\Seo\Models\Site;
use Vatan\Seo\Services\SiteManager;

abstract class BaseController extends Controller
{
    protected function site(): Site
    {
        return app(SiteManager::class)->current();
    }

    protected function adminRef(): string
    {
        $guard = config('seo-engine.host.admin_guard', 'admin');
        return 'admin:'.(auth($guard)->id() ?? auth()->id() ?? '0');
    }

    protected function view(string $name, array $data = [])
    {
        return view('seo::'.$name, $data + ['site' => $this->site()]);
    }
}
