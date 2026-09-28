<?php

namespace App\Services;

use App\Contracts\AiImageProviderInterface;
use App\Models\AiModel;
use App\Models\Product;
use App\Support\ProviderStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Exception;

/**
 * ══════════════════════════════════════════════════════
 * AiProviderRouter — روتر هوشمند سرویس‌های هوش مصنوعی
 * ──────────────────────────────────────────────────────
 * این کلاس جایگزین مستقیم OpenRouterService در تمام
 * کنترلرهاست (فقط ۲ خط کد هر کنترلر عوض می‌شود).
 *
 * نحوه عمل:
 * - بر اساس فیلد `provider` مدل انتخابی، درخواست را
 *   به OpenRouter یا providerهای تصویر هدایت می‌کند.
 * - OpenRouterService کاملاً دست‌نخورده باقی می‌ماند.
 *
 * نگه‌داری اضافه — کلید مرکزی ProviderStatus:
 * - اگر ادمین OpenRouter را از پنل «خاموش» کرده باشد،
 *   درخواست‌های تولید تصویر با شناسه‌های OpenRouter به‌طور
 *   هوشمند به یک provider فعال با همان شناسه مدل منتقل می‌شوند.
 * - این تنها لایه‌ی روتر است که به این تنظیمات آگاه است؛
 *   providerهای غیرفعال هیچ‌گاه فراخوانی نمی‌شوند.
 * ══════════════════════════════════════════════════════
 */
class AiProviderRouter
{
    public function __construct(
        protected OpenRouterService $openRouter,
        protected ?\App\Services\Providers\FalImageProvider $fal = null,
        protected ?\App\Services\Providers\ReplicateImageProvider $replicate = null,
        protected ?\App\Services\Providers\OpenRouterVideoProvider $openRouterVideo = null
    ) {}

    // ─────────────────────────────────────────────────────────────
    // انتخاب سرویس بر اساس شناسه مدل
    // ─────────────────────────────────────────────────────────────
    protected function serviceForModelId(string $modelId, ?string $preferredProvider = null): AiImageProviderInterface
    {
        $provider = in_array($preferredProvider, ProviderStatus::PROVIDERS, true)
            ? $preferredProvider
            : null;

        // وقتی provider روی خود محصول ذخیره شده، هیچ lookup مبهمی لازم نیست.
        // فقط محصولات قدیمیِ بدون ai_provider از جدول مدل‌ها استنتاج می‌شوند.
        if ($provider === null) {
            $model = $this->findModel($modelId);
            $provider = $model?->provider ?? 'openrouter';
        }

        // اگر provider خاموش است، از راه فال‌بک استفاده کن
        if (!ProviderStatus::isEnabled($provider)) {
            $fallback = $this->fallbackServiceFor($modelId, $provider);
            if ($fallback) {
                return $fallback;
            }
        }

        return $this->serviceForProvider($provider);
    }

    protected function serviceForProvider(string $provider): AiImageProviderInterface
    {
        return match ($provider) {
            'fal' => $this->fal ?: app(\App\Services\Providers\FalImageProvider::class),
            'replicate' => $this->replicate ?: app(\App\Services\Providers\ReplicateImageProvider::class),
            default => $this->openRouter,
        };
    }

    public function videoServiceFor(string $provider): AiImageProviderInterface
    {
        return $provider === 'openrouter'
            ? ($this->openRouterVideo ?: app(\App\Services\Providers\OpenRouterVideoProvider::class))
            : $this->serviceForProvider($provider);
    }

    protected function findModel(string $modelId, ?string $provider = null): ?AiModel
    {
        return AiModel::query()
            ->when($provider, fn ($query) => $query->where('provider', $provider))
            ->where(function ($query) use ($modelId) {
                $query->where('openrouter_model_id', $modelId)
                    ->orWhere('external_model_id', $modelId);
            })
            ->first();
    }

    /**
     * وقتی provider مدل خاموش است، سعی کن یک provider جایگزین فعال پیدا کنی.
     * اولویت: نگاشت مستقیم شناسه مدل به provider فعال (اگر مدلی با همان
     * openrouter_model_id در provider فعال وجود داشته باشد → همان سرویس)،
     */
    protected function fallbackServiceFor(string $modelId, string $disabledProvider): ?object
    {
        // ۱) آیا مدلی با همین شناسه در provider فعال داریم؟
        $active = AiModel::where('openrouter_model_id', $modelId)
            ->where('is_active', true)
            ->whereIn('provider', ProviderStatus::enabled())
            ->first();

        if ($active) {
            Log::info('AiProviderRouter: auto-fallback to enabled provider (same model id)', [
                'model'    => $modelId,
                'from'     => $disabledProvider,
                'to'       => $active->provider,
            ]);
            return $this->serviceForProvider($active->provider);
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    // انتخاب سرویس بر اساس مدل اصلی یک محصول
    // ─────────────────────────────────────────────────────────────
    protected function serviceForProduct(Product $product): AiImageProviderInterface
    {
        return $this->serviceForModelId(
            (string) $product->primary_model,
            $product->ai_provider
        );
    }

    /**
     * بررسی می‌کند که آیا حداقل یک provider فعال داریم؛ در غیر این صورت
     * خطای فارسی واضح می‌دهیم که تنظیمات پنل ادمین باید بازبینی شود.
     */
    protected function assertHasEnabledProvider(): void
    {
        if (empty(ProviderStatus::enabled())) {
            throw new Exception('هیچ سرویس هوش مصنوعی در پنل ادمین فعال نیست. لطفاً از تنظیمات مدل‌های هوش مصنوعی حداقل یک provider را روشن کنید.');
        }
    }

    private function assertOpenRouterEnabledForImageProduct(): void
    {
        if (! ProviderStatus::isEnabled('openrouter')) {
            throw new Exception('ساخت محصولات تصویری فقط از طریق OpenRouter انجام می‌شود؛ اتصال OpenRouter در پنل ادمین فعال نیست.');
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Proxy متدها — امضا دقیقاً همان OpenRouterService است
    // ─────────────────────────────────────────────────────────────

    public function generateForProduct(
        Product $product,
        string  $prompt,
        string  $resolution,
        string  $aspectRatio,
        int     $count        = 1,
        array   $extraPayload = []
    ): array {
        $this->assertOpenRouterEnabledForImageProduct();
        return $this->tryProductModelsAcrossProviders($product, $resolution, $extraPayload, function ($service, $candidate) use ($prompt, $resolution, $aspectRatio, $count, $extraPayload) {
            return $service->generateForProduct($candidate, $prompt, $resolution, $aspectRatio, $count, $extraPayload);
        });
    }

    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array
    {
        $this->assertOpenRouterEnabledForImageProduct();
        return $this->tryProductModelsAcrossProviders($product, null, [], function ($service, $candidate) use ($prompt, $base64Images) {
            return $service->editImageForProduct($candidate, $prompt, $base64Images);
        });
    }

    private function tryProductModelsAcrossProviders(Product $product, ?string $resolution, array $extraPayload, callable $run): array
    {
        $models = array_values(array_filter(array_merge([(string) $product->primary_model], (array) $product->fallback_models)));
        $providers = array_values(array_merge([(string) $product->ai_provider], (array) $product->fallback_model_providers));
        $requestDeadline = microtime(true) + max(
            60,
            (int) config('services.openrouter.image_request_budget', 240)
        );

        // بعضی محصولات قدیمی هنوز روی Riverflow Pro مانده‌اند. برای ساخت
        // معمول کاربر، مسیر سریع OpenRouter را فقط در همان درخواست جلو می
        // آوریم و تنظیم ذخیره‌شدهٔ محصول/مسیرهای حرفه‌ای را تغییر ندهیم.
        if ($resolution !== null && $this->shouldPreferFastImageRoute($product, $resolution, $extraPayload)) {
            $routes = [];
            foreach ($models as $index => $modelId) {
                $routes[] = [
                    'model' => $modelId,
                    'provider' => $providers[$index] ?? $this->findModel($modelId)?->provider,
                ];
            }

            $requiresImageInput = !empty($extraPayload['input_references']);
            $fastCandidates = match ((string) ($routes[0]['model'] ?? '')) {
                'sourceful/riverflow-v2-pro' => ['openai/gpt-image-1-mini', 'sourceful/riverflow-v2-fast'],
                'sourceful/riverflow-v2.5-pro' => ['openai/gpt-image-1-mini', 'sourceful/riverflow-v2.5-fast'],
                default => [],
            };

            foreach ($fastCandidates as $fastModel) {
                $catalogModel = $this->findModel($fastModel, 'openrouter');
                if (!$catalogModel?->is_active) continue;
                if ($requiresImageInput && !$catalogModel->supports_image_input) continue;

                $routes = array_values(array_filter(
                    $routes,
                    fn (array $route): bool => $route['model'] !== $fastModel
                ));
                array_unshift($routes, ['model' => $fastModel, 'provider' => 'openrouter']);
                $models = array_column($routes, 'model');
                $providers = array_column($routes, 'provider');

                Log::notice('AiProviderRouter: fast OpenRouter lane selected', [
                    'product_id' => $product->id,
                    'source_model' => $product->primary_model,
                    'model' => $fastModel,
                    'resolution' => $resolution,
                    'has_input_reference' => $requiresImageInput,
                ]);
                break;
            }
        }

        $lastError = null;
        $disabledProviders = [];
        $exhaustedProviders = [];
        $attemptedRoutes = [];
        $retryPolicy = app(ImageGenerationRetryPolicy::class)->forProduct($product);
        $routeAttemptLimits = collect($models)
            ->map(fn ($_model, int $index): int => ! $retryPolicy['enabled']
                ? 1
                : ($index === 0
                    ? (int) $retryPolicy['primary_max_attempts']
                    : (int) $retryPolicy['fallback_max_attempts']))
            ->all();

        foreach ($models as $index => $modelId) {
            $provider = $providers[$index] ?? $this->findModel($modelId)?->provider;
            if (!$provider) continue;

            if (!ProviderStatus::isEnabled($provider)) {
                $disabledProviders[] = $provider;
                continue;
            }
            if (isset($exhaustedProviders[$provider])) {
                Log::warning('AiProviderRouter: skipped provider after account/availability failure', [
                    'product_id' => $product->id,
                    'model' => $modelId,
                    'provider' => $provider,
                    'reason' => $exhaustedProviders[$provider],
                ]);
                continue;
            }
            $attemptedRoutes[$provider . '|' . $modelId] = true;
            $routeMaxAttempts = max(1, (int) ($routeAttemptLimits[$index] ?? 1));
            $routeError = null;

            for ($attempt = 1; $attempt <= $routeMaxAttempts; $attempt++) {
                $candidate = $product->replicate();
                $candidate->primary_model = $modelId;
                $candidate->ai_provider = $provider;
                $candidate->fallback_models = [];

                $remainingAttempts = $this->plannedAttemptsRemaining($routeAttemptLimits, $index, $attempt);
                $remainingDelays = $index === 0
                    ? array_sum(array_slice(
                        (array) $retryPolicy['primary_retry_delays_seconds'],
                        max(0, $attempt - 1),
                    ))
                    : 0;
                if (! $this->configureImageAttemptTimeout(
                    $candidate,
                    $requestDeadline,
                    $remainingAttempts,
                    $remainingDelays,
                )) {
                    break 2;
                }

                try {
                    return $run($this->serviceForModelId($modelId, $provider), $candidate);
                } catch (\Throwable $error) {
                    $routeError = $lastError = $error;
                    $canRetrySameModel = $retryPolicy['enabled']
                        && $attempt < $routeMaxAttempts
                        && $this->isRetryableImageError($error);
                    $delay = $canRetrySameModel
                        ? (int) ($retryPolicy['primary_retry_delays_seconds'][$attempt - 1] ?? 0)
                        : 0;

                    Log::warning('AiProviderRouter: image model attempt failed', [
                        'product_id' => $product->id,
                        'model' => $modelId,
                        'provider' => $provider,
                        'attempt' => $attempt,
                        'max_attempts' => $routeMaxAttempts,
                        'retryable' => $canRetrySameModel,
                        'retry_delay_seconds' => $delay,
                        'message' => $error->getMessage(),
                    ]);

                    if (! $canRetrySameModel || ! $this->canWaitForRetry($requestDeadline, $delay)) {
                        break;
                    }

                    Sleep::for($delay)->seconds();
                }
            }

            if ($routeError && $this->isProviderExhaustionError($routeError)) {
                $exhaustedProviders[$provider] = $this->providerFailureReason($routeError);
            }
            Log::warning('AiProviderRouter: model exhausted, trying configured fallback', [
                'product_id' => $product->id,
                'model' => $modelId,
                'provider' => $provider,
                'attempts' => $routeMaxAttempts,
                'message' => $routeError?->getMessage(),
            ]);
        }

        // وقتی مسیر محصول قفل شده (strict_model_priority) — یعنی روتر اجازه
        // ندارد یک مدل تصادفی/گران‌تر جایگزین کند — اما مدل اصلی از طریق
        // OpenRouter بوده و فقط fallback غیر-OpenRouter (مثلاً Fal) به‌دلیل
        // اتمام اعتبار/سرویس شکست خورده، یک بار دیگر همان مدل اصلی را از
        // طریق OpenRouter امتحان کن. این یک مدل جدید یا گران‌تر انتخاب
        // نمی‌کند — دقیقاً همان مدلی که قرار بود اجرا شود را، وقتی خود
        // OpenRouter در فهرست providerهای ازکارافتاده نیست (یعنی شکست اول
        // می‌تواند موقتی/گذرا بوده باشد)، یک شانس دوم می‌دهد.
        $primaryProvider = $providers[0] ?? null;
        $primaryModelId = $models[0] ?? null;
        if ($product->getAttribute('strict_model_priority')
            && $lastError
            && ! $retryPolicy['enabled']
            && $primaryProvider === 'openrouter'
            && $primaryModelId
            && count($models) > 1
            && !isset($exhaustedProviders['openrouter'])
        ) {
            $candidate = $product->replicate();
            $candidate->primary_model = $primaryModelId;
            $candidate->ai_provider = 'openrouter';
            $candidate->fallback_models = [];
            if ($this->configureImageAttemptTimeout($candidate, $requestDeadline)) {
                try {
                    Log::notice('AiProviderRouter: retrying original OpenRouter model after fallback exhaustion', [
                        'product_id' => $product->id,
                        'model' => $primaryModelId,
                    ]);

                    return $run($this->serviceForModelId($primaryModelId, 'openrouter'), $candidate);
                } catch (\Throwable $error) {
                    $lastError = $error;
                    if ($this->isProviderExhaustionError($error)) {
                        $exhaustedProviders['openrouter'] = $this->providerFailureReason($error);
                    }
                    Log::warning('AiProviderRouter: OpenRouter retry after fallback exhaustion failed', [
                        'product_id' => $product->id,
                        'model' => $primaryModelId,
                        'message' => $error->getMessage(),
                    ]);
                }
            }
        }

        // بعضی محصولات قدیمی از یک migration آزمایشی آمده‌اند و primary و
        // fallback هر دو روی یک provider ثبت شده‌اند. در این وضعیت، بعد از
        // شکست حساب/سرویس، از بین مدل‌های فعال و هم‌نوع OpenRouter یک مسیر
        // پشتیبان واقعی پیدا کن؛ این مسیر فقط برای همان درخواست ساخته می‌شود
        // و تنظیم ذخیره‌شده‌ی محصول را تغییر نمی‌دهد.
        // ترتیب صریح کیفیت فقط وقتی باید جلوی fallback پویا را بگیرد که
        // واقعاً یک مسیر OpenRouter در همان ترتیب امتحان شده باشد. بعضی
        // محصولات قدیمی strict هستند اما تمام مسیرهایشان Fal/Replicate است؛
        // خالی‌شدن حساب آن provider نباید کل محصول را از کار بیندازد.
        $hasAttemptedOpenRouter = collect(array_keys($attemptedRoutes))
            ->contains(fn (string $route): bool => str_starts_with($route, 'openrouter|'));
        $runtimeFallbacks = $product->getAttribute('strict_model_priority') && $hasAttemptedOpenRouter
            ? []
            : $this->runtimeOpenRouterFallbacks($product, $attemptedRoutes);

        foreach ($runtimeFallbacks as $fallback) {
            $modelId = $fallback['model'];
            $provider = 'openrouter';
            if (isset($exhaustedProviders[$provider])) continue;

            $candidate = $product->replicate();
            $candidate->primary_model = $modelId;
            $candidate->ai_provider = $provider;
            $candidate->fallback_models = [];
            if (! $this->configureImageAttemptTimeout($candidate, $requestDeadline)) {
                break;
            }
            try {
                Log::notice('AiProviderRouter: using runtime OpenRouter fallback', [
                    'product_id' => $product->id,
                    'model' => $modelId,
                    'provider' => $provider,
                    'source_provider' => $fallback['source_provider'],
                ]);

                return $run($this->serviceForModelId($modelId, $provider), $candidate);
            } catch (\Throwable $error) {
                $lastError = $error;
                if ($this->isProviderExhaustionError($error)) {
                    $exhaustedProviders[$provider] = $this->providerFailureReason($error);
                }
                Log::warning('AiProviderRouter: runtime OpenRouter fallback failed', [
                    'product_id' => $product->id,
                    'model' => $modelId,
                    'provider' => $provider,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        if ($lastError) throw $lastError;

        if ($disabledProviders) {
            $labels = collect($disabledProviders)->unique()->map(fn (string $provider): string => match ($provider) {
                'fal' => 'Fal.ai',
                'replicate' => 'Replicate',
                'openrouter' => 'OpenRouter',
                default => $provider,
            })->implode('، ');

            throw new Exception("مدل انتخاب‌شده از سرویس {$labels} است، اما این سرویس فعلاً در پنل غیرفعال است. پس از بررسی کلید و سلامت اتصال، provider را فعال کنید.");
        }

        throw new Exception('هیچ مدل فعال و قابل‌استفاده‌ای برای این محصول پیدا نشد.');
    }

    private function configureImageAttemptTimeout(
        Product $candidate,
        float $requestDeadline,
        int $plannedAttempts = 1,
        int $plannedDelaySeconds = 0,
    ): bool
    {
        $remainingSeconds = (int) floor($requestDeadline - microtime(true));
        if ($remainingSeconds < 15) {
            return false;
        }

        $configuredTimeout = (int) ($candidate->timeout ?: config('services.openrouter.timeout', 60));
        $attemptTimeout = max(15, (int) config('services.openrouter.image_attempt_timeout', 90));
        $availableForAttempts = max(15, $remainingSeconds - max(0, $plannedDelaySeconds));
        $fairAttemptBudget = max(15, (int) floor($availableForAttempts / max(1, $plannedAttempts)));
        $candidate->timeout = min($configuredTimeout, $attemptTimeout, $fairAttemptBudget);

        return true;
    }

    private function plannedAttemptsRemaining(array $routeAttemptLimits, int $routeIndex, int $currentAttempt): int
    {
        $remaining = max(1, (int) ($routeAttemptLimits[$routeIndex] ?? 1) - $currentAttempt + 1);
        foreach (array_slice($routeAttemptLimits, $routeIndex + 1) as $limit) {
            $remaining += max(1, (int) $limit);
        }

        return $remaining;
    }

    private function canWaitForRetry(float $requestDeadline, int $delaySeconds): bool
    {
        return ($requestDeadline - microtime(true)) >= ($delaySeconds + 15);
    }

    private function shouldPreferFastImageRoute(Product $product, string $resolution, array $extraPayload): bool
    {
        // این تصمیم نباید به فلگ فرم وابسته باشد؛ بعضی فرم‌های قدیمی یا
        // صفحات محصول آن فلگ را نمی‌فرستند. خودِ رزولوشن استاندارد معیار
        // معتبر مسیر سریع است و کیفیت‌های 2K/4K همچنان روی مدل حرفه‌ای می‌مانند.
        return in_array(strtolower(trim($resolution)), ['480', '480p', '512', '720', '720p', '1k', '1080', '1080p'], true);
    }

    /** @return array<int, array{model:string,source_provider:string}> */
    private function runtimeOpenRouterFallbacks(Product $product, array $attemptedRoutes): array
    {
        if (!ProviderStatus::isEnabled('openrouter')) return [];

        $primary = $this->findModel((string) $product->primary_model, (string) $product->ai_provider);
        $taskType = (string) ($primary?->task_type ?: 'text_to_image');
        if (!in_array($taskType, ['text_to_image', 'image_to_image', 'face_consistency'], true)) {
            return [];
        }

        try {
            $credentials = app(AiProviderCredentials::class)->for('openrouter');
            if (blank($credentials['api_key'] ?? null)) return [];
        } catch (\Throwable) {
            return [];
        }

        $targetGrade = $primary?->pricingGrade() ?? 3;
        $requiresImageInput = $taskType !== 'text_to_image'
            || (int) ($product->min_reference_images ?? 0) > 0;

        $preferredModels = [
            'google/gemini-3.1-flash-lite-image',
            'openai/gpt-image-1-mini',
            'google/gemini-2.5-flash-image',
        ];

        return AiModel::query()
            ->where('provider', 'openrouter')
            ->where('is_active', true)
            ->where('output_modality', 'image')
            ->where('task_type', 'text_to_image')
            ->when($requiresImageInput, fn ($query) => $query->where('supports_image_input', true))
            ->get()
            ->filter(function (AiModel $model) use ($attemptedRoutes): bool {
                $route = 'openrouter|' . (string) $model->openrouter_model_id;
                return (string) $model->openrouter_model_id !== '' && !isset($attemptedRoutes[$route]);
            })
            ->sortBy(fn (AiModel $model): array => [
                ($preferredIndex = array_search((string) $model->openrouter_model_id, $preferredModels, true)) === false
                    ? count($preferredModels)
                    : $preferredIndex,
                abs($model->pricingGrade() - $targetGrade),
                $model->lab_priority === null ? 999 : (int) $model->lab_priority,
                (int) $model->id,
            ], SORT_REGULAR)
            ->take(3)
            ->map(fn (AiModel $model): array => [
                'model' => (string) $model->openrouter_model_id,
                'source_provider' => (string) ($primary?->provider ?: $product->ai_provider),
            ])
            ->values()
            ->all();
    }

    private function isProviderExhaustionError(\Throwable $error): bool
    {
        $message = strtolower($error->getMessage());

        return str_contains($message, 'http 401')
            || str_contains($message, 'http 402')
            || str_contains($message, 'http 403')
            || str_contains($message, 'http 429')
            || str_contains($message, 'insufficient credit')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'quota')
            || str_contains($message, 'api_key');
    }

    private function isRetryableImageError(\Throwable $error): bool
    {
        $message = strtolower($error->getMessage());

        foreach ([
            'http 400', 'http 401', 'http 402', 'http 403', 'http 404', 'http 422',
            'insufficient credit', 'invalid api', 'api_key', 'unsupported', 'validation',
            'content policy', 'moderation', 'safety',
        ] as $permanentFailure) {
            if (str_contains($message, $permanentFailure)) {
                return false;
            }
        }

        foreach ([
            'http 408', 'http 409', 'http 425', 'http 429', 'http 5',
            'timeout', 'timed out', 'connection', 'temporar', 'unavailable',
            'server error', 'gateway', 'overloaded', 'rate limit', 'generation failed',
            'prediction failed', 'request failed', 'تکمیل نشد', 'ناموفق',
        ] as $transientFailure) {
            if (str_contains($message, $transientFailure)) {
                return true;
            }
        }

        return false;
    }

    private function providerFailureReason(\Throwable $error): string
    {
        $message = strtolower($error->getMessage());

        if (str_contains($message, '402') || str_contains($message, 'insufficient credit')) return 'اعتبار سرویس کافی نیست';
        if (str_contains($message, '429') || str_contains($message, 'rate limit')) return 'محدودیت نرخ سرویس';
        if (str_contains($message, '401') || str_contains($message, '403') || str_contains($message, 'api_key')) return 'کلید یا دسترسی سرویس معتبر نیست';
        return 'سرویس موقتاً در دسترس نیست';
    }

    public function generateImageFromPrompt(
        string $modelId,
        string $prompt,
        string $resolution   = '1K',
        string $aspectRatio  = '1:1',
        int    $n            = 1,
        array  $extraPayload = [],
        ?string $preferredProvider = null
    ): array {
        $this->assertHasEnabledProvider();
        return $this->serviceForModelId($modelId, $preferredProvider)->generateImageFromPrompt(
            $modelId, $prompt, $resolution, $aspectRatio, $n, $extraPayload
        );
    }

    public function generateImage(\App\Models\AiModel $aiModel, string $prompt, array $extraPayload = [], ?int $timeoutOverride = null): array
    {
        $this->assertHasEnabledProvider();
        return $this->serviceForProvider($aiModel->provider ?: 'openrouter')->generateImage(
            $aiModel, $prompt, $extraPayload, $timeoutOverride
        );
    }

    public function editImageWithModel(string $modelId, string $prompt, array $base64Images, ?int $timeout = null): array
    {
        $this->assertHasEnabledProvider();
        return $this->serviceForModelId($modelId)->editImageWithModel($modelId, $prompt, $base64Images, $timeout);
    }

    public function serviceFor(string $provider): AiImageProviderInterface
    {
        return $this->serviceForProvider($provider);
    }
}
