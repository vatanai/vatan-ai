<?php

namespace Vatan\Content\Http\Controllers;

use Vatan\Content\Models\AiUsage;
use Vatan\Content\Models\Calendar;
use Vatan\Content\Models\CalendarItem;
use Vatan\Content\Models\MusicTrack;
use Vatan\Content\Models\Production;
use Vatan\Content\Models\Reference;
use Vatan\Content\Models\Style;

class OverviewController extends Controller
{
    public function __invoke()
    {
        $ws = $this->ws();
        $calendar = Calendar::where('workspace_id', $ws)->orderByRaw("status = 'approved' desc")->latest('id')->first();
        $today = now(config('content-factory.host.timezone'))->startOfDay();
        $upcoming = $calendar ? CalendarItem::where('calendar_id', $calendar->id)->whereDate('publish_date', '>=', $today)
            ->orderBy('publish_date')->limit(7)->with('production')->get() : collect();
        $statusCounts = Production::where('workspace_id', $ws)->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('cf::overview', [
            'cfTitle' => 'فرماندهی', 'cfIcon' => 'fa-gauge-high', 'cfHelp' => 'overview',
            'cfSubtitle' => 'وضعیت کل کارخانه‌ی محتوا: تقویم، صف تولید، تأیید و هزینه.',
            'calendar' => $calendar,
            'upcoming' => $upcoming,
            'todayItem' => $upcoming->first(fn ($i) => $i->publish_date->isSameDay($today)),
            'statusCounts' => $statusCounts,
            'review' => Production::where('workspace_id', $ws)->whereIn('status', ['review', 'revision'])->latest('updated_at')->limit(5)->get(),
            'stats' => [
                'items' => $calendar ? $calendar->items()->count() : 0,
                'published' => $statusCounts['published'] ?? 0,
                'styles' => Style::where('workspace_id', $ws)->count(),
                'styles_pending' => Style::where('workspace_id', $ws)->where('status', 'pending')->count(),
                'refs' => Reference::where('workspace_id', $ws)->count(),
                'music' => MusicTrack::where('workspace_id', $ws)->count(),
                'cost_month' => (float) AiUsage::where('workspace_id', $ws)->where('created_at', '>=', now()->startOfMonth())->sum('cost_usd')
                    + (float) Production::where('workspace_id', $ws)->where('created_at', '>=', now()->startOfMonth())->sum('cost_usd'),
            ],
        ]);
    }
}
