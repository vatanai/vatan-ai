<?php

namespace App\Services\Finance;

use App\Models\FinanceExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class FinanceExchangeRateSnapshotService
{
    public function __construct(private readonly ExchangeRateService $exchangeRateService)
    {
    }

    public function current(string $currency = 'USD', ?CarbonInterface $at = null): ?FinanceExchangeRate
    {
        if (! Schema::hasTable('finance_exchange_rates')) {
            return null;
        }

        $date = ($at ?: now())->toDateString();
        $existing = FinanceExchangeRate::query()
            ->where('currency', strtoupper($currency))
            ->whereDate('rate_date', $date)
            ->first();

        if ($existing) {
            return $existing;
        }

        $live = $this->exchangeRateService->usdToIrr();
        $rate = (float) ($live['rate'] ?? 0);

        if ($rate <= 0) {
            return FinanceExchangeRate::query()
                ->where('currency', strtoupper($currency))
                ->whereDate('rate_date', '<=', $date)
                ->latest('rate_date')
                ->first();
        }

        $values = [
            'currency' => strtoupper($currency),
            'rate_date' => $date,
            'rate_to_irr' => $rate,
            'source' => (string) ($live['source'] ?? 'سرویس نرخ ارز'),
            'is_manual' => false,
        ];
        if (Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
            $values['rate_to_toman'] = $rate / 10;
        }

        return FinanceExchangeRate::query()->create($values);
    }

    public function rate(string $currency = 'USD', ?CarbonInterface $at = null): float
    {
        if (strtoupper($currency) === 'IRR') {
            return 1;
        }

        return (float) ($this->current($currency, $at)?->rate_to_irr ?? 0);
    }

    public function rateToman(string $currency = 'USD', ?CarbonInterface $at = null): float
    {
        if (in_array(strtoupper($currency), ['IRT', 'TOMAN'], true)) {
            return 1;
        }

        $snapshot = $this->current($currency, $at);
        if ($snapshot && Schema::hasColumn('finance_exchange_rates', 'rate_to_toman') && (float) $snapshot->rate_to_toman > 0) {
            return (float) $snapshot->rate_to_toman;
        }

        return (float) ($snapshot?->rate_to_irr ?? 0) / 10;
    }
}
