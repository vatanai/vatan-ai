<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    public function usdToIrr(): array
    {
        return Cache::remember('finance.usd_irr', now()->addMinutes(10), function () {
            $sources = [
                ['url' => config('services.exchange_rate.url'), 'name' => 'نوبیتکس (USDT/ریال)', 'unit' => 'IRR'],
                ['url' => config('services.exchange_rate.backup_url'), 'name' => 'والکس (USDT/تومان)', 'unit' => 'IRT'],
            ];

            foreach ($sources as $source) {
                try {
                    if (!$source['url']) continue;
                    $json = Http::acceptJson()->timeout(8)->get($source['url'])->throw()->json();
                    $latest = (float) (
                        data_get($json, 'lastTradePrice')
                        ?? data_get($json, 'result.symbols.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.lastPrice')
                        ?? 0
                    );
                    if ($latest > 0) {
                        if ($source['unit'] === 'IRT') $latest *= 10;
                        return ['rate' => $latest, 'source' => $source['name'], 'online' => true, 'at' => now()];
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return [
                'rate' => (float) config('services.exchange_rate.fallback', 0),
                'source' => 'نرخ پشتیبان',
                'online' => false,
                'at' => now(),
            ];
        });
    }
}
