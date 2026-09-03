<?php

namespace App\Services\Providers;

use App\Contracts\AiAsyncImageProviderInterface;
use App\Contracts\AiImageProviderInterface;
use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\Product;
use App\Services\AiProviderCredentials;
use App\Services\AiProviderLimitService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

abstract class AbstractQueuedImageProvider implements AiImageProviderInterface, AiAsyncImageProviderInterface
{
    public function __construct(
        protected AiProviderCredentials $credentials,
        protected AiProviderLimitService $limits
    )
    {
    }

    abstract public function provider(): string;

    abstract protected function submitRemote(AiModel $model, array $input, ?string $webhookUrl): array;

    abstract protected function pollRemote(AiModel $model, string $requestId): array;

    abstract protected function cancelRemote(AiModel $model, string $requestId): array;

    protected function modelForId(string $modelId): AiModel
    {
        $model = AiModel::query()
            ->where('provider', $this->provider())
            ->where(function ($query) use ($modelId) {
                $query->where('external_model_id', $modelId)
                    ->orWhere('openrouter_model_id', $modelId);
            })
            ->first();

        if (!$model) {
            throw new RuntimeException("مدل {$modelId} برای سرویس {$this->provider()} در پنل ثبت نشده است.");
        }

        return $model;
    }

    public function validateModelConfiguration(AiModel $model): array
    {
        $issues = [];
        $credentials = $this->credentials->for($this->provider());

        if (blank($credentials['api_key'])) {
            $issues[] = 'کلید دسترسی provider تنظیم نشده است.';
        }
        if (blank($model->externalModelId())) {
            $issues[] = 'شناسه‌ی خارجی مدل خالی است.';
        }
        return ['valid' => empty($issues), 'issues' => $issues];
    }

    public function getModelCapabilities(AiModel $model): array
    {
        $schema = is_array($model->input_schema) ? $model->input_schema : [];
        $config = is_array($model->capability_config) ? $model->capability_config : [];
        $properties = data_get($schema, 'properties', []);

        if (empty($config['allowed_inputs']) && is_array($properties)) {
            $config['allowed_inputs'] = array_keys($properties);
        }

        return array_merge([
            'allowed_inputs' => ['prompt'],
            'field_map' => [],
            'supports_text_to_image' => true,
            'supports_image_to_image' => false,
        ], $config);
    }

    public function generateForProduct(Product $product, string $prompt, string $resolution, string $aspectRatio, int $count = 1, array $extraPayload = []): array
    {
        $models = $this->buildPriorityList($product->primary_model, $product->fallback_models);
        $lastError = null;

        foreach ($models as $modelId) {
            try {
                return $this->generateImageFromPrompt($modelId, $prompt, $resolution, $aspectRatio, $count, $extraPayload);
            } catch (\Throwable $error) {
                $lastError = $error;
                Log::warning('Queued AI provider model failed', [
                    'provider' => $this->provider(),
                    'model' => $modelId,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        throw $lastError ?: new RuntimeException('هیچ مدل فعالی برای این provider پیدا نشد.');
    }

    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array
    {
        return $this->generateForProduct($product, $prompt, '1K', '1:1', 1, [
            'input_references' => array_map(fn (string $image) => [
                'type' => 'image_url',
                'image_url' => ['url' => $image],
            ], $base64Images),
        ]);
    }

    public function generateImageFromPrompt(string $modelId, string $prompt, string $resolution = '1K', string $aspectRatio = '1:1', int $n = 1, array $extraPayload = []): array
    {
        $model = $this->modelForId($modelId);
        $result = $this->submitAndWait($model, $prompt, $this->buildInput($model, $prompt, $resolution, $aspectRatio, $n, $extraPayload), $extraPayload);

        if ($result['status'] !== 'completed') {
            throw new RuntimeException($result['error_message'] ?: 'تولید تصویر در provider تکمیل نشد.');
        }

        return $this->legacyResponse($result);
    }

    public function generateImage(AiModel $aiModel, string $prompt, array $extraPayload = [], ?int $timeoutOverride = null): array
    {
        $resolution = (string) ($extraPayload['resolution'] ?? '1K');
        $aspectRatio = (string) ($extraPayload['aspect_ratio'] ?? '1:1');
        $n = (int) ($extraPayload['n'] ?? 1);
        if ($timeoutOverride) {
            $extraPayload['timeout'] = $timeoutOverride;
        }

        $input = $this->buildInput($aiModel, $prompt, $resolution, $aspectRatio, $n, $extraPayload);
        $result = $this->submitAndWait($aiModel, $prompt, $input, $extraPayload);
        if ($result['status'] !== 'completed') {
            throw new RuntimeException($result['error_message'] ?: 'تولید تصویر در provider تکمیل نشد.');
        }

        return $this->legacyResponse($result);
    }

    public function editImageWithModel(string $modelId, string $prompt, array $base64Images, ?int $timeout = null): array
    {
        return $this->generateImageFromPrompt($modelId, $prompt, '1K', '1:1', 1, [
            'timeout' => $timeout,
            'input_references' => array_map(fn (string $image) => [
                'type' => 'image_url',
                'image_url' => ['url' => $image],
            ], $base64Images),
        ]);
    }

    public function submitGeneration(AiModel $model, string $prompt, array $payload = [], ?string $webhookUrl = null): array
    {
        $validation = $this->validateModelConfiguration($model);
        if (!$validation['valid']) {
            throw new RuntimeException(implode(' ', $validation['issues']));
        }

        $input = $payload['input'] ?? $this->buildInput(
            $model,
            $prompt,
            (string) ($payload['resolution'] ?? '1K'),
            (string) ($payload['aspect_ratio'] ?? '1:1'),
            (int) ($payload['n'] ?? 1),
            $payload
        );
        $providerSettings = $this->credentials->for($this->provider());
        $configuredWebhook = ($model->supports_webhook && $providerSettings['webhook_enabled'])
            ? $this->credentials->webhookUrl($this->provider())
            : null;
        $estimatedCost = $this->estimateCost($model, $payload);
        $outputs = max(1, (int) ($payload['n'] ?? 1));
        $reservation = $this->limits->reserve(
            $model,
            $estimatedCost,
            $outputs,
            is_numeric($payload['order_id'] ?? null) ? (int) $payload['order_id'] : null
        );

        try {
            $remote = $this->submitRemote($model, $input, $webhookUrl ?: $configuredWebhook);
            $requestId = (string) ($remote['request_id'] ?? $remote['id'] ?? '');
            if ($requestId === '') {
                throw new RuntimeException('provider شناسه‌ی درخواست برنگرداند.');
            }

            $reservation->update([
                'external_request_id' => $requestId,
                'status' => 'queued',
                'raw_response' => $remote,
                'submitted_at' => now(),
            ]);
        } catch (\Throwable $error) {
            // برای جلوگیری از عبور درخواست‌های خطادار از سقف عددی/هزینه‌ای،
            // رزرو تا پایان بازه باقی می‌ماند و وضعیت خطا می‌گیرد.
            $reservation->update([
                'status' => 'failed',
                'error_message' => \Illuminate\Support\Str::limit($error->getMessage(), 1000, ''),
                'completed_at' => now(),
            ]);
            throw $error;
        }

        return [
            'provider' => $this->provider(),
            'external_request_id' => $requestId,
            'status' => 'queued',
            'raw' => $remote,
        ];
    }

    public function getGenerationStatus(AiModel $model, string $requestId): array
    {
        return $this->normalizeResponse($model, $this->pollRemote($model, $requestId));
    }

    public function cancelGeneration(AiModel $model, string $requestId): array
    {
        return $this->normalizeResponse($model, $this->cancelRemote($model, $requestId));
    }

    protected function submitAndWait(AiModel $model, string $prompt, array $input, array $payload): array
    {
        $submitted = $this->submitGeneration($model, $prompt, ['input' => $input] + $payload);
        $requestId = $submitted['external_request_id'];
        $timeout = max(5, (int) ($payload['timeout'] ?? $this->credentials->for($this->provider())['timeout']));
        $deadline = microtime(true) + $timeout;

        do {
            try {
                $status = $this->getGenerationStatus($model, $requestId);
                if (in_array($status['status'], ['completed', 'failed', 'canceled'], true)) {
                    $this->persistNormalizedRequest($model, $status, $requestId);
                    return $status;
                }
            } catch (\Throwable $error) {
                // خطای موقت شبکه/پاسخ ۵xx در مرحلهٔ status یا دریافت فایل
                // نباید بلافاصله درخواست موفقِ در حال پردازش را ناموفق کند؛
                // در غیر این صورت Fal ممکن است تصویر را بسازد و برنامه هم
                // بدون تصویر، هزینهٔ واقعی آن را ثبت کند. polling تا پایان
                // مهلت ادامه پیدا می‌کند و خطاهای قطعی همان لحظه ثبت می‌شوند.
                if (!$this->isTransientProviderError($error) || microtime(true) >= $deadline) {
                    $this->markRequestAsFailed($requestId, $error->getMessage(), 'provider_status_error');
                    throw $error;
                }

                Log::warning('AI provider status temporarily unavailable; retrying', [
                    'provider' => $this->provider(),
                    'request_id' => $requestId,
                    'message' => $error->getMessage(),
                ]);
            }

            if (microtime(true) < $deadline) usleep(750000);
        } while (microtime(true) < $deadline);

        // یک بررسی نهایی قبل از لغو، race condition بین آخرین poll و اتمام
        // واقعی صف را کم می‌کند؛ مخصوصاً وقتی endpoint نتیجه دیرتر در دسترس
        // قرار می‌گیرد.
        try {
            $finalStatus = $this->getGenerationStatus($model, $requestId);
            if (in_array($finalStatus['status'], ['completed', 'failed', 'canceled'], true)) {
                $this->persistNormalizedRequest($model, $finalStatus, $requestId);
                return $finalStatus;
            }
        } catch (\Throwable $finalError) {
            Log::warning('AI provider final status check failed', [
                'provider' => $this->provider(),
                'request_id' => $requestId,
                'message' => $finalError->getMessage(),
            ]);
        }

        $message = "زمان انتظار provider {$this->provider()} تمام شد. شناسه درخواست: {$requestId}";
        $this->markRequestAsFailed($requestId, $message, 'provider_timeout');
        try {
            // لغو درخواست صف‌شده از هزینه‌ی ادامه‌دار بعد از خطای کاربر جلوگیری می‌کند.
            $this->cancelRemote($model, $requestId);
        } catch (\Throwable $cancelError) {
            Log::warning('AI provider request could not be cancelled after timeout', [
                'provider' => $this->provider(),
                'request_id' => $requestId,
                'error' => $cancelError->getMessage(),
            ]);
        }

        throw new RuntimeException($message);
    }

    protected function isTransientProviderError(\Throwable $error): bool
    {
        $message = strtolower($error->getMessage());

        return str_contains($message, 'connection')
            || str_contains($message, 'timed out')
            || str_contains($message, 'timeout')
            || str_contains($message, 'could not resolve')
            || str_contains($message, 'http 5');
    }

    protected function markRequestAsFailed(string $requestId, string $message, string $errorCode): void
    {
        AiProviderRequest::query()
            ->where('provider', $this->provider())
            ->where('external_request_id', $requestId)
            ->update([
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => \Illuminate\Support\Str::limit($message, 1000, ''),
                'completed_at' => now(),
            ]);
    }

    protected function buildInput(AiModel $model, string $prompt, string $resolution, string $aspectRatio, int $count, array $extraPayload): array
    {
        $capabilities = $this->getModelCapabilities($model);
        $allowed = array_values(array_unique(array_merge(['prompt'], (array) ($capabilities['allowed_inputs'] ?? []))));
        $fieldMap = (array) ($capabilities['field_map'] ?? []);
        $defaults = $model->default_parameters;
        $defaults = is_array($defaults) ? $defaults : (json_decode((string) $defaults, true) ?: []);
        $input = [];

        // بعضی catalogها قابلیت «عکس ورودی» را ثبت می‌کنند، اما فیلد مرجع را
        // داخل allowed_inputs نگه نمی‌دارند. فیلدهای مرجع را از schema هم پیدا
        // می‌کنیم تا مدل‌های image-to-image واقعاً تصویر انتخابی را دریافت کنند.
        $properties = (array) (
            data_get($model->input_schema, 'components.schemas.Input.properties')
            ?: data_get($model->input_schema, 'properties')
            ?: []
        );
        $referenceKeys = ['image_url', 'image_urls', 'image', 'images', 'input_image', 'input_images', 'source_image', 'subject_image', 'reference_image', 'reference_images'];
        $schemaReferenceKeys = array_values(array_intersect($referenceKeys, array_keys($properties)));
        if ($model->supports_image_input && $this->provider() === 'fal' && $schemaReferenceKeys === [] && empty($capabilities['reference_fields'])) {
            $schemaReferenceKeys = str_contains(strtolower((string) $model->externalModelId()), '/edit')
                ? ['image_urls']
                : ['image_url'];
        }
        // بعضی رکوردهای قدیمی capability_config ناقص دارند، درحالی‌که schema
        // خود مدل فیلدهایی مثل aspect_ratio را اعلام کرده است. برای جلوگیری از
        // ارسال ناقص ورودی، کنترل‌های استانداردی که واقعاً در schema هستند را
        // نیز مجاز می‌کنیم.
        $schemaControls = array_values(array_intersect([
            'aspect_ratio', 'quality', 'resolution', 'image_size', 'num_images',
            'number_of_images', 'num_outputs', 'output_format', 'input_fidelity',
            'background', 'moderation', 'limit_generations',
        ], array_keys($properties)));
        $allowed = array_values(array_unique(array_merge(
            $allowed,
            (array) ($capabilities['reference_fields'] ?? []),
            $schemaReferenceKeys,
            $schemaControls,
        )));

        foreach ($defaults as $key => $value) {
            if (in_array($key, $allowed, true)) {
                $input[$key] = $value;
            }
        }

        $put = function (string $canonical, mixed $value) use (&$input, $allowed, $fieldMap): void {
            if ($value === null || $value === '' || $value === []) return;
            $target = (string) ($fieldMap[$canonical] ?? $canonical);
            if (in_array($target, $allowed, true)) {
                $input[$target] = $value;
            }
        };

        $put('prompt', $prompt);
        // مقدار کیفیت/رزولوشن باید با نام فیلد واقعی مدل ارسال شود. برخی
        // مدل‌های Replicate مقدار را با «1K/2K/4K» می‌گیرند، درحالی‌که فرم
        // محصول برای کاربر «480/720/1080/1440/2160» نمایش می‌دهد.
        $normalizedResolution = match (strtoupper((string) $resolution)) {
            '480', '720' => '1K',
            '1080', '1440' => '2K',
            '2160' => '4K',
            default => $resolution,
        };
        // Seedream 4.5 به‌جای resolution از size استفاده می‌کند و خروجی 1K
        // ندارد؛ پایین‌ترین tier معتبر آن 2K است.
        $resolutionValue = (($fieldMap['resolution'] ?? null) === 'size')
            ? match ($normalizedResolution) {
                '4K' => '4K',
                default => '2K',
            }
            : $normalizedResolution;
        $put('resolution', $resolutionValue);
        // Replicate مدل GPT Image 2 کیفیت را به‌صورت enum می‌گیرد، نه
        // رزولوشن عددی یا مقدار «1K/2K/4K». نگاشت را اینجا انجام می‌دهیم تا
        // اجرای محصول و آزمایشگاه هر دو یک payload معتبر داشته باشند.
        $quality = $extraPayload['quality'] ?? $normalizedResolution;
        if ($model->provider === 'replicate' && $model->externalModelId() === 'openai/gpt-image-2') {
            $quality = match (strtoupper((string) $quality)) {
                '2160', '4K', 'HIGH' => 'high',
                '1080', '1440', '2K', 'MEDIUM' => 'medium',
                default => 'low',
            };
        }
        // endpointهای ویرایش OpenAI در Fal.ai کیفیت را با enum می‌گیرند، نه
        // مقدار ۱K/۲K/۴K. سطح کسب‌وکاری ۷۲۰ و ۱۰۸۰ متوسط و ۲۱۶۰ بالا است.
        if ($model->provider === 'fal' && str_contains($model->externalModelId(), 'gpt-image-') && str_ends_with($model->externalModelId(), '/edit')) {
            $quality = match (strtoupper((string) ($extraPayload['quality'] ?? $resolution))) {
                '480' => 'low',
                '2160', '4K', 'HIGH' => 'high',
                default => 'medium',
            };
        }
        $put('quality', $quality);
        $put('aspect_ratio', $this->compatibleAspectRatio($model, $aspectRatio));
        $put('negative_prompt', $extraPayload['negative_prompt'] ?? null);
        $put('seed', isset($extraPayload['seed']) ? (int) $extraPayload['seed'] : null);
        $put('steps', isset($extraPayload['steps']) ? (int) $extraPayload['steps'] : null);
        $put('guidance_scale', isset($extraPayload['guidance_scale']) ? (float) $extraPayload['guidance_scale'] : null);
        $put('num_images', max(1, min(10, $count)));
        // endpointهایی که این فلگ را در schema اعلام می‌کنند اجازه ندارند
        // بر اساس متن کاربر بیش از تعداد درخواستی خروجی پولی بسازند.
        $put('limit_generations', true);
        $put('output_format', $extraPayload['output_format'] ?? null);

        [$width, $height] = $this->dimensionsFor($model, $aspectRatio);
        $put('width', $width);
        $put('height', $height);
        $put('image_size', $extraPayload['image_size'] ?? $this->compatibleImageSize($model, $aspectRatio));
        $put('input_fidelity', $extraPayload['input_fidelity'] ?? (
            $model->supports_face_identity && !empty($extraPayload['input_references']) ? 'high' : null
        ));

        $references = $this->referenceUrls($extraPayload['input_references'] ?? $extraPayload['input'] ?? []);
        if ($references) {
            $put('image_url', $references[0]);
            $put('image_urls', $references);
            $put('image', $references[0]);
            $put('images', $references);
            $put('input_image', $references[0]);
            $put('input_images', $references);
            $put('source_image', $references[0]);
            $put('subject_image', $references[0]);
            $put('reference_image', $references[0]);
            $put('reference_images', $references);
        }

        return $input;
    }

    protected function referenceUrls(array $references): array
    {
        return array_values(array_filter(array_map(function ($reference) {
            if (is_string($reference)) return $reference;
            return data_get($reference, 'image_url.url') ?: ($reference['url'] ?? null);
        }, $references)));
    }

    protected function dimensionsFor(AiModel $model, string $aspectRatio): array
    {
        $width = max(1, (int) ($model->default_width ?: 1024));
        $height = max(1, (int) ($model->default_height ?: 1024));
        if ($aspectRatio === 'auto' || !str_contains($aspectRatio, ':')) return [$width, $height];

        [$ratioWidth, $ratioHeight] = array_pad(array_map('floatval', explode(':', $aspectRatio, 2)), 2, 1.0);
        if ($ratioWidth <= 0 || $ratioHeight <= 0) return [$width, $height];

        // طول ضلع بزرگ مدل حفظ می‌شود و ضلع دیگر از نسبت انتخاب‌شده به‌دست می‌آید؛
        // سپس هر دو مقدار به مضرب ۸ گرد می‌شوند تا با محدودیت رایج مدل‌ها سازگار باشند.
        $longSide = max($width, $height, 1024);
        if ($ratioWidth >= $ratioHeight) {
            $targetWidth = $longSide;
            $targetHeight = $longSide * $ratioHeight / $ratioWidth;
        } else {
            $targetHeight = $longSide;
            $targetWidth = $longSide * $ratioWidth / $ratioHeight;
        }

        $roundToEight = static fn (float $value): int => max(8, (int) (round($value / 8) * 8));
        return [$roundToEight($targetWidth), $roundToEight($targetHeight)];
    }

    protected function legacyResponse(array $normalized): array
    {
        $data = array_map(fn (array $item) => [
            'url' => $item['url'],
            'headers' => $item['headers'] ?? [],
        ], $normalized['output_urls'] ?? []);

        return [
            'model' => $normalized['provider'] . ':' . ($normalized['external_request_id'] ?? ''),
            'data' => $data,
            'usage' => [
                'provider' => $normalized['provider'],
                'request_id' => $normalized['external_request_id'] ?? null,
                'estimated_cost_usd' => $normalized['estimated_cost_usd'] ?? null,
                'actual_cost_usd' => $normalized['actual_cost_usd'] ?? null,
            ],
            'provider_metadata' => $normalized,
        ];
    }

    protected function persistNormalizedRequest(AiModel $model, array $normalized, string $requestId, bool $webhook = false): void
    {
        $values = [
            'ai_model_id' => $model->id,
            'status' => $normalized['status'],
            'output_urls' => $normalized['output_urls'] ?? [],
            'raw_response' => $normalized['provider_metadata'] ?? [],
            'estimated_cost_usd' => $normalized['estimated_cost_usd'] ?? null,
            'actual_cost_usd' => $normalized['actual_cost_usd'] ?? null,
            'error_code' => $normalized['error_code'] ?? null,
            'error_message' => $normalized['error_message'] ?? null,
            'completed_at' => in_array($normalized['status'], ['completed', 'failed', 'canceled'], true) ? now() : null,
        ];
        if ($webhook) $values['webhook_received_at'] = now();

        AiProviderRequest::updateOrCreate(
            ['provider' => $this->provider(), 'external_request_id' => $requestId],
            $values
        );
    }

    protected function buildPriorityList(?string $primary, mixed $fallbacks): array
    {
        $list = is_array($fallbacks) ? $fallbacks : (json_decode((string) $fallbacks, true) ?: []);
        return array_values(array_filter(array_merge([$primary], $list)));
    }

    protected function compatibleAspectRatio(AiModel $model, string $requested): string
    {
        // Replicate's GPT Image 2 has an explicit allow-list. Do not trust a
        // stale/partial catalog schema here: sending the UI default 4:5 to
        // this endpoint causes a 422 and previously made the whole lab fail.
        if ($model->provider === 'replicate' && $model->externalModelId() === 'openai/gpt-image-2') {
            $supported = [
                '1:1', '3:2', '2:3', '4:3', '3:4', '16:9', '9:16', 'auto',
                '1024x1024', '1536x1024', '1024x1536', '1536x1152', '1152x1536',
                '2048x2048', '2048x1152', '1152x2048', '3840x2160', '2160x3840',
            ];
            if (in_array($requested, $supported, true)) return $requested;
            $requestedRatio = $this->aspectRatioValue($requested);
            return collect($supported)
                ->reject(fn (string $candidate) => $candidate === 'auto')
                ->sortBy(fn (string $candidate) => abs(log(max(.0001, $requestedRatio) / max(.0001, $this->aspectRatioValue($candidate)))))
                ->first() ?: '3:4';
        }

        $properties = (array) (data_get($model->input_schema, 'components.schemas.Input.properties') ?: data_get($model->input_schema, 'properties') ?: []);
        $supported = array_values(array_filter((array) data_get($properties, 'aspect_ratio.enum', []), 'is_string'));
        if ($requested === 'auto') {
            return in_array('auto', $supported, true) ? 'auto' : ($supported[0] ?? 'auto');
        }
        if (!$supported) {
            // رابط کاربری نسبت‌های متداولی مثل ۴:۵ را هم می‌پذیرد، اما بعضی
            // مدل‌ها فقط نسبت‌های استاندارد خودشان را قبول می‌کنند. وقتی
            // schema مدل در کاتالوگ ناقص است، نزدیک‌ترین مقدار قراردادی را
            // بفرست تا درخواست با خطای 422 متوقف نشود.
            return match ($requested) {
                '4:5' => '3:4',
                '5:4' => '4:3',
                '4:6', '2:3' => '2:3',
                '6:4', '3:2' => '3:2',
                default => $requested,
            };
        }
        if (in_array($requested, $supported, true)) return $requested;

        $requestedRatio = $this->aspectRatioValue($requested);
        $best = $supported[0];
        $bestDistance = INF;
        foreach ($supported as $candidate) {
            $ratio = $this->aspectRatioValue($candidate);
            $distance = abs(log(max(.0001, $requestedRatio) / max(.0001, $ratio)));
            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $candidate;
            }
        }

        return $best;
    }

    protected function compatibleImageSize(AiModel $model, string $requested): ?string
    {
        if ($model->provider !== 'fal' || !str_contains($model->externalModelId(), 'gpt-image-')) {
            return null;
        }

        return match ($requested) {
            '1:1' => '1024x1024',
            '16:9', '3:2', '4:3' => '1536x1024',
            '4:5', '3:4', '2:3', '9:16' => '1024x1536',
            default => 'auto',
        };
    }

    private function aspectRatioValue(string $value): float
    {
        $value = trim(strtolower($value));
        if (str_contains($value, ':')) {
            [$width, $height] = array_pad(array_map('floatval', explode(':', $value, 2)), 2, 1.0);
        } elseif (str_contains($value, 'x')) {
            [$width, $height] = array_pad(array_map('floatval', explode('x', $value, 2)), 2, 1.0);
        } else {
            return 1.0;
        }

        return $height > 0 && $width > 0 ? $width / $height : 1.0;
    }

    public function estimateCost(AiModel $model, array $payload = []): ?float
    {
        return app(\App\Services\ProviderPricingService::class)
            ->estimate($model, max(1, (int) ($payload['n'] ?? 1)), true, $payload)['usd'];
    }
}
