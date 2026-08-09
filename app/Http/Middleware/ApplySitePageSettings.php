<?php

namespace App\Http\Middleware;

use App\Services\SitePageService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ApplySitePageSettings
{
    public function __construct(private readonly SitePageService $pages)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $page = $this->pages->forRoute($request->route()?->getName());

        if (! $page) {
            return $next($request);
        }

        $isAdminPreview = auth('admin')->check();
        abort_if($page->maintenance_mode && ! $isAdminPreview, 503, $page->maintenance_message ?: 'این صفحه موقتاً در دسترس نیست.');
        abort_unless($page->isAvailable() || $isAdminPreview, 404);

        if ($page->requires_auth && ! auth()->check() && ! $isAdminPreview) {
            return redirect()->guest(route('login'));
        }

        View::share('sitePage', $page);

        return $next($request);
    }
}
