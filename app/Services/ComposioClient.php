<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** واسط محدود و بدون نگهداری داده‌ی حساس برای اجرای ابزارهای ثبت‌شده در Composio. */
class ComposioClient
{
    /** @return array{ok:bool,message:string,data:array,external_id:?string,retryable:bool,status:?int} */
    public function execute(string $toolSlug, array $arguments, ?string $connectedAccountId = null, ?string $userId = null): array
    {
        $apiKey = trim((string) config('services.composio.api_key'));
        if ($apiKey === '') {
            return $this->failure('کلید اتصال `Composio` روی این محیط تنظیم نشده است.');
        }

        $accountId = trim((string) ($connectedAccountId ?: config('services.composio.connected_account_id')));
        $resolvedUserId = trim((string) ($userId ?: config('services.composio.user_id')));
        if ($accountId === '' || $resolvedUserId === '') {
            return $this->failure('شناسه‌ی حساب متصل `Composio` کامل نشده است.');
        }

        try {
            /** @var Response $response */
            $response = $this->client($apiKey)->post('/tools/execute/'.rawurlencode($toolSlug), [
                'connected_account_id' => $accountId,
                'user_id' => $resolvedUserId,
                'version' => config('services.composio.toolkit_version', 'latest'),
                'arguments' => $arguments,
            ]);
        } catch (\Throwable) {
            return $this->failure('ارتباط با سرویس `Composio` برقرار نشد.', true);
        }

        $body = (array) $response->json();
        $successful = $response->successful() && ($body['successful'] ?? true) === true;
        if ($successful) {
            $data = (array) ($body['data'] ?? []);
            $externalId = (string) ($data['message_id'] ?? $data['id'] ?? $body['log_id'] ?? '');

            return [
                'ok' => true,
                'message' => 'درخواست با موفقیت به `Composio` رسید.',
                'data' => $data,
                'external_id' => $externalId !== '' ? $externalId : null,
                'retryable' => false,
                'status' => $response->status(),
            ];
        }

        $status = $response->status();
        $error = (string) ($body['error'] ?? $body['message'] ?? 'سرویس `Composio` درخواست را نپذیرفت.');
        if (is_array($body['error'] ?? null)) {
            $error = (string) (($body['error']['message'] ?? $body['error']['code'] ?? 'سرویس `Composio` درخواست را نپذیرفت.'));
        }

        return $this->failure($this->safeMessage($error), $status === 408 || $status === 429 || $status >= 500, $status);
    }

    /**
     * اجرای یک endpoint رسمی که هنوز در فهرست ابزارهای Composio نیست؛
     * Composio خودش اعتبار اتصال را تزریق می‌کند و توکن خام به برنامه برنمی‌گردد.
     * @return array{ok:bool,message:string,data:array,external_id:?string,retryable:bool,status:?int}
     */
    public function proxy(string $endpoint, string $method, array $body = [], array $parameters = [], ?string $connectedAccountId = null): array
    {
        $apiKey = trim((string) config('services.composio.api_key'));
        if ($apiKey === '') {
            return $this->failure('کلید اتصال `Composio` روی این محیط تنظیم نشده است.');
        }

        $accountId = trim((string) ($connectedAccountId ?: config('services.composio.connected_account_id')));
        if ($accountId === '') {
            return $this->failure('شناسه‌ی حساب متصل `Composio` کامل نشده است.');
        }

        $payload = [
            'connected_account_id' => $accountId,
            'endpoint' => $endpoint,
            'method' => strtoupper($method),
        ];
        if ($body !== []) {
            $payload['body'] = $body;
        }
        if ($parameters !== []) {
            $payload['parameters'] = $parameters;
        }

        try {
            /** @var Response $response */
            $response = $this->client($apiKey)->post('/tools/execute/proxy', $payload);
        } catch (\Throwable) {
            return $this->failure('ارتباط با مسیر واسط `Composio` برقرار نشد.', true);
        }

        $responseBody = (array) $response->json();
        $status = (int) ($responseBody['status'] ?? $response->status());
        $ok = $response->successful() && $status >= 200 && $status < 300;
        if ($ok) {
            $data = (array) ($responseBody['data'] ?? $responseBody);
            $externalId = (string) ($data['message_id'] ?? $data['id'] ?? '');

            return ['ok' => true, 'message' => 'درخواست واسط `Composio` با موفقیت اجرا شد.', 'data' => $data, 'external_id' => $externalId !== '' ? $externalId : null, 'retryable' => false, 'status' => $status];
        }

        $error = (string) data_get($responseBody, 'error.message', $responseBody['message'] ?? 'درخواست واسط `Composio` رد شد.');

        return $this->failure($this->safeMessage($error), $status === 408 || $status === 429 || $status >= 500, $status);
    }

    private function client(string $apiKey): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.composio.base_url'), '/'))
            ->withHeaders(['x-api-key' => $apiKey])
            ->acceptJson()
            ->connectTimeout((int) config('services.composio.connect_timeout', 10))
            ->timeout((int) config('services.composio.timeout', 45));
    }

    /** @return array{ok:bool,message:string,data:array,external_id:?string,retryable:bool,status:?int} */
    private function failure(string $message, bool $retryable = false, ?int $status = null): array
    {
        return ['ok' => false, 'message' => $message, 'data' => [], 'external_id' => null, 'retryable' => $retryable, 'status' => $status];
    }

    private function safeMessage(string $message): string
    {
        return mb_substr(preg_replace('/(x-api-key|api[_ -]?key|token)\s*[:=]\s*[^\s,}]+/iu', '$1: [hidden]', $message) ?: 'خطا در سرویس `Composio`.', 0, 500);
    }
}
