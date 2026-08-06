<?php

namespace App\Services;

use App\Models\SmsMessage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use App\Models\SmsProvider;
use Illuminate\Support\Facades\Schema;

class MeliPayamakService
{
    private function normalizeBaseUrl(string $baseUrl): string
    {
        $baseUrl = trim($baseUrl);
        if ($baseUrl === '') {
            return 'https://console.melipayamak.com/api';
        }

        if (str_contains($baseUrl, 'console.melipayamak.com/api')) {
            return preg_replace('#^http://#i', 'https://', $baseUrl) ?: 'https://console.melipayamak.com/api';
        }

        return $baseUrl;
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()->asJson()
            ->timeout(config('services.melipayamak.timeout', 15))
            ->connectTimeout(10);
    }

    private function url(string $action): string
    {
        $provider = Schema::hasTable('sms_providers') ? SmsProvider::where('is_active', true)->orderByDesc('is_default')->first() : null;
        $configKey = trim((string) config('services.melipayamak.api_key'));
        $key = $configKey !== '' ? $configKey : trim((string) $provider?->api_key);
        if ($key === '') {
            throw new RuntimeException('کلید اتصال ملی‌پیامک تنظیم نشده است.');
        }

        $configBaseUrl = trim((string) config('services.melipayamak.base_url'));
        $baseUrl = $configBaseUrl !== '' ? $configBaseUrl : (string) $provider?->base_url;
        $baseUrl = $this->normalizeBaseUrl($baseUrl);
        return rtrim($baseUrl, '/') . '/' . trim($action, '/') . '/' . $key;
    }

    public function sendOtp(string $to): array
    {
        $response = $this->client()->post($this->url('send/otp'), ['to' => $to]);
        $data = $response->json() ?: [];
        if (!$response->successful() || empty($data['code'])) {
            throw new RuntimeException($data['status'] ?? 'ارسال رمز یک‌بارمصرف ناموفق بود.');
        }

        $this->log('otp', $to, null, $data, 'sent');
        return $data;
    }

    public function sendSimple(string $to, string $text, ?string $from = null, string $type = 'simple'): array
    {
        $sender = $from ?: $this->sender();
        if (!$sender) {
            throw new RuntimeException('خط فرستنده پیامک تنظیم نشده است.');
        }

        $response = $this->client()->post($this->url('send/simple'), [
            'from' => $sender, 'to' => $to, 'text' => $text,
        ]);
        $data = $response->json() ?: [];
        $ok = $response->successful() && !empty($data['recId']);
        $this->log($type, $to, $text, $data, $ok ? 'sent' : 'failed', $sender);

        if (!$ok) throw new RuntimeException($data['status'] ?? 'ارسال پیامک ناموفق بود.');
        return $data;
    }

    public function sendShared(string $to, array $values, string $bodyId, ?string $body = null, string $type = 'shared'): array
    {
        if (trim($bodyId) === '') throw new RuntimeException('شناسه الگوی خط خدماتی تنظیم نشده است.');

        $response = $this->client()->post($this->url('send/shared'), [
            'to' => $to,
            'args' => array_values(array_map(static fn ($value) => (string) $value, $values)),
            'bodyId' => $bodyId,
        ]);
        $data = $response->json() ?: [];
        $providerId = $data['recId'] ?? $data['id'] ?? null;
        $ok = $response->successful() && !empty($providerId);
        if ($providerId && empty($data['recId'])) $data['recId'] = $providerId;
        $this->log($type, $to, $body, $data, $ok ? 'sent' : 'failed', metadata: [
            'body_id' => $bodyId, 'values' => array_values($values),
        ]);
        if (!$ok) throw new RuntimeException($this->errorMessage($response, $data, 'ارسال از خط خدماتی ناموفق بود.'));
        return $data;
    }

    public function sendAdvanced(array $recipients, string $text, ?string $from = null): array
    {
        $sender = $from ?: $this->sender();
        if (!$sender) throw new RuntimeException('خط فرستنده پیامک تنظیم نشده است.');

        $response = $this->client()->post($this->url('send/advanced'), [
            'from' => $sender, 'to' => array_values($recipients), 'text' => $text, 'udh' => '',
        ]);
        $data = $response->json() ?: [];
        $ok = $response->successful() && !empty($data['recIds']);
        foreach ($recipients as $index => $recipient) {
            $row = $data;
            $row['recId'] = $data['recIds'][$index] ?? null;
            $this->log('advanced', $recipient, $text, $row, $ok ? 'sent' : 'failed', $sender);
        }
        if (!$ok) throw new RuntimeException($data['status'] ?? 'ارسال گروهی ناموفق بود.');
        return $data;
    }

    public function schedule(string $to, string $text, string $date, ?int $period = null, ?string $from = null): array
    {
        $sender = $from ?: $this->sender();
        if (!$sender) throw new RuntimeException('خط فرستنده پیامک تنظیم نشده است.');
        $payload = ['message' => $text, 'from' => $sender, 'to' => $to, 'date' => $date];
        if ($period) $payload['period'] = $period;
        $response = $this->client()->post($this->url('send/schedule'), $payload);
        $data = $response->json() ?: [];
        $ok = $response->successful() && !empty($data['id']);
        $this->log('scheduled', $to, $text, $data, $ok ? 'scheduled' : 'failed', $sender, ['date' => $date, 'period' => $period]);
        if (!$ok) throw new RuntimeException($data['status'] ?? 'زمان‌بندی پیامک ناموفق بود.');
        return $data;
    }

    private function sender(): ?string
    {
        $configured = trim((string) config('services.melipayamak.from'));
        if ($configured !== '') return $configured;
        if (Schema::hasTable('sms_providers')) {
            $sender = SmsProvider::where('is_active', true)->orderByDesc('is_default')->value('sender');
            if ($sender) return $sender;
        }
        return null;
    }

    private function errorMessage($response, array $data, string $fallback): string
    {
        $message = $data['status'] ?? $data['message'] ?? $data['error'] ?? null;
        if (is_string($message) && trim($message) !== '') return $message;
        $body = trim(strip_tags((string) $response->body()));
        if ($body !== '' && mb_strlen($body) <= 300) return $body;
        return "{$fallback} (HTTP {$response->status()})";
    }

    public function balance(): mixed
    {
        $response = $this->client()->get($this->url('receive/balance'));
        if (!$response->successful()) throw new RuntimeException('دریافت اعتبار ملی‌پیامک ناموفق بود.');
        if (!str_contains(strtolower((string) $response->header('Content-Type')), 'application/json')) {
            throw new RuntimeException('ملی‌پیامک به‌جای پاسخ API صفحه کنسول را برگرداند؛ دسترسی این متد را در پنل بررسی کنید.');
        }
        $data = $response->json();
        return is_array($data) ? ($data['balance'] ?? $data['credit'] ?? $data['value'] ?? $data) : $response->body();
    }

    public function probe(): array
    {
        $response = $this->client()->post($this->url('send/shared'), [
            'to' => '09000000000',
            'args' => ['connection-probe'],
            'bodyId' => '0',
        ]);

        if (! str_contains(strtolower((string) $response->header('Content-Type')), 'application/json')) {
            throw new RuntimeException('ملی‌پیامک به‌جای پاسخ API صفحه کنسول را برگرداند.');
        }

        $data = $response->json() ?: [];
        $message = (string) ($data['status'] ?? $data['message'] ?? $data['error'] ?? '');
        if (str_contains($message, 'کلید کنسول معتبر نیست')) {
            throw new RuntimeException($message);
        }

        // الگوی عمداً نامعتبر است؛ پاسخ JSON با رد الگو یعنی
        // سرویس قابل دسترسی است و کلید هم پذیرفته شده است. این بررسی پیامکی نمی‌فرستد.
        return ['connected' => true, 'message' => $message];
    }

    private function log(string $type, string $to, ?string $body, array $response, string $status, ?string $sender = null, array $metadata = []): void
    {
        $containsOtp = str_contains(strtolower($type), 'otp') || str_contains(strtolower($type), 'authentication');
        if ($containsOtp) {
            $body = 'رمز یک‌بارمصرف (مخفی‌شده)';
            unset($metadata['values']);
        }

        SmsMessage::create([
            'type' => $type, 'direction' => 'outgoing', 'recipient' => $to,
            'sender' => $sender, 'body' => $body, 'provider_id' => $response['recId'] ?? $response['id'] ?? null,
            'status' => $status, 'provider_status' => $response['status'] ?? null,
            'metadata' => $metadata ?: null, 'sent_at' => in_array($status, ['sent', 'scheduled'], true) ? now() : null,
        ]);
    }
}
