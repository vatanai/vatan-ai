<?php

namespace App\Services;

use App\Models\Product;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $defaultTimeout;

    public function __construct()
    {
        $this->apiKey   = config('services.openrouter.api_key');
        $this->baseUrl  = rtrim(config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
        $this->defaultTimeout = (int) config('services.openrouter.timeout', 60);
    }

    // ─────────────────────────────────────────────────────────────────────
    // متد اصلی سمت کاربر — ادیت عکس آپلودی با پرامپت
    // از endpoint: POST /api/v1/chat/completions با image_url
    // ─────────────────────────────────────────────────────────────────────
    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array
    {
        $models = $this->buildPriorityList($product->primary_model, $product->fallback_models);
        $timeout = $product->timeout ?: $this->defaultTimeout;

        return $this->tryModelsInOrder($models, $timeout, function (string $modelId, int $timeout) use ($prompt, $base64Images) {
            return $this->callEditViaChat($modelId, $prompt, $base64Images, $timeout);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // تولید عکس از پرامپت — بدون عکس ورودی
    // از endpoint: POST /api/v1/images
    // ─────────────────────────────────────────────────────────────────────
    public function generateImageFromPrompt(string $modelId, string $prompt, string $resolution = '1K', string $aspectRatio = '1:1', int $n = 1, array $extraPayload = []): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENROUTER_API_KEY تنظیم نشده است.');
        }

        $payload = array_merge([
            'model'        => $modelId,
            'prompt'       => $prompt,
            'resolution'   => $resolution,
            'aspect_ratio' => $aspectRatio,
            'n'            => $n,
        ], $extraPayload); // input_references و سایر پارامترها اینجا اضافه می‌شوند

        Log::info('OpenRouter: ارسال درخواست تولید تصویر', ['model' => $modelId, 'prompt_length' => strlen($prompt)]);

        $response = Http::withToken($this->apiKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title'      => config('app.name'),
            ])
            ->timeout($this->defaultTimeout)
            ->post("{$this->baseUrl}/images", $payload);

        if ($response->failed()) {
            $body = $response->body();
            Log::error('OpenRouter: خطا در تولید تصویر', ['status' => $response->status(), 'body' => $body]);
            throw new Exception("OpenRouter HTTP {$response->status()}: {$body}");
        }

        $json = $response->json();

        if (empty($json['data'])) {
            throw new Exception('تصویری در پاسخ OpenRouter یافت نشد. پاسخ: ' . json_encode($json));
        }

        Log::info('OpenRouter: تصویر با موفقیت تولید شد', ['model' => $modelId]);

        return $json;
    }

    // ─────────────────────────────────────────────────────────────────────
    // تولید تصویر با AiModel (برای تست از پنل ادمین)
    // ─────────────────────────────────────────────────────────────────────
    public function generateImage(AiModel $aiModel, string $prompt, array $extraPayload = [], ?int $timeoutOverride = null): array
    {
        return $this->generateImageFromPrompt(
            $aiModel->model_id,
            $prompt,
            $extraPayload['resolution']   ?? '1K',
            $extraPayload['aspect_ratio'] ?? '1:1',
            $extraPayload['n']            ?? 1
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // ادیت عکس از طریق chat/completions با image_url
    // ─────────────────────────────────────────────────────────────────────
    protected function callEditViaChat(string $modelId, string $prompt, array $base64Images, int $timeout): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENROUTER_API_KEY تنظیم نشده است.');
        }

        // ساخت content با متن + عکس‌ها
        $content = [['type' => 'text', 'text' => $prompt]];

        foreach ($base64Images as $b64) {
            // اگر data URI کامل نبود، اضافه کن
            $url = str_starts_with($b64, 'data:') ? $b64 : "data:image/jpeg;base64,{$b64}";
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }

        $payload = [
            'model'    => $modelId,
            'messages' => [['role' => 'user', 'content' => $content]],
        ];

        Log::info('OpenRouter: ارسال درخواست ادیت تصویر', ['model' => $modelId, 'images_count' => count($base64Images)]);

        $response = Http::withToken($this->apiKey)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title'      => config('app.name'),
            ])
            ->timeout($timeout)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if ($response->failed()) {
            $body = $response->body();
            Log::error('OpenRouter: خطا در ادیت تصویر', ['status' => $response->status(), 'body' => $body]);
            throw new Exception("OpenRouter HTTP {$response->status()}: {$body}");
        }

        $json = $response->json();

        if (empty($json['choices'])) {
            throw new Exception('پاسخ نامعتبر از OpenRouter دریافت شد: ' . json_encode($json));
        }

        return $json;
    }

    // ─────────────────────────────────────────────────────────────────────
    // کمکی: ساخت لیست مدل‌ها به ترتیب اولویت
    // ─────────────────────────────────────────────────────────────────────
    protected function buildPriorityList(?string $primary, $fallbacks): array
    {
        $list = is_array($fallbacks)
            ? $fallbacks
            : (json_decode($fallbacks ?? '[]', true) ?? []);

        return array_values(array_filter(array_merge([$primary], $list)));
    }

    // ─────────────────────────────────────────────────────────────────────
    // کمکی: تلاش به ترتیب اولویت — اولین موفق برگردانده می‌شود
    // ─────────────────────────────────────────────────────────────────────
    protected function tryModelsInOrder(array $models, int $timeout, callable $callApi): array
    {
        if (empty($models)) {
            throw new Exception('هیچ مدلی برای این محصول تنظیم نشده است.');
        }

        $attempts = [];

        foreach ($models as $idx => $modelId) {
            $start = microtime(true);
            try {
                $response  = $callApi($modelId, $timeout);
                $durationMs = round((microtime(true) - $start) * 1000);

                $attempts[] = ['model' => $modelId, 'priority' => $idx + 1, 'status' => 'success', 'duration_ms' => $durationMs];

                Log::info('OpenRouter: مدل موفق شد', ['model' => $modelId, 'priority' => $idx + 1, 'duration_ms' => $durationMs]);

                return ['model' => $modelId, 'data' => $response, 'attempts' => $attempts];

            } catch (Exception $e) {
                $attempts[] = ['model' => $modelId, 'priority' => $idx + 1, 'status' => 'failed', 'error' => $e->getMessage()];
                Log::warning('OpenRouter: مدل شکست خورد، رفتن به بعدی', ['model' => $modelId, 'error' => $e->getMessage()]);
                continue;
            }
        }

        Log::error('OpenRouter: همه مدل‌ها شکست خوردند', ['attempts' => $attempts]);
        throw new Exception('هیچ‌کدام از مدل‌های هوش مصنوعی پاسخ ندادند. لطفاً بعداً تلاش کنید.');
    }
}