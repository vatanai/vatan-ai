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
 *   به LiaraAiService یا OpenRouterService هدایت می‌کند.
 * - OpenRouterService کاملاً دست‌نخورده باقی می‌ماند.
 * - LiaraAiService API لیارا را مدیریت می‌کند.
 *
 * نگه‌داری اضافه — کلید مرکزی ProviderStatus:
 * - اگر ادمین OpenRouter را از پنل «خاموش» کرده باشد،
 *   درخواست‌های تولید تصویر با شناسه‌های OpenRouter به‌طور
 *   هوشمند به معادل Liara تبدیل می‌شوند تا محصولات قدیمی
 *   نشکنند. اگر معادلی پیدا نشود، خطای واضح برمی‌گردد.
 * - این تنها لایه‌ی روتر است که به این تنظیمات آگاه است؛
 *   OpenRouterService و LiaraAiService دست‌نخورده‌اند.
 * ══════════════════════════════════════════════════════
 */
class AiProviderRouter
{
    public function __construct(
        protected OpenRouterService $openRouter,
        protected LiaraAiService    $liara,
        protected ?\App\Services\Providers\FalImageProvider $fal = null,
        protected ?\App\Services\Providers\ReplicateImageProvider $replicate = null
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
            'liara' => $this->liara,
            'fal' => $this->fal ?: throw new Exception('سرویس Fal.ai در روتر ثبت نشده است.'),
            'replicate' => $this->replicate ?: throw new Exception('سرویس Replicate در روتر ثبت نشده است.'),
            default => $this->openRouter,
        };
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
     * سپس اگر Liara فعال است، سرویس Liara را برگردان (وابسته به این‌که
     * مدل موجود در پرامپت واقعی روی Liara نیز پذیرفته شود).
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

        // ۲) اگر Liara تنها provider فعال باشد و شناسه مدل OpenAI/Google
        //    قابل قبول برای Liara به نظر برسد، از Liara استفاده کن.
        //    (Liara همان الگوی provider/model را می‌پذیرد)
        if (ProviderStatus::isEnabled('liara') && !ProviderStatus::isEnabled('openrouter')) {
            Log::info('AiProviderRouter: forced fallback to Liara (OpenRouter is disabled)', [
                'model' => $modelId,
                'from'  => $disabledProvider,
            ]);
            return $this->liara;
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

        foreach ($models as $index => $modelId) {
            $provider = $providers[$index] ?? $this->findModel($modelId)?->provider;
            if (!$provider || !ProviderStatus::isEnabled($provider)) continue;
            $candidate = $product->replicate();
            $candidate->primary_model = $modelId;
            $candidate->ai_provider = $provider;
            $candidate->fallback_models = [];
            try {
                return $run($this->serviceForModelId($modelId, $provider), $candidate);
            } catch (\Throwable $error) {
                $lastError = $error;
                Log::warning('AiProviderRouter: product model failed, trying next provider/model', [
                    'product_id' => $product->id, 'model' => $modelId, 'provider' => $provider,
                    'message' => $error->getMessage(),
                ]);
            }
        }

        throw $lastError ?: new Exception('هیچ مدل فعال و قابل‌استفاده‌ای برای این محصول پیدا نشد.');
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
