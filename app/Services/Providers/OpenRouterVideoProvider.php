<?php

namespace App\Services\Providers;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $idempotencyKey = trim((string) ($input['_idempotency_key'] ?? ''));
        unset($input['_idempotency_key']);
        $body = ['model' => $model->externalModelId()] + $input;
        if ($webhookUrl && filled($credentials['webhook_secret'] ?? null) && str_starts_with($webhookUrl, 'https://')) {
            $body['callback_url'] = $webhookUrl;
        }
        $headers = $this->requestHeaders();
        if ($idempotencyKey !== '') $headers['X-OpenRouter-Idempotency-Key'] = $idempotencyKey;
        $response = Http::withHeaders($headers)->connectTimeout(15)->timeout(60)->post($base . '/videos', $body);
        if ($response->failed()) {
            throw new RuntimeException($this->providerFailureMessage($response, 'ارسال درخواست ویدیو'));
        }
        return (array) $response->json();
    }

    protected function pollRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('openrouter');
        $base = rtrim($credentials['base_url'] ?: 'https://openrouter.ai/api/v1', '/');
        $response = Http::withHeaders($this->requestHeaders())->connectTimeout(10)->timeout(60)->get($base . '/videos/' . rawurlencode($requestId));
        if ($response->failed()) {
            throw new RuntimeException($this->providerFailureMessage($response, 'دریافت وضعیت ویدیو'));
        }
        return (array) $response->json();
    }

    protected function cancelRemote(AiModel $model, string $requestId): array
    {
        // قرارداد رسمی فعلی OpenRouter برای /videos مسیر لغو مستقیمی اعلام
        // نکرده است. لغو در لایهٔ برنامه ثبت می‌شود و polling برای دریافت
        // هزینهٔ واقعی تا وضعیت نهایی سرویس ادامه پیدا می‌کند.
        return ['id' => $requestId, 'status' => 'processing'];
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
            $candidate = is_array($url) ? ($url['url'] ?? null) : $url;
            if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_URL)) {
                $urls[] = ['url' => $candidate];
            }
        }
        $requestId = (string) ($payload['id'] ?? $payload['job_id'] ?? '');
        if ($normalized === 'completed' && $urls === [] && $requestId !== '') {
            $base = rtrim($this->credentials->for('openrouter')['base_url'] ?: 'https://openrouter.ai/api/v1', '/');
            $urls[] = ['url' => $base . '/videos/' . rawurlencode($requestId) . '/content?index=0'];
        }
        $error = $payload['error'] ?? null;
        $errorMessage = is_string($error)
            ? $error
            : (is_array($error) ? (string) ($error['message'] ?? json_encode($error, JSON_UNESCAPED_UNICODE)) : null);
        return [
            'provider' => 'openrouter', 'external_request_id' => $requestId,
            'status' => $normalized, 'output_urls' => $urls,
            'estimated_cost_usd' => data_get($payload, 'usage.cost'),
            'actual_cost_usd' => array_key_exists('cost', (array) ($payload['usage'] ?? [])) ? data_get($payload, 'usage.cost') : null,
            'error_code' => $error ? (string) data_get($payload, 'error.code', 'openrouter_video_failed') : null,
            'error_message' => $errorMessage,
            'provider_metadata' => ['response' => $payload],
        ];
    }

    private function providerFailureMessage(Response $response, string $operation): string
    {
        $payload = (array) $response->json();
        $error = $payload['error'] ?? null;
        $code = is_array($error) ? ($error['code'] ?? null) : null;
        $message = is_array($error) ? ($error['message'] ?? null) : (is_string($error) ? $error : null);
        $message = trim((string) ($message ?: $response->body()));
        $safe = Str::limit(preg_replace('/\s+/', ' ', $message) ?: 'پاسخ نامعتبر از OpenRouter', 800, '');

        Log::warning('OpenRouter video request rejected', [
            'operation' => $operation,
            'http_status' => $response->status(),
            'provider_error_code' => $code,
            'provider_error_message' => $safe,
        ]);

        return sprintf('OpenRouter HTTP %d%s: %s', $response->status(), $code ? " [{$code}]" : '', $safe);
    }

    public function handleWebhook(array $payload): array
    {
        $data = (array) ($payload['data'] ?? $payload);
        $modelId = (string) ($data['model'] ?? '');
        $model = $modelId !== ''
            ? $this->modelForId($modelId)
            : AiProviderRequest::query()
                ->where('provider', 'openrouter')
                ->where('external_request_id', (string) ($data['id'] ?? $data['job_id'] ?? ''))
                ->with('aiModel')
                ->first()?->aiModel;
        if (!$model) throw new RuntimeException('مدل درخواست وب‌هوک OpenRouter قابل تشخیص نیست.');

        return $this->normalizeResponse($model, $data);
    }

    public function estimateCost(AiModel $model, array $payload = []): ?float
    {
        $workflow = (string) ($payload['workflow'] ?? '');
        if ($workflow === 'image_sequence_to_video') $workflow = 'image_to_video';
        $workflowTiers = (array) data_get($model->pricing_config, 'workflow_resolution_tiers', []);
        $resolution = (string) ($payload['resolution'] ?? '');
        $perSecond = (float) data_get($workflowTiers, $workflow . '.' . $resolution, 0);
        if ($perSecond <= 0) $perSecond = (float) ($model->cost_per_generation_usd ?: 0);
        $duration = max(1, (int) ($payload['duration'] ?? 1));
        return $perSecond > 0 ? $perSecond * $duration : null;
    }
}
