<?php

namespace App\Services;

use App\Contracts\AiImageProviderInterface;
use App\Models\AiModel;
use App\Models\Product;
use App\Support\ProviderStatus;
use Illuminate\Support\Facades\Log;
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
        $this->assertHasEnabledProvider();
        return $this->tryProductModelsAcrossProviders($product, function ($service, $candidate) use ($prompt, $resolution, $aspectRatio, $count, $extraPayload) {
            return $service->generateForProduct($candidate, $prompt, $resolution, $aspectRatio, $count, $extraPayload);
        });
    }

    public function editImageForProduct(Product $product, string $prompt, array $base64Images = []): array
    {
        $this->assertHasEnabledProvider();
        return $this->tryProductModelsAcrossProviders($product, function ($service, $candidate) use ($prompt, $base64Images) {
            return $service->editImageForProduct($candidate, $prompt, $base64Images);
        });
    }

    private function tryProductModelsAcrossProviders(Product $product, callable $run): array
    {
        $models = array_values(array_filter(array_merge([(string) $product->primary_model], (array) $product->fallback_models)));
        $providers = array_values(array_merge([(string) $product->ai_provider], (array) $product->fallback_model_providers));
        $lastError = null;
        $disabledProviders = [];
        $attemptedPaidProviders = [];
        $exhaustedProviders = [];
        $attemptedRoutes = [];

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
            // یک اقدام کاربر نباید چند endpoint از Fal را پشت‌سرهم شارژ کند.
            // در صورت خطای Fal فقط provider دیگری می‌تواند fallback شود.
            if ($provider === 'fal' && isset($attemptedPaidProviders[$provider])) {
                Log::warning('AiProviderRouter: skipped repeated paid Fal fallback', [
                    'product_id' => $product->id,
                    'model' => $modelId,
                ]);
                continue;
            }
            if ($provider === 'fal') {
                $attemptedPaidProviders[$provider] = true;
            }
            $candidate = $product->replicate();
            $candidate->primary_model = $modelId;
            $candidate->ai_provider = $provider;
            $candidate->fallback_models = [];
            $attemptedRoutes[$provider . '|' . $modelId] = true;
            try {
                return $run($this->serviceForModelId($modelId, $provider), $candidate);
            } catch (\Throwable $error) {
                $lastError = $error;
                if ($this->isProviderExhaustionError($error)) {
                    $exhaustedProviders[$provider] = $this->providerFailureReason($error);
                }
                Log::warning('AiProviderRouter: product model failed, trying next provider/model', [
                    'product_id' => $product->id, 'model' => $modelId, 'provider' => $provider,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        // بعضی محصولات قدیمی از یک migration آزمایشی آمده‌اند و primary و
        // fallback هر دو روی یک provider ثبت شده‌اند. در این وضعیت، بعد از
        // شکست حساب/سرویس، از بین مدل‌های فعال و هم‌نوع OpenRouter یک مسیر
        // پشتیبان واقعی پیدا کن؛ این مسیر فقط برای همان درخواست ساخته می‌شود
        // و تنظیم ذخیره‌شده‌ی محصول را تغییر نمی‌دهد.
        foreach ($this->runtimeOpenRouterFallbacks($product, $attemptedRoutes) as $fallback) {
            $modelId = $fallback['model'];
            $provider = 'openrouter';
            if (isset($exhaustedProviders[$provider])) continue;

            $candidate = $product->replicate();
            $candidate->primary_model = $modelId;
            $candidate->ai_provider = $provider;
            $candidate->fallback_models = [];
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
            || str_contains($message, 'http 5')
            || str_contains($message, 'insufficient credit')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'quota')
            || str_contains($message, 'api_key');
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
