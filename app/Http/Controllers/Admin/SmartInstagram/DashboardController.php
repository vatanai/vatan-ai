<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Services\SmartInstagram\MetricsService;
use Illuminate\View\View;

/** داشبورد اینستاگرام (مرکز فرماندهی): در کمتر از یک دقیقه معلوم کند امروز چه چیزی اقدام لازم دارد. */
class DashboardController extends Controller
{
    public function __invoke(MetricsService $metrics): View
    {
        $this->authorizeAbility('view');

        return view('admin.smart-instagram.dashboard', $metrics->dashboard() + [
            'outboundEnabled' => (bool) config('smart_instagram.outbound_enabled'),
            'aiEnabled' => (bool) config('smart_instagram.ai.enabled'),
        ]);
    }
}
