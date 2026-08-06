<?php

namespace App\Services\Providers;

use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FalImageProvider extends AbstractQueuedImageProvider
{
    public function provider(): string
    {
        return 'fal';
    }

    protected function requestHeaders(): array
    {
        $key = $this->credentials->for('fal')['api_key'];
        return [
            'Authorization' => 'Key ' . $key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function submitRemote(AiModel $model, array $input, ?string $webhookUrl): array
    {
        $credentials = $this->credentials->for('fal');
        $url = rtrim($credentials['base_url'] ?: 'https://queue.fal.run', '/') . '/' . ltrim($model->externalModelId(), '/');
        if ($webhookUrl) {
            $url .= '?fal_webhook=' . rawurlencode($webhookUrl);
        }

        $response = Http::withHeaders($this->requestHeaders())
            ->connectTimeout(15)
            ->timeout($credentials['timeout'])
            ->post($url, $input);

        if ($response->failed()) {
            throw new RuntimeException('Fal.ai HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    protected function pollRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('fal');
        $base = rtrim($credentials['base_url'] ?: 'https://queue.fal.run', '/');
        $response = Http::withHeaders($this->requestHeaders())
            ->timeout($credentials['timeout'])
            ->get($base . '/' . ltrim($model->externalModelId(), '/') . '/requests/' . rawurlencode($requestId) . '/status');

        if ($response->failed()) {
            throw new RuntimeException('Fal.ai status HTTP ' . $response->status() . ': ' . $response->body());
        }

        $status = (array) $response->json();
        if (($status['status'] ?? null) === 'COMPLETED' && !empty($status['response_url'])) {
            $result = Http::withHeaders($this->requestHeaders())
                ->timeout($credentials['timeout'])
                ->get($base . '/' . ltrim($model->externalModelId(), '/') . '/requests/' . rawurlencode($requestId));
            if ($result->successful()) {
                $status['result'] = $result->json();
            }
        }

        return $status;
    }

    protected function cancelRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('fal');
        $base = rtrim($credentials['base_url'] ?: 'https://queue.fal.run', '/');
        $response = Http::withHeaders($this->requestHeaders())
            ->timeout($credentials['timeout'])
            ->put($base . '/' . ltrim($model->externalModelId(), '/') . '/requests/' . rawurlencode($requestId) . '/cancel');

        if ($response->failed() && $response->status() !== 400) {
            throw new RuntimeException('Fal.ai cancel HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    public function normalizeResponse(AiModel $model, array $payload): array
    {
        $status = strtoupper((string) ($payload['status'] ?? ''));
        $normalizedStatus = match ($status) {
            'IN_QUEUE' => 'queued',
            'IN_PROGRESS' => 'processing',
            'COMPLETED', 'OK' => !empty(data_get($payload, 'error')) ? 'failed' : 'completed',
            'CANCELED', 'CANCELLED', 'CANCELLATION_REQUESTED' => 'canceled',
            'ERROR' => 'failed',
            default => 'processing',
        };

        $result = (array) ($payload['result'] ?? ($payload['payload'] ?? $payload));
        $items = [];
        foreach (['images', 'image', 'output'] as $key) {
            $value = $result[$key] ?? null;
            if (!$value) continue;
            $values = is_array($value) && array_is_list($value) ? $value : [$value];
            foreach ($values as $item) {
                $url = is_string($item) ? $item : ($item['url'] ?? null);
                if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $items[] = ['url' => $url, 'headers' => []];
                }
            }
        }

        $requestId = (string) ($payload['request_id'] ?? $payload['gateway_request_id'] ?? '');
        return [
            'provider' => 'fal',
            'external_request_id' => $requestId,
            'status' => $normalizedStatus,
            'output_urls' => $items,
            'estimated_cost_usd' => $this->estimateCost($model),
            'actual_cost_usd' => null,
            'error_code' => $payload['error_type'] ?? null,
            'error_message' => $payload['error'] ?? null,
            'provider_metadata' => $payload,
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $requestId = (string) ($payload['request_id'] ?? '');
        if ($requestId === '') {
            throw new RuntimeException('Fal.ai webhook فاقد request_id است.');
        }

        $request = \App\Models\AiProviderRequest::query()
            ->where('provider', 'fal')
            ->where('external_request_id', $requestId)
            ->first();
        $model = $request?->aiModel ?: new AiModel([
            'provider' => 'fal',
            'external_model_id' => 'unknown',
            'openrouter_model_id' => 'unknown',
        ]);
        $normalized = $this->normalizeResponse($model, $payload);
        $this->persistNormalizedRequest($model, $normalized, $requestId, true);
        return $normalized;
    }
}
