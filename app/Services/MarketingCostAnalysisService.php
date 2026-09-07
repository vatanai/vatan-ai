<?php

namespace App\Services;

use App\Models\FinanceExchangeRate;
use App\Models\MarketingCostEvent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Schema;

class MarketingCostAnalysisService
{
    private const TIMEZONE = 'Asia/Tehran';

    public function report(array $filters = []): array
    {
        [$from, $to] = $this->resolveRange($filters);
        $events = Schema::hasTable('marketing_cost_events')
            ? MarketingCostEvent::with(['campaign', 'content'])->whereBetween('incurred_at', [$from, $to])->latest('incurred_at')->get()
            : collect();
        $total = (int) $events->sum('cost_toman');
        $known = $events->where('status', '!=', 'needs_review')->sum('cost_toman');
        $rate = $this->latestRate();
        $providers = $events->groupBy('provider')->map(fn ($items, $provider): array => ['label' => $provider, 'events' => $items->count(), 'cost' => (int) $items->sum('cost_toman'), 'known' => $items->where('status', '!=', 'needs_review')->count()])->sortByDesc('cost')->values();
        $daily = collect(CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay()))->map(function (Carbon $day) use ($events): array { $key = $day->toDateString(); return ['label' => $day->format('m/d'), 'cost' => (int) $events->filter(fn ($event) => $event->incurred_at?->setTimezone(self::TIMEZONE)->toDateString() === $key)->sum('cost_toman')]; });

        return [
            'range' => ['from' => $from, 'to' => $to],
            'metrics' => ['total' => $total, 'known' => (int) $known, 'pending' => (int) $events->where('status', 'needs_review')->count(), 'events' => $events->count(), 'average' => $events->count() > 0 ? (int) round($total / $events->count()) : 0],
            'rate' => $rate,
            'providers' => $providers,
            'daily' => ['labels' => $daily->pluck('label')->all(), 'costs' => $daily->pluck('cost')->all()],
            'recentCosts' => $events->take(12),
        ];
    }

    private function resolveRange(array $filters): array
    {
        $to = !empty($filters['to']) ? Carbon::parse($filters['to'], self::TIMEZONE)->endOfDay() : now(self::TIMEZONE)->endOfDay();
        $from = !empty($filters['from']) ? Carbon::parse($filters['from'], self::TIMEZONE)->startOfDay() : $to->copy()->subDays(29)->startOfDay();
        return [$from, $to->greaterThan($from) ? $to : $from->copy()->endOfDay()];
    }

    private function latestRate(): array
    {
        if (!Schema::hasTable('finance_exchange_rates') || !Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) return ['value' => 0, 'source' => 'هنوز ثبت نشده', 'date' => null, 'is_live' => false];
        $latest = FinanceExchangeRate::query()->where('currency', 'USD')->where('rate_to_toman', '>', 0)->latest('rate_date')->first();
        return $latest ? ['value' => (float) $latest->rate_to_toman, 'source' => $latest->source ?: 'ثبت مالی وطن', 'date' => $latest->rate_date?->format('Y/m/d'), 'is_live' => !$latest->is_manual] : ['value' => 0, 'source' => 'هنوز ثبت نشده', 'date' => null, 'is_live' => false];
    }
}
