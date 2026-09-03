<?php

namespace App\Services\Providers;

use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Adapter صف ویدیوی رسمی OpenRouter (/api/v1/videos). */
class OpenRouterVideoProvider extends AbstractQueuedImageProvider
{
    public function provider(): string { return 'openrouter'; }

    protected function requestHeaders(): array
    {
        $key = $this->credentials->for('openrouter')['api_key'];
        return ['Authorization' => 'Bearer ' . $key, 'Accept' => 'application/json', 'Content-Type' => 'application/json'];
    }

    protected function submitRemote(AiModel $model, array $input, ?string $webhookUrl): array
    {
        $credentials = $this->credentials->for('openrouter');
        $base = rtrim($credentials['base_url'] ?: 'https://openrouter.ai/api/v1', '/');
        $body = ['model' => $model->externalModelId()] + $input;
        if ($webhookUrl) $body['callback_url'] = $webhookUrl;
        $response = Http::withHeaders($this->requestHeaders())->connectTimeout(15)->timeout(60)->post($base . '/videos', $body);
        if ($response->failed()) throw new RuntimeException('OpenRouter HTTP ' . $response->status() . ': ' . $response->body());
        return (array) $response->json();
    }

    protected function pollRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('openrouter');
        $base = rtrim($credentials['base_url'] ?: 'https://openrouter.ai/api/v1', '/');
        $response = Http::withHeaders($this->requestHeaders())->connectTimeout(10)->timeout(60)->get($base . '/videos/' . rawurlencode($requestId));
        if ($response->failed()) throw new RuntimeException('OpenRouter status HTTP ' . $response->status() . ': ' . $response->body());
        return (array) $response->json();
    }

    protected function cancelRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('openrouter');
        $base = rtrim($credentials['base_url'] ?: 'https://openrouter.ai/api/v1', '/');
        $response = Http::withHeaders($this->requestHeaders())->connectTimeout(10)->timeout(60)->post($base . '/videos/' . rawurlencode($requestId) . '/cancel');
        if ($response->failed() && $response->status() !== 404) throw new RuntimeException('OpenRouter cancel HTTP ' . $response->status() . ': ' . $response->body());
        return (array) $response->json();
    }

    protected function buildInput(AiModel $model, string $prompt, string $resolution, string $aspectRatio, int $count, array $extraPayload): array
    {
        $input = ['prompt' => $prompt, 'resolution' => $resolution, 'aspect_ratio' => $aspectRatio];
        if (isset($extraPayload['duration'])) $input['duration'] = (int) $extraPayload['duration'];
        if (array_key_exists('generate_audio', $extraPayload)) $input['generate_audio'] = (bool) $extraPayload['generate_audio'];
        if (isset($extraPayload['seed'])) $input['seed'] = (int) $extraPayload['seed'];
        foreach ((array) ($extraPayload['frame_images'] ?? []) as $frame) $input['frame_images'][] = $frame;
        foreach ((array) ($extraPayload['input_references'] ?? []) as $reference) $input['input_references'][] = $reference;
        return $input;
    }

    public function normalizeResponse(AiModel $model, array $payload): array
    {
        $status = strtolower((string) ($payload['status'] ?? 'pending'));
        $normalized = match ($status) {
            'pending', 'queued' => 'queued',
            'in_progress', 'processing' => 'processing',
            'completed', 'succeeded' => 'completed',
            'cancelled', 'canceled' => 'canceled',
            'failed', 'expired' => 'failed',
            default => 'processing',
        };
        $urls = [];
        foreach ((array) ($payload['unsigned_urls'] ?? $payload['output_urls'] ?? []) as $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) $urls[] = ['url' => $url, 'headers' => $this->requestHeaders()];
        }
        $error = $payload['error'] ?? null;
        return [
            'provider' => 'openrouter', 'external_request_id' => (string) ($payload['id'] ?? ''),
            'status' => $normalized, 'output_urls' => $urls,
            'estimated_cost_usd' => data_get($payload, 'usage.cost'), 'actual_cost_usd' => data_get($payload, 'usage.cost'),
            'error_code' => $error ? 'openrouter_video_failed' : null,
            'error_message' => is_string($error) ? $error : (is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE) : null),
            'provider_metadata' => ['response' => $payload],
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $data = (array) ($payload['data'] ?? $payload);
        return $this->normalizeResponse($this->modelForId((string) ($data['model'] ?? '')), $data);
    }

    public function estimateCost(AiModel $model, array $payload = []): ?float
    {
        $perSecond = (float) ($model->cost_per_generation_usd ?: 0);
        $duration = max(1, (int) ($payload['duration'] ?? 1));
        return $perSecond > 0 ? $perSecond * $duration : null;
    }
}
