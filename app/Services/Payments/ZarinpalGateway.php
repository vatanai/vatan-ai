<?php

namespace App\Services\Payments;

use App\Models\PlanPurchase;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.zarinpal.merchant_id'));
    }

    /**
     * @return array{track_id:string,redirect_url:string,response:array<string,mixed>}
     */
    public function request(PlanPurchase $purchase, User $user, string $callbackUrl): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('درگاه پرداخت هنوز پیکربندی نشده است. لطفاً چند دقیقه دیگر دوباره تلاش کنید.');
        }

        $metadata = array_filter([
            'mobile' => $this->normalMobile($user->phone ?: $purchase->billing_phone),
            'email' => filter_var($user->email ?: $purchase->billing_email, FILTER_VALIDATE_EMAIL) ?: null,
        ]);

        $response = $this->post(config('services.zarinpal.request_url'), [
            'merchant_id' => config('services.zarinpal.merchant_id'),
            'amount' => $this->toRial((int) $purchase->paid_amount),
            'callback_url' => $callbackUrl,
            'description' => 'خرید پلن ' . $purchase->plan_name . ' از وطن',
            'metadata' => $metadata,
        ]);

        $code = (int) data_get($response, 'data.code', 0);
        $authority = trim((string) data_get($response, 'data.authority', ''));
        if ($code !== 100 || $authority === '') {
            throw new RuntimeException($this->message($response, 'ایجاد درخواست پرداخت ناموفق بود.'));
        }

        return [
            'track_id' => $authority,
            'redirect_url' => rtrim((string) config('services.zarinpal.start_url'), '/') . '/' . $authority,
            'response' => $response,
        ];
    }

    /** @return array<string,mixed> */
    public function verify(PlanPurchase $purchase, string $authority): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('امکان بررسی پرداخت به‌دلیل پیکربندی‌نشدن درگاه وجود ندارد.');
        }

        return $this->post(config('services.zarinpal.verify_url'), [
            'merchant_id' => config('services.zarinpal.merchant_id'),
            'amount' => $this->toRial((int) $purchase->paid_amount),
            'authority' => $authority,
        ]);
    }

    /** @param array<string,mixed> $response */
    public function verifiedSuccessfully(array $response): bool
    {
        return in_array((int) data_get($response, 'data.code', 0), [100, 101], true);
    }

    /** @param array<string,mixed> $response */
    public function amountMatches(PlanPurchase $purchase, array $response): bool
    {
        $amount = (int) data_get($response, 'data.amount', 0);

        // زرین‌پال مبلغ را در درخواست verify دریافت می‌کند و معمولاً آن را در پاسخ برنمی‌گرداند.
        return $amount === 0 || $amount === $this->toRial((int) $purchase->paid_amount);
    }

    /** @param array<string,mixed> $response */
    public function reference(array $response, string $fallback): string
    {
        return (string) (data_get($response, 'data.ref_id') ?: $fallback);
    }

    /** @param array<string,mixed> $response */
    public function status(array $response): string
    {
        return (string) (data_get($response, 'data.code') ?: data_get($response, 'errors.code', ''));
    }

    /** @param array<string,mixed> $response */
    public function message(array $response, string $fallback = 'درگاه پرداخت پاسخ معتبری نداد.'): string
    {
        return (string) (data_get($response, 'errors.message')
            ?: data_get($response, 'data.message')
            ?: $fallback);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function post(string $url, array $payload): array
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withUserAgent('VatanAI Zarinpal Gateway')
                ->timeout((int) config('services.zarinpal.timeout', 15))
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('ارتباط با درگاه پرداخت برقرار نشد. لطفاً دوباره تلاش کنید.', previous: $exception);
        }

        $data = $response->json();
        $data = is_array($data) ? $data : [];
        if ($response->failed()) {
            throw new RuntimeException($this->message($data));
        }

        return $data;
    }

    private function toRial(int $toman): int
    {
        return $toman * (int) config('services.zarinpal.rial_multiplier', 10);
    }

    private function normalMobile(?string $mobile): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $mobile) ?: '';
        if (str_starts_with($digits, '98')) {
            $digits = '0' . substr($digits, 2);
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : null;
    }
}
