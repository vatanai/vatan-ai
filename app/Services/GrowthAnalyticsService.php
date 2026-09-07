<?php

namespace App\Services;

use App\Models\GrowthEvent;
use App\Models\GrowthLink;
use App\Models\GrowthLinkSnapshot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class GrowthAnalyticsService
{
    private const TIMEZONE = 'Asia/Tehran';

    /**
     * Persist every completed Tehran-calendar 24-hour window. Raw events are
     * never removed; snapshots are an immutable-history-friendly read model.
     */
    public function syncClosedWindows(GrowthLink $link): void
    {
        $firstEventAt = $link->events()->min('occurred_at');

        if (! $firstEventAt) {
            return;
        }

        $cursor = Carbon::parse($firstEventAt)->setTimezone(self::TIMEZONE)->startOfDay();
        $today = now(self::TIMEZONE)->startOfDay();

        while ($cursor->lt($today)) {
            $windowStart = $cursor->copy()->utc();
            $windowEnd = $cursor->copy()->addDay()->utc();
            $summary = $this->summarize($link, $windowStart, $windowEnd);

            GrowthLinkSnapshot::updateOrCreate(
                [
                    'growth_link_id' => $link->id,
                    'window_start' => $windowStart,
                ],
                [
                    'window_end' => $windowEnd,
                    'total_clicks' => $summary['total_clicks'],
                    'unique_clicks' => $summary['unique_clicks'],
                    'page_opens' => $summary['page_opens'],
                    'failed_opens' => $summary['failed_opens'],
                    'success_rate' => $summary['success_rate'],
                    'mobile_clicks' => $summary['mobile_clicks'],
                    'desktop_clicks' => $summary['desktop_clicks'],
                    'new_visitors' => $summary['new_visitors'],
                    'repeat_visitors' => $summary['repeat_visitors'],
                    'breakdowns' => $summary['breakdowns'],
                ]
            );

            $cursor->addDay();
        }
    }

    public function summarize(GrowthLink $link, CarbonInterface $start, CarbonInterface $end): array
    {
        $events = $link->events()
            ->where('occurred_at', '>=', $start)
            ->where('occurred_at', '<', $end)
            ->orderBy('occurred_at')
            ->get();

        $clicks = $events->where('event_type', GrowthEvent::TYPE_CLICK)->values();
        $pageOpens = $events->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->values();
        // بازشدن ممکن است چند ثانیه بعد و در پنجره روز بعد رخ بدهد؛ برای تشخیص
        // موفقیت هر کلیک، ارتباط والد را در کل تاریخ رویدادهای همان لینک می‌خوانیم.
        $openedClickIds = $clicks->isEmpty()
            ? collect()
            : $link->events()
                ->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)
                ->whereIn('parent_event_uuid', $clicks->pluck('event_uuid'))
                ->pluck('parent_event_uuid')
                ->unique();
        $successfulClickCount = $clicks->pluck('event_uuid')->intersect($openedClickIds)->count();
        $totalClicks = $clicks->count();

        return [
            'window_start' => $start->copy(),
            'window_end' => $end->copy(),
            'total_clicks' => $totalClicks,
            'unique_clicks' => $clicks->pluck('visitor_id')->filter()->unique()->count(),
            'page_opens' => $pageOpens->count(),
            'failed_opens' => max(0, $totalClicks - $successfulClickCount),
            'success_rate' => $totalClicks > 0 ? round(($successfulClickCount / $totalClicks) * 100, 2) : 0,
            'mobile_clicks' => $clicks->where('device_type', 'mobile')->count(),
            'desktop_clicks' => $clicks->where('device_type', 'desktop')->count(),
            'new_visitors' => $clicks->where('is_new_visitor', true)->count(),
            'repeat_visitors' => $clicks->where('is_new_visitor', false)->count(),
            'breakdowns' => [
                'operating_systems' => $this->breakdown($clicks, 'operating_system'),
                'browsers' => $this->breakdown($clicks, 'browser'),
                'sources' => $this->breakdown($clicks, 'source'),
                'countries' => $this->breakdown($clicks, 'country'),
                'cities' => $this->breakdown($clicks, 'city'),
            ],
        ];
    }

    /**
     * Completed windows come from persistent snapshots; only the current
     * window is calculated live. Nothing resets or disappears at midnight.
     */
    public function windows(GrowthLink $link, int $limit = 30): Collection
    {
        $this->syncClosedWindows($link);

        $currentLocalStart = now(self::TIMEZONE)->startOfDay();
        $currentStart = $currentLocalStart->copy()->utc();
        $currentEnd = $currentLocalStart->copy()->addDay()->utc();
        $current = collect($this->summarize($link, $currentStart, $currentEnd))
            ->put('is_live', true);

        $closed = $link->snapshots()
            ->latest('window_start')
            ->limit(max(1, $limit - 1))
            ->get()
            ->map(fn (GrowthLinkSnapshot $snapshot) => collect($snapshot->toArray())->put('is_live', false));

        return collect([$current])->concat($closed);
    }

    private function breakdown(Collection $events, string $field): array
    {
        return $events
            ->map(fn (GrowthEvent $event) => $event->{$field} ?: 'نامشخص')
            ->countBy()
            ->sortDesc()
            ->take(8)
            ->all();
    }
}
