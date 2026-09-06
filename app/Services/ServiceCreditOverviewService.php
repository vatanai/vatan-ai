<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\ServiceCreditAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ServiceCreditOverviewService
{
    public function __construct(
        private ExchangeRateService $exchangeRate,
        private AiProviderCredentials $credentials,
    ) {}

    public function get(bool $dashboardOnly = false): array
    {
        $exchange = $this->exchangeRate->usdToIrr();
        if (!Schema::hasTable('service_credit_accounts')) {
            return ['accounts' => collect(), 'exchange' => $exchange, 'totals' => $this->emptyTotals()];
        }

        $query = ServiceCreditAccount::query()->where('is_active', true);
        if ($dashboardOnly) $query->where('show_on_dashboard', true);
        $accounts = $query->orderBy('id')->get()->map(fn ($account) => $this->decorate($account, $exchange['rate']));

        return ['accounts' => $accounts, 'exchange' => $exchange, 'totals' => $this->totals($accounts)];
    }

    private function decorate(ServiceCreditAccount $account, float $rate): ServiceCreditAccount
    {
        $todayUsage = (float) $account->transactions()->where('type', 'usage')
            ->whereDate('occurred_at', today())->sum('amount');
        $monthUsage = (float) $account->transactions()->where('type', 'usage')
            ->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');
        $live = null;

        if ($account->sync_driver === 'openrouter') {
            $live = $this->openRouterCredits();
            if (isset($live['today_usage'])) {
                $todayUsage = (float) $live['today_usage'];
                $monthUsage = (float) ($live['month_usage'] ?? 0);
            } elseif (Schema::hasTable('generated_images')) {
                $todayUsage = (float) GeneratedImage::whereDate('created_at', today())->sum('cost');
                $monthUsage = (float) GeneratedImage::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('cost');
            }
        } elseif ($account->sync_driver === 'liara') {
            $live = $this->liaraCredits();
            $todayUsage = (float) ($live['today_usage'] ?? 0);
            $monthUsage = (float) ($live['month_usage'] ?? 0);
        } elseif ($account->sync_driver === 'fal') {
            $live = $this->falCredits();
        } elseif ($account->sync_driver === 'replicate') {
            $live = $this->replicateCredits();
        }

        $balance = array_key_exists('balance', (array) $live) && $live['balance'] !== null
            ? (float) $live['balance']
            : (float) $account->manual_balance;
        $balanceIsLive = (bool) ($live['balance_is_live'] ?? false);
        $account->setAttribute('display_balance', $balance);
        $account->setAttribute('today_usage', $todayUsage);
        $account->setAttribute('month_usage', $monthUsage);
        $account->setAttribute('total_usage', $live['total_usage'] ?? (float) $account->transactions()->where('type', 'usage')->sum('amount'));
        $account->setAttribute('is_online', (bool) ($live['online'] ?? false));
        $account->setAttribute('balance_is_live', $balanceIsLive);
        $account->setAttribute('status_label', $live['online'] ?? false
            ? ($balanceIsLive ? 'متصل و آنلاین' : 'متصل؛ موجودی دستی')
            : 'ثبت دستی');
        $account->setAttribute('sync_error', $live['error'] ?? null);
        $account->setAttribute('usage_is_estimate', (bool) ($live['usage_is_estimate'] ?? false));
        $account->setAttribute('hourly_usage', (float) ($live['hourly_usage'] ?? 0));
        $account->setAttribute('balance_irr', $account->currency === 'USD' ? $balance * $rate : $balance);
        $account->setAttribute('today_usage_irr', $account->currency === 'USD' ? $todayUsage * $rate : $todayUsage);
        $account->setAttribute('month_usage_irr', $account->currency === 'USD' ? $monthUsage * $rate : $monthUsage);
        $account->setAttribute('balance_usd', $account->currency === 'USD'
            ? $balance
            : ($rate > 0 ? $balance / $rate : null));
        $account->setAttribute('balance_toman', ($account->currency === 'USD' ? $balance * $rate : $balance) / 10);
        $account->setAttribute('today_usage_toman', $account->today_usage_irr / 10);
        $account->setAttribute('month_usage_toman', $account->month_usage_irr / 10);
        $account->setAttribute('is_low', (float) $account->low_balance_threshold > 0 && $balance <= (float) $account->low_balance_threshold);
        return $account;
    }

    private function openRouterCredits(): array
    {
        return Cache::remember('finance.openrouter_credits', now()->addMinutes(3), function () {
            $key = config('services.openrouter.management_key') ?: config('services.openrouter.api_key');
            if (!$key) return ['online' => false, 'error' => 'کلید مدیریتی OpenRouter تنظیم نشده است'];
            $headers = [];
            if (config('services.openrouter.gateway_secret')) {
                $headers['X-Vatan-Gateway-Key'] = config('services.openrouter.gateway_secret');
            }
            $configured = array_filter(array_map(
                'trim',
                explode(',', (string) config('services.openrouter.base_urls'))
            ));
            $endpoints = array_values(array_unique(array_merge(
                $configured,
                [config('services.openrouter.base_url', 'https://openrouter.ai/api/v1')]
            )));

            foreach ($endpoints as $endpoint) {
                try {
                    $client = Http::withToken($key)->withHeaders($headers)->acceptJson()->timeout(10);
                    $base = rtrim($endpoint, '/');
                    $response = $client->get($base . '/credits')->throw()->json();
                    $purchased = (float) data_get($response, 'data.total_credits', 0);
                    $used = (float) data_get($response, 'data.total_usage', 0);
                    $keyResponse = [];
                    try {
                        $keyResponse = $client->get($base . '/key')->throw()->json();
                    } catch (\Throwable $e) {
                        report($e);
                    }
                    return [
                        'online' => true,
                        'balance_is_live' => true,
                        'balance' => max(0, $purchased - $used),
                        'total_usage' => $used,
                        'today_usage' => (float) data_get($keyResponse, 'data.usage_daily', 0)
                            + (float) data_get($keyResponse, 'data.byok_usage_daily', 0),
                        'month_usage' => (float) data_get($keyResponse, 'data.usage_monthly', 0)
                            + (float) data_get($keyResponse, 'data.byok_usage_monthly', 0),
                    ];
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return ['online' => false, 'error' => 'دریافت آنلاین موجودی ناموفق بود'];
        });
    }

    private function liaraCredits(): array
    {
        return Cache::remember('finance.liara_credits', now()->addMinutes(3), function () {
            $token = config('services.liara.account_api_token');
            if (!$token) return ['online' => false, 'error' => 'توکن API حساب Liara تنظیم نشده است'];

            try {
                $client = Http::withToken($token)->acceptJson()->timeout(12);
                $base = rtrim(config('services.liara.account_api_url'), '/');
                $billing = $client->get($base . '/v1/billing')->throw()->json();
                $usage = $client->get($base . '/v1/usage-report')->throw()->json();

                // API حساب Liara اعداد مالی را به تومان برمی‌گرداند؛ واحد داخلی این بخش ریال است.
                $balanceIrr = (float) data_get($billing, 'user.balance', 0) * 10;
                $hourlyIrr = (float) data_get($usage, 'totalHourlyPrice', 0) * 10;
                $monthlyIrr = (float) data_get($usage, 'totalMonthlyPrice', 0) * 10;
                $tehranNow = now('Asia/Tehran');
                $elapsedToday = ($tehranNow->timestamp - $tehranNow->copy()->startOfDay()->timestamp) / 3600;

                return [
                    'online' => true,
                    'balance_is_live' => true,
                    'balance' => $balanceIrr,
                    'today_usage' => $hourlyIrr * $elapsedToday,
                    'month_usage' => $monthlyIrr,
                    'hourly_usage' => $hourlyIrr,
                    'usage_is_estimate' => true,
                ];
            } catch (\Throwable $e) {
                report($e);
                return ['online' => false, 'error' => 'دریافت آنلاین اطلاعات Liara ناموفق بود'];
            }
        });
    }

    private function falCredits(): array
    {
        return Cache::remember('finance.fal_credits', now()->addMinutes(3), function () {
            $key = $this->credentials->for('fal')['api_key'];
            if (!$key) return ['online' => false, 'error' => 'کلید Fal.ai تنظیم نشده است'];

            try {
                $response = Http::withHeaders(['Authorization' => 'Key ' . $key])
                    ->acceptJson()
                    ->timeout(12)
                    ->get(rtrim((string) config('services.fal.platform_base_url', 'https://api.fal.ai'), '/') . '/v1/account/billing', [
                        'expand' => 'credits',
                    ]);

                if ($response->status() === 403) {
                    return [
                        'online' => true,
                        'balance_is_live' => false,
                        'error' => 'اتصال برقرار است؛ کلید Fal.ai مجوز خواندن صورتحساب ندارد',
                    ];
                }

                $response->throw();
                $response = $response->json();

                $balance = data_get($response, 'credits.current_balance');
                if ($balance === null) {
                    return ['online' => true, 'balance_is_live' => false, 'error' => 'موجودی اعتبار از پاسخ Fal.ai قابل خواندن نیست'];
                }

                return [
                    'online' => true,
                    'balance_is_live' => true,
                    'balance' => (float) $balance,
                ];
            } catch (\Throwable $e) {
                report($e);
                return ['online' => false, 'error' => 'دریافت آنلاین موجودی Fal.ai ناموفق بود'];
            }
        });
    }

    private function replicateCredits(): array
    {
        return Cache::remember('finance.replicate_credits', now()->addMinutes(3), function () {
            $credentials = $this->credentials->for('replicate');
            if (!$credentials['api_key']) return ['online' => false, 'error' => 'توکن Replicate تنظیم نشده است'];

            try {
                Http::withToken($credentials['api_key'])
                    ->acceptJson()
                    ->timeout(12)
                    ->get(rtrim($credentials['base_url'], '/') . '/account')
                    ->throw();

                return [
                    'online' => true,
                    'balance_is_live' => false,
                    'error' => 'اتصال برقرار است؛ API رسمی Replicate موجودی مالی را اعلام نمی‌کند',
                ];
            } catch (\Throwable $e) {
                report($e);
                return ['online' => false, 'error' => 'دریافت اطلاعات حساب Replicate ناموفق بود'];
            }
        });
    }

    private function totals(Collection $accounts): array
    {
        return [
            'balance_irr' => $accounts->sum('balance_irr'),
            'balance_usd' => $accounts->sum(fn ($account) => (float) ($account->balance_usd ?? 0)),
            'balance_toman' => $accounts->sum('balance_toman'),
            'today_irr' => $accounts->sum('today_usage_irr'),
            'month_irr' => $accounts->sum('month_usage_irr'),
            'low_count' => $accounts->where('is_low', true)->count(),
        ];
    }

    private function emptyTotals(): array
    {
        return [
            'balance_irr' => 0,
            'balance_usd' => 0,
            'balance_toman' => 0,
            'today_irr' => 0,
            'month_irr' => 0,
            'low_count' => 0,
        ];
    }
}
