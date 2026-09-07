<?php

namespace App\Services;

use App\Models\GrowthContent;
use App\Models\GrowthEvent;
use App\Models\MarketingContent;
use App\Models\MarketingEvent;
use App\Models\MarketingScenario;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MarketingAnalyticsService
{
    private const TIMEZONE = 'Asia/Tehran';

    public function report(array $filters = []): array
    {
        [$from, $to] = $this->resolveRange($filters);
        $growthEvents = $this->growthEvents($from, $to);
        $marketingEvents = $this->marketingEvents($from, $to);
        if (!empty($filters['channel'])) {
            $channel = (string) $filters['channel'];
            $marketingEvents = $marketingEvents->where('channel', $channel)->values();
            if ($channel !== 'instagram') {
                $growthEvents = collect();
            }
        }
        $contentStats = $this->contentStats($from, $to);

        $metrics = [
            'views' => (int) $contentStats['views'],
            'engagements' => (int) $contentStats['engagements'],
            'comments' => (int) $contentStats['comments'] + $this->countMarketingEvents($marketingEvents, ['comment.received']),
            'messages' => $this->countMarketingEvents($marketingEvents, ['dm.received', 'message.received']),
            'clicks' => $growthEvents->where('event_type', GrowthEvent::TYPE_CLICK)->count() + $this->countMarketingEvents($marketingEvents, ['link.clicked', 'click']),
            'opens' => $growthEvents->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count() + $this->countMarketingEvents($marketingEvents, ['link.opened', 'page_open']),
            'builds' => $this->countMarketingEvents($marketingEvents, ['generation.completed', 'build.completed']),
            'purchases' => $this->countMarketingEvents($marketingEvents, ['purchase.completed', 'purchase']),
        ];
        $metrics['engagement_rate'] = $metrics['views'] > 0 ? round(($metrics['engagements'] / $metrics['views']) * 100, 2) : 0;
        $metrics['click_rate'] = $metrics['views'] > 0 ? round(($metrics['clicks'] / $metrics['views']) * 100, 2) : 0;
        $metrics['open_rate'] = $metrics['clicks'] > 0 ? round(($metrics['opens'] / $metrics['clicks']) * 100, 2) : 0;
        $metrics['purchase_rate'] = $metrics['clicks'] > 0 ? round(($metrics['purchases'] / $metrics['clicks']) * 100, 2) : 0;

        return [
            'range' => ['from' => $from, 'to' => $to],
            'metrics' => $metrics,
            'funnel' => $this->funnel($metrics),
            'trend' => $this->trend($from, $to, $growthEvents, $marketingEvents),
            'channels' => $this->channels($growthEvents, $marketingEvents),
            'topContents' => $this->topContents(),
            'topScenarios' => $this->topScenarios($marketingEvents),
            'dataStatus' => [
                'internal' => true,
                'social' => Schema::hasTable('marketing_events') && $marketingEvents->isNotEmpty(),
                'message' => $marketingEvents->isEmpty() ? 'رویداد اجتماعی هنوز از منبع خارجی دریافت نشده است.' : 'رویدادهای اجتماعی در بازه‌ی انتخاب‌شده دریافت شده‌اند.',
            ],
        ];
    }

    private function resolveRange(array $filters): array
    {
        $to = !empty($filters['to']) ? Carbon::parse($filters['to'], self::TIMEZONE)->endOfDay() : now(self::TIMEZONE)->endOfDay();
        $from = !empty($filters['from']) ? Carbon::parse($filters['from'], self::TIMEZONE)->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        return [$from, $to->greaterThan($from) ? $to : $from->copy()->endOfDay()];
    }

    private function growthEvents(Carbon $from, Carbon $to): Collection
    {
        return Schema::hasTable('growth_events')
            ? GrowthEvent::whereBetween('occurred_at', [$from, $to])->get()
            : collect();
    }

    private function marketingEvents(Carbon $from, Carbon $to): Collection
    {
        return Schema::hasTable('marketing_events')
            ? MarketingEvent::whereBetween('occurred_at', [$from, $to])->get()
            : collect();
    }

    private function contentStats(Carbon $from, Carbon $to): array
    {
        if (!Schema::hasTable('growth_contents')) return ['views' => 0, 'engagements' => 0, 'comments' => 0];
        $query = GrowthContent::where(function ($query) use ($from, $to) {
            $query->whereBetween('published_at', [$from, $to])->orWhereNull('published_at');
        });
        return ['views' => (int) $query->sum('impressions'), 'engagements' => (int) $query->sum('engagements'), 'comments' => (int) $query->sum('comments')];
    }

    private function countMarketingEvents(Collection $events, array $types): int
    {
        return $events->whereIn('event_type', $types)->count();
    }

    private function funnel(array $metrics): array
    {
        $items = [
            ['label' => 'نمایش محتوا', 'value' => $metrics['views']],
            ['label' => 'تعامل', 'value' => $metrics['engagements']],
            ['label' => 'کلیک لینک', 'value' => $metrics['clicks']],
            ['label' => 'بازشدن مقصد', 'value' => $metrics['opens']],
            ['label' => 'ساخت موفق', 'value' => $metrics['builds']],
            ['label' => 'خرید موفق', 'value' => $metrics['purchases']],
        ];
        $maximum = max(1, ...array_column($items, 'value'));
        return collect($items)->map(fn (array $item) => $item + ['width' => $item['value'] > 0 ? max(4, round(($item['value'] / $maximum) * 100, 2)) : 0])->all();
    }

    private function trend(Carbon $from, Carbon $to, Collection $growthEvents, Collection $marketingEvents): array
    {
        $start = $to->copy()->subDays(13)->startOfDay()->greaterThan($from) ? $to->copy()->subDays(13)->startOfDay() : $from->copy()->startOfDay();
        $labels = [];
        $clicks = [];
        $opens = [];
        foreach (CarbonPeriod::create($start, '1 day', $to->copy()->startOfDay()) as $day) {
            $key = $day->toDateString();
            $labels[] = $day->format('m/d');
            $clicks[] = $growthEvents->filter(fn ($event) => $event->event_type === GrowthEvent::TYPE_CLICK && $event->occurred_at?->setTimezone(self::TIMEZONE)->toDateString() === $key)->count() + $marketingEvents->filter(fn ($event) => in_array($event->event_type, ['link.clicked', 'click'], true) && $event->occurred_at?->setTimezone(self::TIMEZONE)->toDateString() === $key)->count();
            $opens[] = $growthEvents->filter(fn ($event) => $event->event_type === GrowthEvent::TYPE_PAGE_OPEN && $event->occurred_at?->setTimezone(self::TIMEZONE)->toDateString() === $key)->count() + $marketingEvents->filter(fn ($event) => in_array($event->event_type, ['link.opened', 'page_open'], true) && $event->occurred_at?->setTimezone(self::TIMEZONE)->toDateString() === $key)->count();
        }
        return compact('labels', 'clicks', 'opens');
    }

    private function channels(Collection $growthEvents, Collection $marketingEvents): array
    {
        $channels = ['instagram' => 'اینستاگرام', 'telegram' => 'تلگرام', 'youtube' => 'یوتیوب', 'other' => 'سایر'];
        return collect($channels)->map(function (string $label, string $key) use ($growthEvents, $marketingEvents): array {
            $marketing = $marketingEvents->where('channel', $key);
            $growth = $key === 'instagram' ? $growthEvents : collect();
            $clicks = $growth->where('event_type', GrowthEvent::TYPE_CLICK)->count() + $marketing->whereIn('event_type', ['link.clicked', 'click'])->count();
            $opens = $growth->where('event_type', GrowthEvent::TYPE_PAGE_OPEN)->count() + $marketing->whereIn('event_type', ['link.opened', 'page_open'])->count();
            return ['key' => $key, 'label' => $label, 'clicks' => $clicks, 'opens' => $opens, 'success_rate' => $clicks > 0 ? round(($opens / $clicks) * 100, 2) : 0];
        })->values()->all();
    }

    private function topContents(): Collection
    {
        if (!Schema::hasTable('marketing_contents')) return collect();
        return MarketingContent::with('campaign')->latest('updated_at')->limit(8)->get()->map(fn (MarketingContent $content): array => ['title' => $content->title, 'channel' => $content->channel, 'status' => $content->status, 'campaign' => $content->campaign?->name ?? 'بدون کمپین', 'published_at' => $content->published_at?->format('Y/m/d H:i')]);
    }

    private function topScenarios(Collection $events): Collection
    {
        if (!Schema::hasTable('marketing_scenarios')) return collect();
        return MarketingScenario::latest()->limit(8)->get()->map(function (MarketingScenario $scenario) use ($events): array {
            $received = $events->where('marketing_scenario_id', $scenario->id)->whereIn('event_type', ['comment.received', 'dm.received', 'message.received'])->count();
            $converted = $events->where('marketing_scenario_id', $scenario->id)->whereIn('event_type', ['link.clicked', 'generation.completed', 'purchase.completed'])->count();
            return ['name' => $scenario->name, 'status' => $scenario->status, 'received' => $received, 'converted' => $converted, 'conversion_rate' => $received > 0 ? round(($converted / $received) * 100, 2) : 0];
        });
    }
}
