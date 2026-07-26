<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ══════════════════════════════════════════════════════
 * LiaraAiService — سرویس هوش مصنوعی لیارا
 * ──────────────────────────────────────────────────────
 * این سرویس با OpenAI-compatible API لیارا کار می‌کند.
 * رابط عمومی آن دقیقاً همان OpenRouterService است؛
 * بنابراین AiProviderRouter بدون تغییر می‌تواند به‌جای
 * هم از آن‌ها استفاده کند.
 *
 * تفاوت‌های اصلی با OpenRouter:
 * - Endpoint: POST /images/generations  (نه /images)
 * - پارامتر size به‌جای resolution + aspect_ratio
 * - پارامتر quality: "high" | "medium" | "low"
 * - پشتیبانی از input (برای image editing در gpt-image-1)
 * ══════════════════════════════════════════════════════
 */
class LiaraAiService
{
    protected string $apiKey;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey  = (string) config('services.liara.api_key', '');
        $this->baseUrl = rtrim((string) config('services.liara.base_url', 'https://ai.liara.ir/api/v1'), '/');
        $this->timeout = (int) config('services.liara.timeout', 120);
    }

    // ─────────────────────────────────────────────────────────────
    // API اصلی: تولید تصویر از پرامپت
    // ─────────────────────────────────────────────────────────────
    /**
     * تولید تصویر از پرامپت (بدون عکس ورودی).
     *
     * @param  string  $modelId       شناسه مدل لیارا مثل gpt-image-1
     * @param  string  $prompt        پرامپت انگلیسی
     * @param  string  $resolution    1K | 2K | 4K  (داخلی — تبدیل به quality می‌شود)
     * @param  string  $aspectRatio   1:1 | 16:9 | 9:16 | ...
     * @param  int     $n             تعداد تصویر
     * @param  array   $extraPayload  پارامترهای اضافی (input_references → input برای ویرایش)
     * @return array   { data: [{ b64_json: "..." }] }
     */
    public function generateImageFromPrompt(
        string $modelId,
        string $prompt,
        string $resolution   = '1K',
        string $aspectRatio  = '1:1',
        int    $n            = 1,
        array  $extraPayload = []
    ): array {
        if (empty($this->apiKey)) {
            throw new Exception('LIARA_AI_API_KEY تنظیم نشده است.');
        }

        $size    = $this->resolveSize($aspectRatio);
        $quality = $this->resolveQuality($resolution);
        $inputImages = $this->extractInputImages($extraPayload);

        if (!empty($inputImages)) {
            return $this->callEditApi($modelId, $prompt, $inputImages, $n, $size, $quality);
        }

        $payload = [
            'model'   => $modelId,
            'prompt'  => $prompt,
            'n'       => $n,
            'size'    => $size,
            'quality' => $quality,
        ];

        // پارامترهای غیرمربوط به OpenAI را حذف می‌کنیم
        // (negative_prompt، strength، output_format، provider، aspect_ratio، resolution)
        $unsupported = ['negative_prompt', 'strength', 'output_format', 'provider', 'aspect_ratio', 'resolution', 'input_references'];
        foreach ($unsupported as $key) {
            unset($extraPayload[$key]);
        }

        // seed در صورت وجود اضافه می‌شود
        if (!empty($extraPayload['seed'])) {
            $payload['seed'] = (int) $extraPayload['seed'];
        }

        Log::info('LiaraAI: ارسال درخواست تولید تصویر', [
            'model'   => $modelId,
            'size'    => $size,
            'quality' => $quality,
            'prompt_length' => strlen($prompt),
        ]);

        $response = $this->post('/images/generations', $payload);

        if ($response->failed()) {
            $body = $response->body();
            Log::error('LiaraAI: خطا در تولید تصویر', ['status' => $response->status(), 'body' => $body]);
            throw new Exception("Liara AI HTTP {$response->status()}: {$body}");
        }

        $json = $response->json();

        if (empty($json['data'])) {
            throw new Exception('تصویری در پاسخ لیارا یافت نشد. پاسخ: ' . json_encode($json));
        }

        Log::info('LiaraAI: تصویر با موفقیت تولید شد', ['model' => $modelId]);

        return $json;
    }

    // ─────────────────────────────────────────────────────────────
    // تولید محصول با ترتیب مدل اصلی + fallbackها
    // ─────────────────────────────────────────────────────────────
    public function generateForProduct(
        Product $product,
        string  $prompt,
        string  $resolution,
        string  $aspectRatio,
        int     $count       = 1,
        array   $extraPayload = []
    ): array {
        $models  = $this->buildPriorityList($product->primary_model, $product->fallback_models);
        $timeout = $product->timeout ?: $this->timeout;

        return $this->tryModelsInOrder($models, $timeout, function (string $modelId) use ($prompt, $resolution, $aspectRatio, $count, $extraPayload) {
            return $this->generateImageFromPrompt($modelId, $prompt, $resolution, $aspectRatio, $count, $extraPayload);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // ویرایش تصویر آپلودی کاربر (image editing pipeline)
    // ─────────────────────────────────────────────────────────────
    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array
    {
        $models  = $this->buildPriorityList($product->primary_model, $product->fallback_models);
        $timeout = $product->timeout ?: $this->timeout;

        return $this->tryModelsInOrder($models, $timeout, function (string $modelId, int $timeout) use ($prompt, $base64Images) {
            return $this->callEditApi($modelId, $prompt, $base64Images);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // تولید تصویر با AiModel (برای تست از پنل ادمین)
    // ─────────────────────────────────────────────────────────────
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

    // ─────────────────────────────────────────────────────────────
    // ویرایش مستقیم مدل با تصویر ورودی (آزمایشگاه پنل)
    // ─────────────────────────────────────────────────────────────
    public function editImageWithModel(string $modelId, string $prompt, array $base64Images, ?int $timeout = null): array
    {
        return $this->callEditApi($modelId, $prompt, $base64Images);
    }

    // ─────────────────────────────────────────────────────────────
    // اجرای ویرایش تصویر (input-based editing برای gpt-image-1)
    // ─────────────────────────────────────────────────────────────
    protected function callEditApi(
        string $modelId,
        string $prompt,
        array $base64Images,
        int $n = 1,
        string $size = '1024x1024',
        string $quality = 'high'
    ): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('LIARA_AI_API_KEY تنظیم نشده است.');
        }

        if (empty($base64Images)) {
            throw new Exception('برای ویرایش تصویر، حداقل یک تصویر مرجع لازم است.');
        }

        Log::info('LiaraAI: ارسال درخواست ویرایش تصویر', [
            'model'        => $modelId,
            'images_count' => count($base64Images),
        ]);

        $request = Http::withToken($this->apiKey)->timeout($this->timeout);
        $fieldName = count($base64Images) === 1 ? 'image' : 'image[]';

        foreach ($base64Images as $index => $image) {
            [$mime, $binary] = $this->decodeImage($image);
            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
            $request = $request->attach(
                $fieldName,
                $binary,
                "reference-{$index}.{$extension}",
                ['Content-Type' => $mime]
            );
        }

        $response = $request->post($this->baseUrl . '/images/edits', [
            'model'   => $modelId,
            'prompt'  => $prompt,
            'n'       => $n,
            'size'    => $size,
            'quality' => $quality,
        ]);

        if ($response->failed()) {
            $body = $response->body();
            Log::error('LiaraAI: خطا در ویرایش تصویر', ['status' => $response->status(), 'body' => $body]);
            throw new Exception("Liara AI HTTP {$response->status()}: {$body}");
        }

        $json = $response->json();

        if (empty($json['data'])) {
            throw new Exception('پاسخ نامعتبر از لیارا دریافت شد: ' . json_encode($json));
        }

        return $json;
    }

    /**
     * ورودی سازگار با OpenRouter را به data URLهای قابل ارسال به endpoint ویرایش تبدیل می‌کند.
     */
    protected function extractInputImages(array $extraPayload): array
    {
        $references = $extraPayload['input_references'] ?? $extraPayload['input'] ?? [];

        return array_values(array_filter(array_map(function ($reference) {
            if (is_string($reference)) {
                return $reference;
            }

            return $reference['image_url']['url'] ?? $reference['url'] ?? null;
        }, is_array($references) ? $references : [])));
    }

    /**
     * @return array{0:string,1:string}
     */
    protected function decodeImage(string $image): array
    {
        $mime = 'image/jpeg';
        $encoded = $image;

        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', $image, $matches)) {
            $mime = strtolower($matches[1]);
            $encoded = $matches[2];
        }

        $binary = base64_decode(preg_replace('/\s+/', '', $encoded), true);
        if ($binary === false || $binary === '') {
            throw new Exception('تصویر مرجع Base64 معتبر نیست.');
        }

        return [$mime, $binary];
    }

    // ─────────────────────────────────────────────────────────────
    // تبدیل aspectRatio به size استاندارد OpenAI
    // ─────────────────────────────────────────────────────────────
    protected function resolveSize(string $aspectRatio): string
    {
        // نسبت‌های عمودی (پرتره)
        $portrait = ['9:16', '2:3', '3:4', '4:7'];
        if (in_array($aspectRatio, $portrait, true)) {
            return '1024x1536';
        }

        // نسبت‌های افقی (منظره)
        $landscape = ['16:9', '3:2', '4:3', '7:4'];
        if (in_array($aspectRatio, $landscape, true)) {
            return '1536x1024';
        }

        // مربع یا هر چیز دیگر
        return '1024x1024';
    }

    // ─────────────────────────────────────────────────────────────
    // تبدیل resolution به quality OpenAI
    // ─────────────────────────────────────────────────────────────
    protected function resolveQuality(string $resolution): string
    {
        return match ($resolution) {
            '4K', '2K' => 'high',
            '1K'       => 'medium',
            default    => 'medium',
        };
    }

    // ─────────────────────────────────────────────────────────────
    // POST به API لیارا
    // ─────────────────────────────────────────────────────────────
    protected function post(string $path, array $payload): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post($this->baseUrl . $path, $payload);
    }

    // ─────────────────────────────────────────────────────────────
    // ساخت لیست اولویت مدل‌ها (اصلی + fallback)
    // ─────────────────────────────────────────────────────────────
    protected function buildPriorityList(?string $primary, $fallbacks): array
    {
        $list = is_array($fallbacks)
            ? $fallbacks
            : (json_decode($fallbacks ?? '[]', true) ?? []);

        return array_values(array_filter(array_merge([$primary], $list)));
    }

    // ─────────────────────────────────────────────────────────────
    // تلاش به ترتیب اولویت — اولین موفق برگردانده می‌شود
    // ─────────────────────────────────────────────────────────────
    protected function tryModelsInOrder(array $models, int $timeout, callable $callApi): array
    {
        if (empty($models)) {
            throw new Exception('هیچ مدل لیارایی برای این محصول تنظیم نشده است.');
        }

        $attempts = [];

        foreach ($models as $idx => $modelId) {
            $start = microtime(true);
            try {
                $response   = $callApi($modelId, $timeout);
                $durationMs = round((microtime(true) - $start) * 1000);

                $attempts[] = ['model' => $modelId, 'priority' => $idx + 1, 'status' => 'success', 'duration_ms' => $durationMs];
                Log::info('LiaraAI: مدل موفق شد', ['model' => $modelId, 'duration_ms' => $durationMs]);

                return ['model' => $modelId, 'data' => $response, 'attempts' => $attempts];

            } catch (Exception $e) {
                $attempts[] = ['model' => $modelId, 'priority' => $idx + 1, 'status' => 'failed', 'error' => $e->getMessage()];
                Log::warning('LiaraAI: مدل شکست خورد، رفتن به بعدی', ['model' => $modelId, 'error' => $e->getMessage()]);
                continue;
            }
        }

        Log::error('LiaraAI: همه مدل‌ها شکست خوردند', ['attempts' => $attempts]);
        throw new Exception('هیچ‌کدام از مدل‌های لیارا پاسخ ندادند. لطفاً بعداً تلاش کنید.');
    }
}
