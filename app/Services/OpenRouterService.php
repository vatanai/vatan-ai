<?php

namespace App\Services;

use App\Contracts\AiImageProviderInterface;
use App\Models\Product;
use App\Models\AiModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class OpenRouterService implements AiImageProviderInterface
{
    public function provider(): string
    {
        return 'openrouter';
    }

    public function validateModelConfiguration(AiModel $model): array
    {
        $issues = [];
        if (blank($this->apiKey)) $issues[] = 'OPENROUTER_API_KEY تنظیم نشده است.';
        if (blank($model->externalModelId())) $issues[] = 'شناسه مدل خالی است.';
        return ['valid' => empty($issues), 'issues' => $issues];
    }

    public function getModelCapabilities(AiModel $model): array
    {
        return array_merge([
            'allowed_inputs' => ['prompt', 'resolution', 'aspect_ratio', 'n'],
            'supports_text_to_image' => true,
            'supports_image_to_image' => (bool) $model->supports_image_input,
        ], is_array($model->capability_config) ? $model->capability_config : []);
    }
    public function translateToPersian(string $text): string
    {
        $response = $this->postWithFailover('/chat/completions', [
            'model' => 'openai/gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'Translate the user text accurately into natural Persian. Preserve technical meaning and placeholders. Return only the Persian translation.'],
                ['role' => 'user', 'content' => $text],
            ],
            'temperature' => 0.1,
        ], 30);
        $response->throw();
        return trim((string) data_get($response->json(), 'choices.0.message.content'));
    }

    /** ارزیابی تصویری خروجی آزمایش با مدل بینایی ارزان OpenRouter. */
    public function scoreLabImage(string $modelId, string $prompt, string $imageData, int $timeout = 60): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENROUTER_API_KEY تنظیم نشده است.');
        }

        $content = [
            [
                'type' => 'text',
                'text' => "پرامپت مورد انتظار:\n{$prompt}\n\n"
                    . 'تصویر را فقط بر اساس این معیارها از ۱ تا ۵ امتیاز بده: '
                    . 'prompt_alignment، visual_quality، composition، product_fit. '
                    . 'فقط JSON معتبر با این ساختار برگردان: '
                    . '{"scores":{"prompt_alignment":1,"visual_quality":1,"composition":1,"product_fit":1},"summary":"..."}',
            ],
            ['type' => 'image_url', 'image_url' => ['url' => $imageData]],
        ];

        $response = $this->postWithFailover('/chat/completions', [
            'model' => $modelId ?: 'openai/gpt-4o-mini',
            'messages' => [['role' => 'user', 'content' => $content]],
            'temperature' => 0,
            'response_format' => ['type' => 'json_object'],
        ], $timeout);
        $response->throw();

        $json = $response->json();
        $content = trim((string) data_get($json, 'choices.0.message.content', '{}'));
        $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);
        $parsed = json_decode($content, true);
        if (!is_array($parsed)) $parsed = [];

        return [
            'scores' => collect((array) ($parsed['scores'] ?? []))
                ->map(fn ($score) => max(1, min(5, (int) $score)))
                ->all(),
            'summary' => trim((string) ($parsed['summary'] ?? '')),
            'usage' => (array) ($json['usage'] ?? []),
            'raw' => $json,
        ];
    }

    protected ?string $apiKey;
    protected ?string $gatewaySecret;
    protected array $baseUrls;
    protected int $defaultTimeout;

    public function __construct()
    {
        $credentials = app(AiProviderCredentials::class)->for('openrouter');
        $this->apiKey   = $credentials['api_key'] ?: config('services.openrouter.api_key');
        $this->gatewaySecret = config('services.openrouter.gateway_secret');
        $settingBaseUrl = null;
        try { $settingBaseUrl = \App\Models\AiProviderSetting::forProvider('openrouter')?->base_url; } catch (\Throwable) {}
        $this->baseUrls = $settingBaseUrl ? [rtrim($settingBaseUrl, '/')] : $this->resolveBaseUrls();
        $this->defaultTimeout = (int) ($credentials['timeout'] ?: config('services.openrouter.timeout', 60));
    }

    /**
     * لیست Endpointهای OpenRouter به‌ترتیب اولویت (Failover).
     * ابتدا OPENROUTER_BASE_URLS (چند آدرس با کاما — مثل چند «پل» کلادفلر + مسیر مستقیم)،
     * در نبودش OPENROUTER_BASE_URL تکی، و در نهایت آدرس مستقیم. اگر یک پل فیلتر/قطع شد،
     * خودکار سراغ بعدی می‌رود و سرویس نمی‌خوابد.
     */
    protected function resolveBaseUrls(): array
    {
        $raw  = config('services.openrouter.base_urls');
        $list = is_array($raw) ? $raw : preg_split('/[,\n]+/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($list)) {
            $list = [config('services.openrouter.base_url', 'https://openrouter.ai/api/v1')];
        }

        $normalized = [];
        foreach ($list as $url) {
            $url = rtrim(trim((string) $url), '/');
            if ($url !== '' && !in_array($url, $normalized, true)) {
                $normalized[] = $url;
            }
        }

        return $normalized ?: ['https://openrouter.ai/api/v1'];
    }

    /**
     * ارسال POST با Failover روی همه Endpointها. اگر یک آدرس در دسترس نبود (قطعی شبکه،
     * فیلتر/تحریم، یا خطای 5xx/401/403/404/429) خودکار آدرس بعدی امتحان می‌شود. فقط
     * خطاهای واقعی خودِ درخواست (مثل 400/422) بدون Failover برگردانده می‌شوند چون روی
     * همه Endpointها یکسان‌اند.
     */
    protected function postWithFailover(string $path, array $payload, int $timeout): \Illuminate\Http\Client\Response
    {
        $failoverStatuses = [401, 403, 404, 407, 408, 421, 425, 429, 451];
        $maxAttempts = max(1, (int) config('services.openrouter.max_attempts', 5));
        $lastError = null;

        foreach ($this->baseUrls as $index => $baseUrl) {
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    $response = Http::withHeaders($this->requestHeaders())
                        ->connectTimeout(12)
                        ->timeout($timeout)
                        ->post("{$baseUrl}{$path}", $payload);
                } catch (\Throwable $e) {
                    // خطای شبکه/DNS/فیلترِ متناوب — همین Endpoint را چند بار دیگر امتحان کن
                    $lastError = "شبکه ({$baseUrl}) تلاش {$attempt}: " . $e->getMessage();
                    Log::warning('OpenRouter: network error, retrying endpoint', ['endpoint' => $baseUrl, 'attempt' => $attempt, 'error' => $e->getMessage()]);
                    continue;
                }

                $status = $response->status();

                // موفق یا خطای واقعیِ خودِ درخواست (۴۰۰/۴۲۲) → همین را برگردان
                if ($status < 500 && !in_array($status, $failoverStatuses, true)) {
                    return $response;
                }

                // وضعیت مسدودکننده (۴۰۳ تحریم، ۵xx و ...) → این Endpoint فایده ندارد، برو سراغ بعدی
                $lastError = "پاسخ ناموفق ({$baseUrl}) HTTP {$status}";
                Log::warning('OpenRouter: endpoint blocked/failed, switching endpoint', ['endpoint' => $baseUrl, 'status' => $status, 'priority' => $index + 1]);
                break;
            }
        }

        throw new Exception($lastError ?? 'هیچ Endpointی برای OpenRouter در دسترس نبود.');
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
            'n'            => max(1, min(10, $n)),
        ], $extraPayload); // input_references و سایر پارامترها اینجا اضافه می‌شوند
        $payload = $this->normalizeImagePayload($modelId, $payload);

        Log::info('OpenRouter: ارسال درخواست تولید تصویر', ['model' => $modelId, 'prompt_length' => strlen($prompt)]);

        $response = $this->postWithFailover('/images', $payload, $this->defaultTimeout);

        if ($response->failed() && $payload['n'] > 1 && $this->isSingleImageOnlyError($response)) {
            Log::info('OpenRouter: مدل فقط یک خروجی در هر درخواست می‌پذیرد؛ ساخت خروجی‌ها به‌صورت تک‌تک ادامه پیدا می‌کند', [
                'model' => $modelId,
                'requested_count' => $payload['n'],
            ]);

            return $this->generateImagesOneByOne($payload, (int) $payload['n']);
        }

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

    /**
     * Images API یک شکل ورودی یکسان دارد، اما پارامترهای مجاز هر خانواده مدل
     * متفاوت است. مثلاً Gemini رزولوشن 1K/2K می‌گیرد و n آن فقط ۱ است؛
     * مدل‌های GPT Image به‌جای resolution از quality استفاده می‌کنند و بعضی
     * نسبت‌ها را قبول ندارند. ارسال پارامتر نامعتبر کل درخواست را 400 می‌کند.
     */
    protected function normalizeImagePayload(string $modelId, array $payload): array
    {
        if (!empty($payload['negative_prompt'])) {
            $payload['prompt'] = rtrim((string) $payload['prompt'])
                . "\n\nAvoid these unwanted traits: " . trim((string) $payload['negative_prompt']);
        }

        // این موارد متعلق به APIهای قدیمی/سرویس‌های دیگرند و در OpenRouter
        // Images API پارامتر عمومی محسوب نمی‌شوند.
        unset($payload['negative_prompt'], $payload['strength'], $payload['input_fidelity']);

        if (str_starts_with($modelId, 'google/') && str_contains($modelId, 'image')) {
            $resolution = (string) ($payload['resolution'] ?? '1K');
            $payload['resolution'] = match ($resolution) {
                '1080', '2K', '2160', '4K' => '2K',
                default => '1K',
            };
            unset(
                $payload['quality'],
                $payload['output_format'],
                $payload['background'],
                $payload['output_compression'],
                $payload['seed']
            );

            return $payload;
        }

        if (str_starts_with($modelId, 'openai/') && str_contains($modelId, 'image')) {
            $resolution = strtoupper((string) ($payload['resolution'] ?? '1K'));
            $payload['quality'] = in_array($resolution, ['1080', '2K', '4K'], true) ? 'high' : 'medium';
            unset($payload['resolution'], $payload['seed'], $payload['output_format']);

            if (in_array($modelId, ['openai/gpt-image-1', 'openai/gpt-image-1-mini'], true)) {
                $payload['aspect_ratio'] = $this->closestClassicOpenAiRatio((string) ($payload['aspect_ratio'] ?? '1:1'));
            } elseif ($modelId === 'openai/gpt-5.4-image-2') {
                unset($payload['aspect_ratio']);
            }
        }

        return $payload;
    }

    protected function closestClassicOpenAiRatio(string $ratio): string
    {
        if ($ratio === '1:1' || $ratio === 'auto') {
            return $ratio;
        }

        [$width, $height] = array_pad(array_map('floatval', explode(':', $ratio, 2)), 2, 1.0);

        return $width >= $height ? '3:2' : '2:3';
    }

    /**
     * برخی مدل‌های تصویر (از جمله خانواده Gemini Image) فقط n=1 را قبول
     * می‌کنند. در صورت اعلام این محدودیت، تعداد درخواستی را تک‌تک می‌سازیم
     * و پاسخ‌ها را به همان ساختار استاندارد Images API برمی‌گردانیم.
     */
    protected function generateImagesOneByOne(array $payload, int $count): array
    {
        $images = [];
        $usage = [];
        $created = null;

        for ($index = 0; $index < $count; $index++) {
            $singlePayload = array_merge($payload, ['n' => 1]);
            $response = $this->postWithFailover('/images', $singlePayload, $this->defaultTimeout);

            if ($response->failed()) {
                $body = $response->body();
                Log::error('OpenRouter: خطا در ساخت تک‌خروجی', [
                    'status' => $response->status(),
                    'model' => $payload['model'] ?? null,
                    'output_index' => $index + 1,
                    'body' => $body,
                ]);
                throw new Exception("OpenRouter HTTP {$response->status()}: {$body}");
            }

            $json = $response->json();
            if (empty($json['data']) || !is_array($json['data'])) {
                throw new Exception('تصویری در پاسخ OpenRouter یافت نشد. پاسخ: ' . json_encode($json));
            }

            $created ??= $json['created'] ?? time();
            $images = array_merge($images, $json['data']);
            $usage = $this->sumUsage($usage, is_array($json['usage'] ?? null) ? $json['usage'] : []);
        }

        return array_filter([
            'created' => $created,
            'data' => $images,
            'usage' => $usage,
        ], fn ($value) => $value !== null && $value !== []);
    }

    protected function isSingleImageOnlyError(\Illuminate\Http\Client\Response $response): bool
    {
        if (!in_array($response->status(), [400, 422], true)) {
            return false;
        }

        $message = strtolower($response->body());

        return str_contains($message, 'n must be')
            || str_contains($message, 'n should be')
            || str_contains($message, 'maximum of 1')
            || str_contains($message, 'max 1')
            || str_contains($message, 'at most 1')
            || str_contains($message, 'only 1 image')
            || str_contains($message, 'only one image');
    }

    protected function sumUsage(array $total, array $usage): array
    {
        foreach ($usage as $key => $value) {
            if (is_numeric($value)) {
                $total[$key] = ($total[$key] ?? 0) + $value;
            }
        }

        return $total;
    }

    /** تولید محصول با رعایت ترتیب مدل اصلی و fallbackهای ثبت‌شده در پنل. */
    public function generateForProduct(Product $product, string $prompt, string $resolution, string $aspectRatio, int $count = 1, array $extraPayload = []): array
    {
        $models = $this->buildPriorityList($product->primary_model, $product->fallback_models);
        $timeout = $product->timeout ?: $this->defaultTimeout;

        return $this->tryModelsInOrder($models, $timeout, function (string $modelId) use ($prompt, $resolution, $aspectRatio, $count, $extraPayload) {
            return $this->generateImageFromPrompt($modelId, $prompt, $resolution, $aspectRatio, $count, $extraPayload);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // تولید تصویر با AiModel (برای تست از پنل ادمین)
    // ─────────────────────────────────────────────────────────────────────
    public function generateImage(AiModel $aiModel, string $prompt, array $extraPayload = [], ?int $timeoutOverride = null): array
    {
        return $this->generateImageFromPrompt(
            $aiModel->openrouter_model_id,
            $prompt,
            $extraPayload['resolution']   ?? '1K',
            $extraPayload['aspect_ratio'] ?? '1:1',
            $extraPayload['n']            ?? 1
        );
    }

    /** اجرای مستقیم یک مدل با تصاویر مرجع؛ مخصوص آزمایشگاه محصول. */
    public function editImageWithModel(string $modelId, string $prompt, array $base64Images, ?int $timeout = null): array
    {
        return $this->callEditViaChat($modelId, $prompt, $base64Images, $timeout ?: $this->defaultTimeout);
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

        $response = $this->postWithFailover('/chat/completions', $payload, $timeout);

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

    /** هدرهای مشترک برای اتصال مستقیم یا گیت‌وی امن Cloudflare. */
    protected function requestHeaders(): array
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => config('app.name'),
        ];

        if (!empty($this->gatewaySecret)) {
            $headers['X-Vatan-Gateway-Key'] = $this->gatewaySecret;
        }

        return $headers;
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
