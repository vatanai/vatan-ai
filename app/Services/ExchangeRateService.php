<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ExchangeRateService
{
    public function usdToIrr(): array
    {
        $cached = Cache::remember('finance.usd_irr', now()->addMinutes(10), function () {
            $sources = [
                ['url' => config('services.exchange_rate.url'), 'name' => 'نوبیتکس (USDT/ریال)', 'unit' => 'IRR'],
                ['url' => config('services.exchange_rate.backup_url'), 'name' => 'والکس (USDT/تومان)', 'unit' => 'IRT'],
            ];

            foreach ($sources as $source) {
                try {
                    if (!$source['url']) continue;
                    // نرخ ارز نباید مسیر رندر صفحات مدیریت را معطل کند؛ اگر DNS یا
                    // سرویس بیرونی در دسترس نبود، بلافاصله به نرخ پشتیبان می‌رویم.
                    $json = Http::acceptJson()->connectTimeout(1)->timeout(2)->get($source['url'])->throw()->json();
                    $latest = (float) (
                        data_get($json, 'lastTradePrice')
                        ?? data_get($json, 'result.symbols.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.stats.lastPrice')
                        ?? data_get($json, 'result.USDTTMN.lastPrice')
                        ?? 0
                    );
                    if ($latest > 0) {
                        if ($source['unit'] === 'IRT') $latest *= 10;
                        Cache::put('finance.usd_irr.last_positive', $latest, now()->addDays(3));
                        return ['rate' => $latest, 'source' => $source['name'], 'online' => true, 'at' => now()];
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $fallbackRate = $this->lastKnownRate();

            return [
                'rate' => $fallbackRate,
                'source' => $fallbackRate > 0 ? 'آخرین نرخ معتبر ذخیره‌شده' : 'نرخ پشتیبان',
                'online' => false,
                'at' => now(),
            ];
        });

        // کش‌های قدیمی ممکن است از نسخه‌های قبلی ساختار متفاوتی داشته باشند؛
        // خروجی عمومی این سرویس باید همیشه شکل و نوع ثابت داشته باشد.
        $rate = (float) data_get($cached, 'rate', 0);
        if ($rate <= 0) {
            $rate = $this->lastKnownRate();
        }

        return [
            'rate' => $rate,
            'source' => $rate > 0 && !data_get($cached, 'online', false)
                ? 'آخرین نرخ معتبر ذخیره‌شده'
                : (is_scalar(data_get($cached, 'source')) ? (string) data_get($cached, 'source') : 'نرخ پشتیبان'),
            'online' => (bool) data_get($cached, 'online', false),
            'at' => data_get($cached, 'at') ?: now(),
        ];
    }

    /**
     * وقتی سرویس‌های نرخ ارز موقتاً در دسترس نیستند، آخرین نرخ معتبر را نگه
     * می‌داریم تا قیمت تومانی آزمایش‌ها به صفر یا خط تیره تبدیل نشود.
     */
    private function lastKnownRate(): float
    {
        $configured = (float) config('services.exchange_rate.fallback', 0);
        if ($configured > 0) return $configured;

        $cached = (float) Cache::get('finance.usd_irr.last_positive', 0);
        if ($cached > 0) return $cached;

        try {
            if (Schema::hasTable('lab_experiments')) {
                $experimentRate = (float) DB::table('lab_experiments')
                    ->where('exchange_rate_irr', '>', 0)
                    ->orderByDesc('id')
                    ->value('exchange_rate_irr');
                if ($experimentRate > 0) return $experimentRate;
            }

            if (Schema::hasTable('lab_runs')) {
                $runRate = (float) DB::table('lab_runs')
                    ->where('exchange_rate_irr', '>', 0)
                    ->orderByDesc('id')
                    ->value('exchange_rate_irr');
                if ($runRate > 0) return $runRate;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return 0.0;
    }
}
