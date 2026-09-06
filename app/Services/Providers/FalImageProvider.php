<?php

namespace App\Services\Providers;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use Illuminate\Http\Client\ConnectionException;
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

        // Timeout اتصال باید کوتاه‌تر از timeout کل باشد؛ در غیر این صورت
        // قطعی شبکه‌ی سرور تا ۹۰ ثانیه به‌صورت «آزمایش بدون خروجی» دیده می‌شود.
        // صف Fal درخواست را نگه می‌دارد و بعد از submit نیازی به اتصال باز
        // طولانی نداریم.
        $connectTimeout = max(8, min(15, (int) ($credentials['timeout'] ?: 600)));
        $requestTimeout = max(60, (int) ($credentials['timeout'] ?: 600));
        try {
            $response = Http::withHeaders($this->requestHeaders())
                ->connectTimeout($connectTimeout)
                ->timeout($requestTimeout)
                ->post($url, $input);
        } catch (ConnectionException $error) {
            throw new RuntimeException(
                'سرور برنامه نتوانست به صف Fal.ai متصل شود. دسترسی خروجی HTTPS به queue.fal.run را در کلودیوا بررسی کنید.',
                previous: $error
            );
        }

        if ($response->failed()) {
            throw new RuntimeException('Fal.ai HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    protected function pollRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('fal');
        $base = rtrim($credentials['base_url'] ?: 'https://queue.fal.run', '/');
        $request = AiProviderRequest::query()
            ->where('provider', 'fal')
            ->where('external_request_id', $requestId)
            ->first();
        $remote = $this->remoteRequestPayload($request);
        // Fal.ai لینک‌های دقیق status/response را در پاسخ submit برمی‌گرداند.
        // بعضی endpointها (مثل نسخه‌های مختلف Flux) مسیر نهایی متفاوتی از
        // شناسه‌ی مدل ثبت‌شده دارند؛ بازسازی URL از روی model id در این حالت
        // باعث 405 می‌شود. لینک provider را اولویت می‌دهیم و مسیر قدیمی فقط
        // برای رکوردهای قدیمیِ بدون این لینک پشتیبان است.
        $statusUrl = (string) ($remote['status_url'] ?? '');
        if ($statusUrl === '') {
            $statusUrl = $base . '/' . ltrim($model->externalModelId(), '/') . '/requests/' . rawurlencode($requestId) . '/status';
        }

        $response = Http::withHeaders($this->requestHeaders())
            ->retry(2, 750, fn ($exception) => $exception instanceof ConnectionException)
            ->connectTimeout(10)
            ->timeout(max(30, (int) ($credentials['timeout'] ?: 600)))
            ->get($statusUrl);

        if ($response->failed()) {
            throw new RuntimeException('Fal.ai status HTTP ' . $response->status() . ': ' . $response->body());
        }

        $status = (array) $response->json();
        $responseUrl = (string) ($status['response_url'] ?? ($remote['response_url'] ?? ''));
        if (strtoupper((string) ($status['status'] ?? '')) === 'COMPLETED' && $responseUrl !== '') {
            $result = Http::withHeaders($this->requestHeaders())
                ->retry(2, 750, fn ($exception) => $exception instanceof ConnectionException, throw: false)
                ->connectTimeout(10)
                ->timeout(max(30, (int) ($credentials['timeout'] ?: 600)))
                ->get($responseUrl);

            if ($result->failed()) {
                throw new RuntimeException('Fal.ai result HTTP ' . $result->status() . ': ' . $result->body());
            }

            $status['result'] = (array) $result->json();
        }

        return $status;
    }

    protected function cancelRemote(AiModel $model, string $requestId): array
    {
        $credentials = $this->credentials->for('fal');
        $base = rtrim($credentials['base_url'] ?: 'https://queue.fal.run', '/');
        $request = AiProviderRequest::query()
            ->where('provider', 'fal')
            ->where('external_request_id', $requestId)
            ->first();
        $remote = $this->remoteRequestPayload($request);
        $cancelUrl = (string) ($remote['cancel_url'] ?? '');
        if ($cancelUrl === '') {
            $cancelUrl = $base . '/' . ltrim($model->externalModelId(), '/') . '/requests/' . rawurlencode($requestId) . '/cancel';
        }
        $response = Http::withHeaders($this->requestHeaders())
            ->retry(2, 750, fn ($exception) => $exception instanceof ConnectionException)
            ->timeout(max(30, (int) ($credentials['timeout'] ?: 600)))
            ->put($cancelUrl);

        if ($response->failed() && $response->status() !== 400) {
            throw new RuntimeException('Fal.ai cancel HTTP ' . $response->status() . ': ' . $response->body());
        }

        return (array) $response->json();
    }

    /**
     * در پاسخ اولیه‌ی صف، لینک‌ها در ریشه هستند؛ بعد از اولین poll همان پاسخ
     * داخل provider_metadata.response ذخیره می‌شود. هر دو شکل باید بدون
     * بازسازی حدسی URL پشتیبانی شوند، چون بعضی endpointها مسیر کوتاه‌شده دارند.
     */
    private function remoteRequestPayload(?AiProviderRequest $request): array
    {
        $raw = is_array($request?->raw_response) ? $request->raw_response : [];
        $response = $raw['response'] ?? null;

        return is_array($response) ? $response : $raw;
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
        // خروجی HTTP صف Fal در بعضی endpointها مستقیم و در بعضی دیگر داخل
        // data برمی‌گردد. هر دو قرارداد رسمی را می‌خوانیم تا پاسخ موفق به‌اشتباه
        // «بدون عکس» ثبت نشود.
        foreach ([$result['data'] ?? null, $payload['data'] ?? null] as $nested) {
            if (is_array($nested) && collect(['images', 'image', 'video', 'videos', 'output', 'outputs'])->contains(fn (string $key): bool => !empty($nested[$key] ?? null))) {
                $result = $nested;
                break;
            }
        }

        $items = [];
        foreach (['images', 'image', 'video', 'videos', 'output', 'outputs'] as $key) {
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

        $error = $payload['error'] ?? data_get($payload, 'result.error');
        $errorMessage = is_string($error)
            ? $error
            : (is_array($error) ? json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null);
        $missingOutput = in_array($status, ['COMPLETED', 'OK'], true) && $items === [];
        if ($missingOutput) {
            $normalizedStatus = 'failed';
            $errorMessage = $errorMessage ?: 'Fal.ai درخواست را تکمیل‌شده اعلام کرد، اما هیچ فایل تصویری یا ویدیویی برنگرداند.';
        }

        $requestId = (string) ($payload['request_id'] ?? $payload['gateway_request_id'] ?? '');
        $billingEvent = null;
        // طبق قرارداد Fal فقط خروجی موفق قابل‌صورتحساب است. اگر provider
        // وضعیت COMPLETED بدهد اما برنامه هیچ فایل معتبری از پاسخ استخراج
        // نکند، آن را موفق/قابل‌کسر ثبت نکن و هزینه را از پاسخ ناقص حدس نزن.
        if ($normalizedStatus === 'completed' && $items !== [] && $requestId !== '') {
            $billingEvent = app(\App\Services\FalAiBillingService::class)->billingEvent($requestId);
        }
        return [
            'provider' => 'fal',
            'external_request_id' => $requestId,
            'status' => $normalizedStatus,
            'output_urls' => $items,
            'estimated_cost_usd' => $this->estimateCost($model),
            'actual_cost_usd' => $normalizedStatus === 'completed' && $items !== []
                ? app(\App\Services\FalAiBillingService::class)->costFromBillingEvent($billingEvent)
                : null,
            'error_code' => $payload['error_type'] ?? ($missingOutput ? 'provider_output_missing' : null),
            'error_message' => $errorMessage,
            'provider_metadata' => array_filter(['response' => $payload, 'billing_event' => $billingEvent], fn ($value) => $value !== null),
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
