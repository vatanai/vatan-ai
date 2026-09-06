<?php

namespace App\Services;

use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditTransaction;
use App\Models\AiProviderRequest;
use App\Models\GeneratedImage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class FalAiBillingService
{
    public function __construct(private AiProviderCredentials $credentials) {}

    public function pricing(string $endpointId): array
    {
        $endpointId = trim($endpointId);
        if ($endpointId === '' || !$this->hasKey()) return ['available' => false, 'source' => 'fal.ai pricing API'];

        return Cache::remember('fal.pricing.' . sha1($endpointId), now()->addMinutes(10), function () use ($endpointId) {
            try {
                $response = $this->client()->get($this->baseUrl() . '/v1/models/pricing', ['endpoint_id' => $endpointId]);
                if ($response->failed()) return ['available' => false, 'source' => 'fal.ai pricing API', 'http_status' => $response->status()];
                $price = collect((array) $response->json('prices', []))->first(fn ($item) => ($item['endpoint_id'] ?? '') === $endpointId);
                if (!is_array($price) || !is_numeric($price['unit_price'] ?? null)) {
                    return ['available' => false, 'source' => 'fal.ai pricing API'];
                }
                return [
                    'available' => true,
                    'unit_price' => (float) $price['unit_price'],
                    'unit' => (string) ($price['unit'] ?? 'unit'),
                    'currency' => (string) ($price['currency'] ?? 'USD'),
                    'source' => 'fal.ai pricing API',
                    'endpoint_id' => $endpointId,
                ];
            } catch (\Throwable) {
                return ['available' => false, 'source' => 'fal.ai pricing API'];
            }
        });
    }

    public function billingEvent(string $requestId): ?array
    {
        $requestId = trim($requestId);
        if ($requestId === '' || !$this->hasKey()) return null;
        try {
            $response = $this->client()->get($this->baseUrl() . '/v1/models/billing-events', [
                'request_id' => $requestId,
                'limit' => 1,
            ]);
            if ($response->failed()) return null;
            $event = collect((array) $response->json('billing_events', []))->first();
            return is_array($event) ? $event : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function costFromBillingEvent(?array $event): ?float
    {
        return is_array($event) ? $this->eventCostUsd($event) : null;
    }

    public function usage(string $start, string $end): array
    {
        if (!$this->hasKey()) return ['available' => false, 'source' => 'fal.ai usage API'];
        try {
            $response = $this->client()->get($this->baseUrl() . '/v1/models/usage', [
                'start' => $start,
                'end' => $end,
                'timezone' => config('app.timezone', 'UTC'),
                'timeframe' => 'day',
                'expand' => 'summary',
                'limit' => 1000,
            ]);
            if ($response->failed()) return ['available' => false, 'source' => 'fal.ai usage API', 'http_status' => $response->status()];
            $payload = (array) $response->json();
            // پاسخ رسمی فعلی در حالت time_series، هزینه را داخل results و
            // با cost_total/cost برمی‌گرداند. نسخهٔ قبلی فقط summary را
            // می‌خواند و در بعضی پاسخ‌ها quantity یا مقدار اشتباه را به‌جای
            // دلار مصرف‌شده وارد کارت اعتبار می‌کرد.
            $summary = (array) ($payload['summary'] ?? []);
            $timeSeries = (array) ($payload['time_series'] ?? []);
            $total = $summary !== []
                ? $this->sumUsageCosts($summary)
                : $this->sumUsageCosts($timeSeries);

            return ['available' => true, 'total_usage' => $total, 'source' => 'fal.ai usage API', 'raw' => $response->json()];
        } catch (\Throwable) {
            return ['available' => false, 'source' => 'fal.ai usage API'];
        }
    }

    public function syncBillingEvents(ServiceCreditAccount $account, ?string $start = null): int
    {
        if (!Schema::hasTable('service_credit_transactions')) return 0;
        if (!$this->hasKey()) return 0;
        $start ??= now()->subDays(90)->toIso8601String();
        try {
            $response = $this->client()->get($this->baseUrl() . '/v1/models/billing-events', [
                'start' => $start,
                'end' => now()->toIso8601String(),
                'limit' => 10000,
            ]);
            if ($response->failed()) return 0;
            $created = 0;
            foreach ((array) $response->json('billing_events', []) as $event) {
                $created += $this->recordBillingEvent($account, $event) ? 1 : 0;
            }
            return $created;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function recordBillingEvent(ServiceCreditAccount $account, array $event): bool
    {
        $requestId = trim((string) ($event['request_id'] ?? ''));
        $cost = $this->eventCostUsd($event);
        if ($requestId === '' || $cost === null || $cost <= 0) return false;
        $reference = 'fal-billing-' . $requestId;
        $created = false;
        if (!ServiceCreditTransaction::query()->where('reference', $reference)->exists()) {
            ServiceCreditTransaction::create([
                'service_credit_account_id' => $account->id,
                'admin_id' => null,
                'type' => 'usage',
                'amount' => round($cost, 6),
                'occurred_at' => $event['timestamp'] ?? now(),
                'reference' => $reference,
                'note' => 'مصرف واقعی از billing-events رسمی Fal.ai؛ endpoint: ' . ($event['endpoint_id'] ?? 'نامشخص'),
            ]);
            $created = true;
        }

        // billing-events ممکن است چند لحظه بعد از تکمیل پاسخ ظاهر شود. با
        // اتصال آن به درخواست داخلی، قیمت نهایی جایگزین برآورد اولیه می‌شود
        // و در صورت چند خروجی، بهای هر تصویر نیز دقیقاً تقسیم می‌گردد.
        $request = AiProviderRequest::query()
            ->where('provider', 'fal')
            ->where('external_request_id', $requestId)
            ->first();
        if ($request) {
            $actual = round($cost, 6);
            if ($request->actual_cost_usd === null || abs((float) $request->actual_cost_usd - $actual) > 0.0000005) {
                $raw = (array) $request->raw_response;
                $raw['billing_event'] = $event;
                $request->forceFill([
                    'actual_cost_usd' => $actual,
                    'raw_response' => $raw,
                ])->save();
            }

            $images = GeneratedImage::query()
                ->where('ai_provider_request_id', $request->id)
                ->get();
            if ($images->isNotEmpty()) {
                $perImage = round($actual / $images->count(), 6);
                foreach ($images as $image) {
                    if (abs((float) $image->cost - $perImage) > 0.0000005) {
                        $image->forceFill(['cost' => $perImage])->save();
                    }
                }
            }
        }

        return $created;
    }

    /**
     * جمع هزینهٔ نهایی پاسخ Usage بدون شمردن quantity یا cost_subtotal.
     * در پاسخ‌های مختلف Fal، ردیف‌ها ممکن است داخل results یا چند لایهٔ
     * گروه‌بندی باشند؛ این تابع هر دو شکل رسمی را پوشش می‌دهد.
     */
    private function sumUsageCosts(mixed $value): float
    {
        if (!is_array($value)) return 0.0;

        if (array_key_exists('cost_total', $value) || array_key_exists('cost', $value) || array_key_exists('cost_estimate_nano_usd', $value)) {
            return $this->eventCostUsd($value) ?? 0.0;
        }

        $total = 0.0;
        foreach ($value as $child) {
            $total += $this->sumUsageCosts($child);
        }

        return $total;
    }

    private function eventCostUsd(array $event): ?float
    {
        foreach (['cost_total', 'cost'] as $key) {
            if (is_numeric($event[$key] ?? null)) {
                return max(0.0, (float) $event[$key]);
            }
        }

        // Fal این مقدار را به nano USD می‌دهد؛ تبدیل نکردن آن باعث اختلاف
        // شدید بین مبلغ واقعی و مبلغ نمایش‌داده‌شده می‌شود.
        if (is_numeric($event['cost_estimate_nano_usd'] ?? null)) {
            return max(0.0, (float) $event['cost_estimate_nano_usd'] / 1_000_000_000);
        }

        return null;
    }

    private function client()
    {
        return Http::withHeaders(['Authorization' => 'Key ' . $this->credentials->for('fal')['api_key']])
            ->acceptJson()->connectTimeout(15)->timeout(15);
    }

    private function hasKey(): bool
    {
        return filled($this->credentials->for('fal')['api_key'] ?? null);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.fal.platform_base_url', 'https://api.fal.ai'), '/');
    }
}
