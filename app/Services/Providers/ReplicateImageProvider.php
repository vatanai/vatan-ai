<?php

namespace App\Services\Providers;

use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ReplicateImageProvider extends AbstractQueuedImageProvider
{
    public function provider(): string
    {
        return 'replicate';
    }

    protected function requestHeaders(): array
    {
        $key = $this->credentials->for('replicate')['api_key'];
        return [
            'Authorization' => 'Bearer ' . $key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function submitRemote(AiModel $model, array $input, ?string $webhookUrl): array
    {
        $credentials = $this->credentials->for('replicate');
        $base = rtrim($credentials['base_url'] ?: 'https://api.replicate.com/v1', '/');
        $version = trim((string) $model->external_version);
        $modelId = trim($model->externalModelId());
        $url = $base . '/predictions';
        $body = ['input' => $input];

        if ($version !== '') {
            $body['version'] = $version;
        } else {
            [$owner, $name] = array_pad(explode('/', $modelId, 2), 2, '');
            if ($owner === '' || $name === '') {
                throw new RuntimeException('شناسه مدل Replicate باید به شکل owner/name باشد.');
            }
            $url = $base . '/models/' . rawurlencode($owner) . '/' . rawurlencode($name) . '/predictions';
        }

        if ($webhookUrl) {
            $body['webhook'] = $webhookUrl;
            $body['webhook_events_filter'] = ['completed'];
        }

        $response = Http::withHeaders($this->requestHeaders())
            ->connectTimeout(15)
            ->timeout($credentials['timeout'])
            ->post($url, $body);

        if ($response->failed()) {
            throw new RuntimeException('Replicate HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    protected function pollRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('replicate');
        $base = rtrim($credentials['base_url'] ?: 'https://api.replicate.com/v1', '/');
        $response = Http::withHeaders($this->requestHeaders())
            ->timeout($credentials['timeout'])
            ->get($base . '/predictions/' . rawurlencode($requestId));

        if ($response->failed()) {
            throw new RuntimeException('Replicate status HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    protected function cancelRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('replicate');
        $base = rtrim($credentials['base_url'] ?: 'https://api.replicate.com/v1', '/');
        $response = Http::withHeaders($this->requestHeaders())
            ->post($base . '/predictions/' . rawurlencode($requestId) . '/cancel');

        if ($response->failed()) {
            throw new RuntimeException('Replicate cancel HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    public function normalizeResponse(AiModel $model, array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? ''));
        $normalizedStatus = match ($status) {
            'starting', 'queued' => 'queued',
            'processing', 'in_progress' => 'processing',
            'succeeded', 'successful', 'completed' => 'completed',
            'failed' => 'failed',
            'canceled', 'cancelled' => 'canceled',
            default => 'processing',
        };

        $items = [];
        $this->collectUrls($payload['output'] ?? null, $items);
        $metrics = (array) ($payload['metrics'] ?? []);

        return [
            'provider' => 'replicate',
            'external_request_id' => (string) ($payload['id'] ?? ''),
            'status' => $normalizedStatus,
            'output_urls' => $items,
            'estimated_cost_usd' => $this->estimateCost($model, (array) ($payload['input'] ?? [])),
            'actual_cost_usd' => null,
            'error_code' => null,
            'error_message' => is_string($payload['error'] ?? null) ? $payload['error'] : null,
            'provider_metadata' => ['prediction' => $payload, 'predict_time' => $metrics['predict_time'] ?? null, 'total_time' => $metrics['total_time'] ?? null],
        ];
    }

    protected function collectUrls(mixed $value, array &$items): void
    {
        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
            $items[] = ['url' => $value, 'requires_auth' => true];
            return;
        }
        if (!is_array($value)) return;
        foreach ($value as $child) {
            $this->collectUrls($child, $items);
        }
    }

    protected function legacyResponse(array $normalized): array
    {
        $response = parent::legacyResponse($normalized);
        foreach ($response['data'] as &$item) {
            $item['headers'] = ['Authorization' => 'Bearer ' . $this->credentials->for('replicate')['api_key']];
        }
        unset($item);
        return $response;
    }

    public function handleWebhook(array $payload): array
    {
        $requestId = (string) ($payload['id'] ?? '');
        if ($requestId === '') {
            throw new RuntimeException('Replicate webhook فاقد prediction id است.');
        }

        $request = \App\Models\AiProviderRequest::query()
            ->where('provider', 'replicate')
            ->where('external_request_id', $requestId)
            ->first();
        $model = $request?->aiModel ?: new AiModel([
            'provider' => 'replicate',
            'external_model_id' => (string) ($payload['model'] ?? 'unknown'),
            'openrouter_model_id' => (string) ($payload['model'] ?? 'unknown'),
            'external_version' => (string) ($payload['version'] ?? ''),
        ]);
        $normalized = $this->normalizeResponse($model, $payload);
        $this->persistNormalizedRequest($model, $normalized, $requestId, true);
        return $normalized;
    }
}
