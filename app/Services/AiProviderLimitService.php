<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AiProviderLimitService
{
    public const DEFAULTS = [
        'enabled' => false,
        'window_minutes' => 60,
        // مقدار صفر یعنی سقف اعمال نمی‌شود. محدودسازی فقط باید با سقف صریح مدیر فعال شود.
        'max_requests' => 0,
        'max_cost_usd' => 0.0,
        'max_concurrent' => 0,
        'max_outputs' => 1,
        'max_failed_requests' => 0,
    ];

    /**
     * Fal یک سرویس پرداخت‌به‌ازای-درخواست است؛ تنظیم امن باید حتی برای نصب
     * تازه یا ردیف ناقص دیتابیس فعال باشد. مقادیر ذخیره‌شده‌ی مدیر همچنان
     * روی این پیش‌فرض‌ها اولویت دارند.
     */
    private const PROVIDER_DEFAULTS = [
        'fal' => [
            'enabled' => true,
            'window_minutes' => 60,
            'max_requests' => 30,
            'max_cost_usd' => 2.0,
            'max_concurrent' => 2,
            'max_outputs' => 1,
            'max_failed_requests' => 3,
        ],
    ];

    public function config(string $provider, ?AiProviderSetting $setting = null): array
    {
        $setting ??= AiProviderSetting::forProvider($provider);
        $saved = (array) ($setting?->settings ?? []);
        $defaults = array_replace(self::DEFAULTS, self::PROVIDER_DEFAULTS[strtolower($provider)] ?? []);
        $limits = array_replace($defaults, (array) ($saved['usage_limits'] ?? []));

        return [
            'enabled' => (bool) $limits['enabled'],
            'window_minutes' => max(1, min(10080, (int) $limits['window_minutes'])),
            'max_requests' => max(0, min(100000, (int) $limits['max_requests'])),
            'max_cost_usd' => max(0, min(100000, (float) $limits['max_cost_usd'])),
            'max_concurrent' => max(0, min(1000, (int) $limits['max_concurrent'])),
            'max_outputs' => max(1, min(10, (int) $limits['max_outputs'])),
            'max_failed_requests' => max(0, min(1000, (int) $limits['max_failed_requests'])),
        ];
    }

    public function summary(string $provider): array
    {
        $setting = AiProviderSetting::forProvider($provider);
        $limits = $this->config($provider, $setting);
        $windowStart = now()->subMinutes($limits['window_minutes']);

        $query = AiProviderRequest::query()
            ->where('provider', $provider)
            ->where('submitted_at', '>=', $windowStart);

        $requestCount = (clone $query)->count();
        $activeCount = (clone $query)->whereIn('status', ['reserved', 'queued', 'processing'])->count();
        $failedCount = (clone $query)->where('status', 'failed')->count();
        // برآورد درخواست ناموفق «هزینهٔ مصرف‌شده» نیست. فقط مبلغ واقعی ثبت‌شده
        // یا برآورد درخواست تکمیل‌شده در سقف هزینه محاسبه می‌شود.
        $spentUsd = (float) ((clone $query)->selectRaw(
            "COALESCE(SUM(CASE WHEN actual_cost_usd IS NOT NULL THEN actual_cost_usd WHEN status = 'completed' THEN estimated_cost_usd ELSE 0 END), 0) AS total"
        )->value('total') ?? 0);

        return $limits + [
            'window_start' => $windowStart,
            'request_count' => $requestCount,
            'active_count' => $activeCount,
            'failed_count' => $failedCount,
            'spent_usd' => round($spentUsd, 6),
            'remaining_requests' => $limits['max_requests'] > 0 ? max(0, $limits['max_requests'] - $requestCount) : null,
            'remaining_cost_usd' => $limits['max_cost_usd'] > 0 ? max(0, round($limits['max_cost_usd'] - $spentUsd, 6)) : null,
        ];
    }

    public function reserve(AiModel $model, ?float $estimatedCost, int $outputs = 1, ?int $orderId = null): AiProviderRequest
    {
        $provider = strtolower((string) $model->provider);
        $outputs = max(1, $outputs);
        $estimatedCost = $estimatedCost !== null ? max(0, round($estimatedCost, 6)) : null;

        return DB::transaction(function () use ($provider, $model, $estimatedCost, $outputs, $orderId) {
            // با قفل ردیف تنظیمات، دو درخواست هم‌زمان نمی‌توانند از یک سقف عبور کنند.
            $setting = AiProviderSetting::query()->firstOrCreate(['provider' => $provider]);
            $setting = AiProviderSetting::query()->whereKey($setting->id)->lockForUpdate()->first();
            $limits = $this->config($provider, $setting);

            if ($limits['enabled']) {
                $this->assertWithinLimits($provider, $limits, $estimatedCost, $outputs);
            }

            return AiProviderRequest::create([
                'provider' => $provider,
                'ai_model_id' => $model->id,
                'order_id' => $orderId,
                'external_request_id' => 'local-reservation:' . Str::uuid(),
                'status' => 'reserved',
                'estimated_cost_usd' => $estimatedCost,
                'submitted_at' => now(),
                'raw_response' => ['outputs' => $outputs],
            ]);
        });
    }

    private function assertWithinLimits(string $provider, array $limits, ?float $estimatedCost, int $outputs): void
    {
        $summary = $this->summary($provider);

        if ($limits['max_outputs'] > 0 && $outputs > $limits['max_outputs']) {
            throw new RuntimeException("سقف خروجی هر درخواست برای {$provider} برابر {$limits['max_outputs']} است.");
        }

        if ($limits['max_requests'] > 0 && $summary['request_count'] >= $limits['max_requests']) {
            throw new RuntimeException($this->limitMessage('تعداد درخواست', $limits['window_minutes']));
        }

        if ($limits['max_concurrent'] > 0 && $summary['active_count'] >= $limits['max_concurrent']) {
            throw new RuntimeException('سقف درخواست‌های هم‌زمان این provider پر است؛ پس از پایان اجرای فعلی دوباره تلاش کنید.');
        }

        if ($limits['max_failed_requests'] > 0 && $summary['failed_count'] >= $limits['max_failed_requests']) {
            throw new RuntimeException('مدار محافظ این provider به‌دلیل چند خروجی ناموفق فعال شد؛ ابتدا علت خطا را بررسی کنید و پس از پایان بازه دوباره تلاش کنید.');
        }

        if ($limits['max_cost_usd'] > 0 && $estimatedCost !== null && ($summary['spent_usd'] + $estimatedCost) > $limits['max_cost_usd']) {
            throw new RuntimeException(sprintf(
                'سقف هزینه این provider در بازه فعلی پر می‌شود؛ باقی‌مانده حدود $%s است.',
                number_format(max(0, $limits['max_cost_usd'] - $summary['spent_usd']), 4)
            ));
        }
    }

    private function limitMessage(string $kind, int $windowMinutes): string
    {
        $window = $windowMinutes >= 60 && $windowMinutes % 60 === 0
            ? ($windowMinutes / 60) . ' ساعت'
            : $windowMinutes . ' دقیقه';

        return "سقف {$kind} این provider در بازه {$window} پر شده است.";
    }
}
