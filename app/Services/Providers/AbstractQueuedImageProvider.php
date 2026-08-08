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
        $reservation = $this->limits->reserve($model, $estimatedCost, $outputs);

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
            $status = $this->getGenerationStatus($model, $requestId);
            if (in_array($status['status'], ['completed', 'failed', 'canceled'], true)) {
                $this->persistNormalizedRequest($model, $status, $requestId);
                return $status;
            }
            usleep(750000);
        } while (microtime(true) < $deadline);

        throw new RuntimeException("زمان انتظار provider {$this->provider()} تمام شد. شناسه درخواست: {$requestId}");
    }

    protected function buildInput(AiModel $model, string $prompt, string $resolution, string $aspectRatio, int $count, array $extraPayload): array
    {
        $capabilities = $this->getModelCapabilities($model);
        $allowed = array_values(array_unique(array_merge(['prompt'], (array) ($capabilities['allowed_inputs'] ?? []))));
        $fieldMap = (array) ($capabilities['field_map'] ?? []);
        $defaults = $model->default_parameters;
        $defaults = is_array($defaults) ? $defaults : (json_decode((string) $defaults, true) ?: []);
        $input = [];

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
        $put('negative_prompt', $extraPayload['negative_prompt'] ?? null);
        $put('seed', isset($extraPayload['seed']) ? (int) $extraPayload['seed'] : null);
        $put('steps', isset($extraPayload['steps']) ? (int) $extraPayload['steps'] : null);
        $put('guidance_scale', isset($extraPayload['guidance_scale']) ? (float) $extraPayload['guidance_scale'] : null);
        $put('num_images', max(1, min(10, $count)));
        $put('output_format', $extraPayload['output_format'] ?? null);

        [$width, $height] = $this->dimensionsFor($model, $aspectRatio);
        $put('width', $width);
        $put('height', $height);
        $put('image_size', $extraPayload['image_size'] ?? null);

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
        if (in_array($aspectRatio, ['9:16', '2:3', '3:4'], true)) return [$height, $width];
        if (in_array($aspectRatio, ['16:9', '3:2', '4:3'], true)) return [$width, $height];
        return [$width, $height];
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

    public function estimateCost(AiModel $model, array $payload = []): ?float
    {
        $pricing = is_array($model->pricing_config) ? $model->pricing_config : [];
        $unitPrice = $pricing['unit_price'] ?? $pricing['price'] ?? $model->cost_per_generation_usd;
        if (!is_numeric($unitPrice)) return null;
        return round((float) $unitPrice * max(1, (int) ($payload['n'] ?? 1)), 6);
    }
}
