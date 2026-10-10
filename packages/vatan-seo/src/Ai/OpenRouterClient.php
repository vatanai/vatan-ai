<?php

namespace Vatan\Seo\Ai;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * کلاینت مستقل OpenRouter برای موتور سئو.
 * - کلید و «پل» کلادفلر را از تنظیمات موتور یا services.openrouter میزبان می‌خواند.
 * - روی چند Endpoint (پل‌ها + مسیر مستقیم) Failover می‌کند.
 * - هزینه‌ی واقعی هر تماس را از usage.cost پاسخ OpenRouter برمی‌گرداند.
 */
class OpenRouterClient
{
    private const MODELS_CACHE_KEY = 'seo-engine:openrouter-models';
    private const KEY_INFO_CACHE_KEY = 'seo-engine:openrouter-key-info';

    public function apiKey(): string
    {
        return (string) (config('seo-engine.ai.dedicated_key') ?: config('seo-engine.ai.api_key') ?: config('services.openrouter.api_key', ''));
    }

    /** اطلاعات کلید: مصرف، سقف و باقیمانده‌ی اعتبار در OpenRouter (GET /key) */
    public function keyInfo(): ?array
    {
        foreach (array_reverse($this->baseUrls()) as $base) {
            try {
                $res = Http::withHeaders($this->headers())->connectTimeout(8)->timeout(15)->get($base.'/key');
                if ($res->successful() && is_array($res->json('data'))) {
                    return (array) $res->json('data');
                }
            } catch (\Throwable) {
                continue;
            }
        }
        return null;
    }

    /** فقط مقدار ذخیره‌شده را می‌خواند و برای رندر صفحه هیچ تماس شبکه‌ای ندارد. */
    public function cachedKeyInfo(): ?array
    {
        $value = Cache::get(self::KEY_INFO_CACHE_KEY);
        return is_array($value) ? $value : null;
    }

    public function configured(): bool
    {
        return $this->apiKey() !== '' || $this->gatewaySecret() !== '';
    }

    public function gatewaySecret(): string
    {
        return (string) (config('seo-engine.ai.gateway_secret') ?: config('services.openrouter.gateway_secret', ''));
    }

    /** @return string[] */
    public function baseUrls(): array
    {
        $raw = config('seo-engine.ai.base_urls') ?: config('services.openrouter.base_urls') ?: config('services.openrouter.base_url');
        $list = is_array($raw) ? $raw : preg_split('/[,\n]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);
        $list = array_map(fn ($u) => rtrim(trim($u), '/'), $list ?: []);
        $list[] = 'https://openrouter.ai/api/v1';

        return array_values(array_unique(array_filter($list)));
    }

    protected function headers(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'HTTP-Referer' => (string) config('seo-engine.host.url', config('app.url')),
            'X-Title' => 'Vatan SEO Engine',
        ];
        if ($this->apiKey() !== '') {
            $headers['Authorization'] = 'Bearer '.$this->apiKey();
        }
        if ($this->gatewaySecret() !== '') {
            $headers['X-Vatan-Gateway-Key'] = $this->gatewaySecret();
            // اگر سئو کلید اختصاصی (با سقف خرج جدا) دارد، پل کلادفلر به‌جای کلید عمومی از آن استفاده می‌کند
            if (filled(config('seo-engine.ai.dedicated_key'))) {
                $headers['X-Vatan-Client-Key'] = (string) config('seo-engine.ai.dedicated_key');
            }
        }
        return $headers;
    }

    /**
     * @return array{content:string, model:string, tokens_in:int, tokens_out:int, cost:?float, raw:array}
     */
    public function chat(array $payload, int $timeout = 120): array
    {
        $payload['usage'] = ['include' => true];
        $deadline = microtime(true) + $timeout;
        $lastError = 'OpenRouter در دسترس نیست.';

        foreach ($this->baseUrls() as $base) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $remaining = (int) ceil($deadline - microtime(true));
                if ($remaining < 2) {
                    break 2;
                }
                try {
                    $response = Http::withHeaders($this->headers())
                        ->connectTimeout(12)
                        ->timeout($remaining)
                        ->post($base.'/chat/completions', $payload);
                } catch (\Throwable $e) {
                    $lastError = 'خطای شبکه: '.$e->getMessage();
                    continue;
                }

                $status = $response->status();
                if ($response->successful()) {
                    $json = (array) $response->json();
                    if (isset($json['error'])) {
                        throw new AiException('OpenRouter: '.data_get($json, 'error.message', 'خطای نامشخص'), (int) data_get($json, 'error.code', 0));
                    }
                    return [
                        'content' => trim((string) data_get($json, 'choices.0.message.content', '')),
                        'model' => (string) data_get($json, 'model', $payload['model'] ?? ''),
                        'tokens_in' => (int) data_get($json, 'usage.prompt_tokens', 0),
                        'tokens_out' => (int) data_get($json, 'usage.completion_tokens', 0),
                        'cost' => data_get($json, 'usage.cost') !== null ? (float) data_get($json, 'usage.cost') : null,
                        'raw' => $json,
                    ];
                }

                // خطای خود درخواست (مدل نامعتبر، ورودی غلط) → Failover روی Endpoint فایده ندارد
                if (in_array($status, [400, 402, 404, 422], true)) {
                    throw new AiException('OpenRouter HTTP '.$status.': '.mb_substr((string) data_get($response->json(), 'error.message', $response->body()), 0, 300), $status);
                }
                $lastError = "HTTP {$status} از {$base}";
                Log::warning('SEO Engine: OpenRouter endpoint failed', ['endpoint' => $base, 'status' => $status]);
                if ($status >= 500 || $status === 429) {
                    usleep(400_000);
                    continue;
                }
                break; // 401/403 تحریم/مسدود → Endpoint بعدی
            }
        }

        throw new AiException($lastError);
    }

    /**
     * فهرست مدل‌های زنده (کش ۱۲ ساعته). اگر دریافت نشد، آرایه‌ی خالی؛
     * در آن صورت مسیریاب روی خطای «مدل نامعتبر» به کاندید بعدی می‌رود.
     * @return array<string, array{in:float,out:float}>
     */
    public function models(bool $fresh = false): array
    {
        $key = self::MODELS_CACHE_KEY;
        if ($fresh) {
            Cache::forget($key);
        }
        return Cache::remember($key, now()->addHours(12), function () {
            foreach (array_reverse($this->baseUrls()) as $base) { // مسیر مستقیم اول؛ پل‌ها معمولاً فقط chat را عبور می‌دهند
                try {
                    $response = Http::withHeaders($this->headers())->connectTimeout(8)->timeout(20)->get($base.'/models');
                    if (! $response->successful()) {
                        continue;
                    }
                    $out = [];
                    foreach ((array) data_get($response->json(), 'data', []) as $m) {
                        $out[(string) $m['id']] = [
                            'in' => (float) data_get($m, 'pricing.prompt', 0) * 1_000_000,
                            'out' => (float) data_get($m, 'pricing.completion', 0) * 1_000_000,
                            'name' => (string) data_get($m, 'name', $m['id']),
                        ];
                    }
                    if ($out) {
                        return $out;
                    }
                } catch (\Throwable) {
                    continue;
                }
            }
            return [];
        });
    }

    /** فهرست مدل‌های کش‌شده؛ هرگز برای صفحهٔ داشبورد شبکه را صدا نمی‌زند. */
    public function cachedModels(): array
    {
        $value = Cache::get(self::MODELS_CACHE_KEY);
        return is_array($value) ? $value : [];
    }
}
