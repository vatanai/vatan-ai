<?php

namespace App\Services;

use App\Models\FinanceExchangeRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ExchangeRateService
{
    public function usdToIrr(): array
    {
        if (! Schema::hasTable('finance_exchange_rates')) {
            return $this->fallbackRate();
        }

        $query = FinanceExchangeRate::query()->where('currency', 'USD');
        if (Schema::hasColumn('finance_exchange_rates', 'captured_at')) {
            $localDate = now('Asia/Tehran')->toDateString();
            $query->where(function ($builder): void {
                $builder->whereNull('captured_at')->orWhere('captured_at', '<=', now('UTC'));
            })->whereDate('rate_date', '<=', $localDate)
                ->orderByRaw('captured_at IS NULL')->latest('captured_at')->latest('created_at')->latest('rate_date');
        } else {
            $query->latest('rate_date');
        }

        $snapshot = $query->first();
        if (!$snapshot) {
            return $this->fallbackRate();
        }

        return [
            'rate' => (float) ($snapshot->rate_to_irr ?? 0),
            'source' => (string) ($snapshot->source ?: 'نرخ زمان‌بندی‌شده'),
            'online' => true,
            'scheduled' => true,
            'at' => $snapshot->captured_at ?: $snapshot->created_at,
        ];
    }

    /**
     * این متد فقط از فرمان زمان‌بندی‌شده فراخوانی می‌شود؛ صفحات وب نباید آن را صدا بزنند.
     */
    public function fetchLive(): array
    {
        $sources = [
            ['url' => config('services.exchange_rate.wallex_url'), 'name' => 'والکس (USDT/تومان)', 'unit' => 'IRT'],
            ['url' => config('services.exchange_rate.nobitex_url'), 'name' => 'نوبیتکس (USDT/ریال)', 'unit' => 'IRR'],
        ];

        foreach ($sources as $source) {
            try {
                if (!$source['url']) continue;

                $json = Http::acceptJson()
                    ->connectTimeout(2)
                    ->timeout(4)
                    ->get($source['url'])
                    ->throw()
                    ->json();
                $latest = $source['unit'] === 'IRT'
                    ? (float) (
                        data_get($json, 'result.symbols.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.lastPrice')
                        ?? 0
                    )
                    : (float) (data_get($json, 'lastTradePrice') ?? 0);

                if ($latest > 0) {
                    if ($source['unit'] === 'IRT') $latest *= 10;

                    return [
                        'rate' => $latest,
                        'source' => $source['name'],
                        'online' => true,
                        'at' => now(),
                    ];
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $this->fallbackRate();
    }

    private function fallbackRate(): array
    {
        return [
            'rate' => (float) config('services.exchange_rate.fallback', 0),
            'source' => 'نرخ پشتیبان',
            'online' => false,
            'scheduled' => false,
            'at' => now(),
        ];
    }
}
