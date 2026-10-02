<?php

namespace App\Http\Controllers\Admin\SmartInstagram;

use App\Services\SmartInstagram\MetricsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** گزارش رشد و درآمد (پروپوزال ۵.۱۰ و ۱۵) — هر عدد به لیست زیرش باز می‌شود. */
class ReportController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics): View
    {
        $this->authorizeAbility('view');
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;

        return view('admin.smart-instagram.reports', ['report' => $metrics->report($days)]);
    }
}
