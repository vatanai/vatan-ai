<?php

namespace App\Services\Finance;

use App\Models\FinanceExchangeRate;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

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

        $moment = ($at ?: Carbon::now('Asia/Tehran'))->copy()->utc();
        $query = FinanceExchangeRate::query()->where('currency', strtoupper($currency));

        if (Schema::hasColumn('finance_exchange_rates', 'captured_at')) {
            $query->where(function ($builder) use ($moment): void {
                $builder->whereNull('captured_at')->orWhere('captured_at', '<=', $moment);
            })->whereDate('rate_date', '<=', $moment->copy()->timezone('Asia/Tehran')->toDateString())
                ->orderByRaw('captured_at IS NULL')->latest('captured_at')->latest('created_at')->latest('rate_date');
        } else {
            $query->whereDate('rate_date', '<=', $moment->copy()->timezone('Asia/Tehran')->toDateString())
                ->latest('rate_date');
        }

        return $query->first();
    }

    public function refresh(string $currency = 'USD', string $slot = 'manual'): FinanceExchangeRate
    {
        if (! Schema::hasTable('finance_exchange_rates')) {
            throw new RuntimeException('جدول snapshot نرخ ارز هنوز ساخته نشده است.');
        }

        $live = $this->exchangeRateService->fetchLive();
        $rate = (float) ($live['rate'] ?? 0);
        if ($rate <= 0) {
            throw new RuntimeException('از هیچ‌یک از منابع نرخ ارز، قیمت معتبر دریافت نشد.');
        }

        $slot = in_array($slot, ['morning', 'evening', 'manual', 'legacy'], true) ? $slot : 'manual';
        $localNow = Carbon::now('Asia/Tehran');
        $values = [
            'rate_to_irr' => $rate,
            'source' => (string) ($live['source'] ?? 'سرویس نرخ ارز'),
            'is_manual' => false,
        ];
        if (Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
            $values['rate_to_toman'] = $rate / 10;
        }
        if (Schema::hasColumn('finance_exchange_rates', 'captured_at')) {
            $values['captured_at'] = Carbon::now('UTC');
        }

        return FinanceExchangeRate::query()->updateOrCreate(
            [
                'currency' => strtoupper($currency),
                'rate_date' => $localNow->toDateString(),
                'capture_slot' => $slot,
            ],
            $values,
        );
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
