<?php

namespace App\Services;

use App\Models\ServiceCreditAccount;
use App\Models\ServiceCreditTransaction;
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
            $summary = collect((array) $response->json('summary', []));
            $total = (float) $summary->sum(fn ($row) => (float) ($row['cost_total'] ?? $row['cost'] ?? 0));
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
        $cost = $event['cost_total'] ?? null;
        if ($requestId === '' || !is_numeric($cost) || (float) $cost <= 0) return false;
        $reference = 'fal-billing-' . $requestId;
        if (ServiceCreditTransaction::query()->where('reference', $reference)->exists()) return false;
        ServiceCreditTransaction::create([
            'service_credit_account_id' => $account->id,
            'admin_id' => null,
            'type' => 'usage',
            'amount' => round((float) $cost, 6),
            'occurred_at' => $event['timestamp'] ?? now(),
            'reference' => $reference,
            'note' => 'مصرف واقعی از billing-events رسمی Fal.ai؛ endpoint: ' . ($event['endpoint_id'] ?? 'نامشخص'),
        ]);
        return true;
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
