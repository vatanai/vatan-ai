<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\ServiceCreditAccount;
use App\Services\MeliPayamakService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ServiceCreditOverviewService
{
    public function __construct(
        private ExchangeRateService $exchangeRate,
        private AiProviderCredentials $credentials,
        private FalAiBillingService $falBilling,
        private MeliPayamakService $melipayamak,
    ) {}

    public function get(bool $dashboardOnly = false): array
    {
        $exchange = $this->exchangeRate->usdToIrr();
        if (!Schema::hasTable('service_credit_accounts')) {
            return ['accounts' => collect(), 'alerts' => collect(), 'exchange' => $exchange, 'totals' => $this->emptyTotals()];
        }

        $query = ServiceCreditAccount::query()->where('is_active', true);
        if ($dashboardOnly) $query->where('show_on_dashboard', true);
        $accounts = $query->orderBy('id')->get()->map(fn ($account) => $this->decorate($account, $exchange['rate']));

        return [
            'accounts' => $accounts,
            'alerts' => $this->alerts($accounts),
            'exchange' => $exchange,
            'totals' => $this->totals($accounts),
        ];
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
        } elseif ($account->sync_driver === 'fal') {
            $live = $this->falCredits();
            if (($live['online'] ?? false) && ($live['usage_source'] ?? null) === 'fal.ai usage API') {
                $this->falBilling->syncBillingEvents($account);
            }
        } elseif ($account->sync_driver === 'replicate') {
            $live = $this->replicateCredits();
        } elseif ($account->sync_driver === 'melipayamak') {
            $live = $this->melipayamakCredits();
        }

        $lastSnapshot = Schema::hasTable('service_credit_snapshots')
            ? $account->snapshots()->latest('captured_at')->first()
            : null;
        $hasLiveBalance = array_key_exists('balance', (array) $live) && $live['balance'] !== null;
        $balance = $hasLiveBalance
            ? (float) $live['balance']
            : ($lastSnapshot ? (float) $lastSnapshot->balance : (float) $account->manual_balance);
        $balanceIsLive = (bool) ($live['balance_is_live'] ?? false);
        $account->setAttribute('display_balance', $balance);
        $account->setAttribute('today_usage', $live['today_usage'] ?? $todayUsage);
        $account->setAttribute('month_usage', $live['month_usage'] ?? $monthUsage);
        $account->setAttribute('total_usage', $live['total_usage'] ?? (float) $account->transactions()->where('type', 'usage')->sum('amount'));
        $account->setAttribute('is_online', (bool) ($live['online'] ?? false));
        $account->setAttribute('balance_is_live', $balanceIsLive);
        $account->setAttribute('balance_is_stale', !$balanceIsLive && $lastSnapshot !== null);
        $account->setAttribute('status_label', $live['online'] ?? false
            ? ($balanceIsLive ? 'متصل و آنلاین' : ($lastSnapshot ? 'متصل؛ آخرین موجودی ثبت‌شده' : 'متصل؛ موجودی دستی'))
            : ($account->sync_driver === 'manual' ? 'ثبت دستی' : 'نیازمند بررسی اتصال'));
        $syncError = $live['error'] ?? null;
        $account->setAttribute('sync_error', is_scalar($syncError) || $syncError === null
            ? $syncError
            : (json_encode($syncError, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'خطای نامشخص'));
        $usageSource = $live['usage_source'] ?? ($account->sync_driver === 'fal' ? 'تراکنش‌های داخلی' : null);
        $account->setAttribute('usage_source', is_scalar($usageSource) || $usageSource === null
            ? $usageSource
            : (json_encode($usageSource, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'منبع نامشخص'));
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
        $timelinePoints = $this->timelinePoints($account->id);
        $hourlyUsage = $this->deriveHourlyUsage($timelinePoints, (float) $account->hourly_usage);
        $account->setAttribute('timeline_points', $timelinePoints);
        $account->setAttribute('hourly_usage', $hourlyUsage);
        $account->setAttribute('forecast_hours', $hourlyUsage > 0 && $balance > 0 ? round($balance / $hourlyUsage, 1) : null);

        $manualBalanceIsKnown = $account->sync_driver === 'manual' || (float) $account->manual_balance > 0;
        $balanceIsKnown = $balanceIsLive || $lastSnapshot !== null || $manualBalanceIsKnown;
        $alertLevel = 'normal';
        if ($account->alerts_enabled) {
            if (!$balanceIsKnown && $account->sync_driver !== 'manual') {
                $alertLevel = 'offline';
            } elseif ((float) $account->critical_balance_threshold > 0 && $balance <= (float) $account->critical_balance_threshold) {
                $alertLevel = 'critical';
            } elseif ((float) $account->low_balance_threshold > 0 && $balance <= (float) $account->low_balance_threshold) {
                $alertLevel = 'warning';
            }
        }
        $account->setAttribute('alert_level', $alertLevel);
        $account->setAttribute('is_low', in_array($alertLevel, ['critical', 'warning'], true));
        $account->setAttribute('alert_title', match ($alertLevel) {
            'critical' => 'وضعیت بحرانی اعتبار',
            'warning' => 'هشدار کاهش اعتبار',
            'offline' => 'وضعیت اعتبار نامشخص',
            default => 'وضعیت اعتبار عادی',
        });
        $account->setAttribute('alert_message', $this->alertMessage($account, $balance, $alertLevel));
        return $account;
    }

    private function melipayamakCredits(): array
    {
        return Cache::remember('finance.melipayamak_credits', now()->addMinutes(3), function () {
            try {
                $balance = $this->numericBalance($this->melipayamak->balance());
                if ($balance === null) {
                    return ['online' => true, 'balance_is_live' => false, 'error' => 'موجودی پنل پیامک از پاسخ سرویس قابل خواندن نیست'];
                }

                return [
                    'online' => true,
                    'balance_is_live' => true,
                    'balance' => $balance,
                    'usage_source' => 'ملی‌پیامک',
                ];
            } catch (\Throwable $e) {
                report($e);
                if (str_contains($e->getMessage(), 'صفحه کنسول')) {
                    return [
                        'online' => true,
                        'balance_is_live' => false,
                        'error' => 'اتصال ملی‌پیامک برقرار است؛ دسترسی دریافت موجودی در API یا پنل فعال نیست',
                    ];
                }
                return ['online' => false, 'balance_is_live' => false, 'error' => 'دریافت آنلاین موجودی پنل پیامک ناموفق بود'];
            }
        });
    }

    private function numericBalance(mixed $value): ?float
    {
        if (is_numeric($value)) return (float) $value;
        if (is_string($value)) {
            $normalized = str_replace([',', '٬', ' '], '', trim($value));
            return is_numeric($normalized) ? (float) $normalized : null;
        }
        if (is_array($value)) {
            foreach (['balance', 'credit', 'value', 'remaining', 'amount'] as $key) {
                if (array_key_exists($key, $value)) {
                    $numeric = $this->numericBalance($value[$key]);
                    if ($numeric !== null) return $numeric;
                }
            }
        }
        return null;
    }

    private function timelinePoints(int $accountId): array
    {
        if (!Schema::hasTable('service_credit_snapshots')) return [];

        $snapshots = ServiceCreditAccount::query()->find($accountId)?->snapshots()
            ->latest('captured_at')->limit(8)->get()->sortBy('captured_at')->values() ?? collect();
        if ($snapshots->isEmpty()) return [];

        $balances = $snapshots->pluck('balance')->map(fn ($balance) => (float) $balance);
        $minimum = $balances->min();
        $maximum = $balances->max();
        $range = max(0.000001, $maximum - $minimum);

        return $snapshots->map(function ($snapshot) use ($minimum, $range) {
            $balance = (float) $snapshot->balance;
            return [
                'time' => $snapshot->captured_at?->format('H:i'),
                'timestamp' => $snapshot->captured_at?->timestamp,
                'balance' => $balance,
                'height' => round(28 + (($balance - $minimum) / $range) * 72, 1),
            ];
        })->all();
    }

    private function deriveHourlyUsage(array $timelinePoints, float $reportedUsage): float
    {
        if ($reportedUsage > 0) return $reportedUsage;
        if (count($timelinePoints) < 2) return 0;

        $first = $timelinePoints[0];
        $last = $timelinePoints[count($timelinePoints) - 1];
        $firstAt = (int) ($first['timestamp'] ?? 0);
        $lastAt = (int) ($last['timestamp'] ?? 0);
        $hours = ($lastAt - $firstAt) / 3600;
        $consumed = (float) $first['balance'] - (float) $last['balance'];

        return $hours > 0 && $consumed > 0 ? round($consumed / $hours, 6) : 0;
    }

    private function alertMessage(ServiceCreditAccount $account, float $balance, string $level): string
    {
        $display = $account->currency === 'USD'
            ? '$' . number_format($balance, 2)
            : number_format($balance / 10) . ' تومان';

        return match ($level) {
            'critical' => "{$account->name} فقط {$display} موجودی دارد؛ اجرای کمپین یا تست قبل از شارژ متوقف شود.",
            'warning' => "موجودی {$account->name} به محدوده هشدار رسیده است: {$display} باقی مانده.",
            'offline' => "موجودی {$account->name} آنلاین قابل خواندن نیست؛ اتصال یا مقدار دستی آن را بررسی کنید.",
            default => '',
        };
    }

    private function alerts(Collection $accounts): Collection
    {
        $rank = ['critical' => 0, 'warning' => 1, 'offline' => 2, 'normal' => 3];
        return $accounts
            ->filter(fn ($account) => $account->alert_level !== 'normal')
            ->map(fn ($account) => [
                'account_id' => $account->id,
                'slug' => $account->slug,
                'name' => $account->name,
                'level' => $account->alert_level,
                'title' => $account->alert_title,
                'message' => $account->alert_message,
                'balance' => $account->display_balance,
                'currency' => $account->currency,
                'forecast_hours' => $account->forecast_hours,
            ])
            ->sortBy(fn ($alert) => $rank[$alert['level']] ?? 9)
            ->values();
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
                if ($response->status() === 429) {
                    return [
                        'online' => true,
                        'balance_is_live' => false,
                        'error' => 'اتصال برقرار است؛ API صورتحساب Fal.ai موقتاً rate-limited است',
                    ];
                }

                $response->throw();
                $response = $response->json();

                $balance = data_get($response, 'credits.current_balance');
                if ($balance === null) {
                    return ['online' => true, 'balance_is_live' => false, 'error' => 'موجودی اعتبار از پاسخ Fal.ai قابل خواندن نیست'];
                }

                $today = $this->falBilling->usage(now()->startOfDay()->toIso8601String(), now()->addDay()->startOfDay()->toIso8601String());
                $month = $this->falBilling->usage(now()->startOfMonth()->toIso8601String(), now()->addMonth()->startOfMonth()->toIso8601String());

                return [
                    'online' => true,
                    'balance_is_live' => true,
                    'balance' => (float) $balance,
                    'today_usage' => ($today['available'] ?? false) ? (float) ($today['total_usage'] ?? 0) : null,
                    'month_usage' => ($month['available'] ?? false) ? (float) ($month['total_usage'] ?? 0) : null,
                    'total_usage' => ($month['available'] ?? false) ? (float) ($month['total_usage'] ?? 0) : null,
                    'usage_source' => ($month['available'] ?? false) ? 'fal.ai usage API' : null,
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
