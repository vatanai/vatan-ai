<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * هزینه‌ی ساخت هر محصول را از نتیجه‌ی آزمایش همان محصول استخراج می‌کند.
 * مبلغ تومان عمداً از نرخ فعلی دلار محاسبه می‌شود تا با نرخ روز هماهنگ بماند.
 */
class ProductLabCostService
{
    private const GRADES = [
        'standard' => [
            'label' => 'استاندارد',
            'legacy_keys' => ['standard', 'economic'],
        ],
        'professional' => [
            'label' => 'حرفه‌ای',
            'legacy_keys' => ['professional'],
        ],
        'best' => [
            'label' => 'بهترین خروجی',
            'legacy_keys' => ['best'],
        ],
    ];

    /**
     * @param  float  $exchangeRateIrr  نرخ یک دلار به ریال
     * @param  Collection<int, AiModel>  $modelCatalog
     * @param  Collection<int, \App\Models\Order>  $userOrders  آخرین ساخت‌های موفق کاربر برای همین محصول
     */
    public function summarize(
        Product $product,
        float $exchangeRateIrr,
        Collection $modelCatalog = new Collection(),
        Collection $userOrders = new Collection(),
    ): array
    {
        $experiment = $product->latestLabExperiment;
        $runs = $experiment?->runs ?? collect();
        $configuration = (array) ($product->model_configuration ?? []);
        $modelsByKey = $modelCatalog->keyBy(fn (AiModel $model) => $this->modelKey($model->provider, $model->openrouter_model_id));

        return collect(self::GRADES)->map(function (array $grade, string $key) use ($runs, $configuration, $exchangeRateIrr, $modelsByKey, $userOrders) {
            $run = $this->bestRunForGrade($runs, $grade['legacy_keys']);
            $userOrder = $this->bestUserOrderForGrade($userOrders, $key);
            $configured = $this->configuredModel($configuration, $key);
            $runUsd = $run ? $this->runCostUsd($run) : null;
            $runModel = $run?->aiModel;
            $configuredModel = $configured ? $modelsByKey->get($this->modelKey($configured['provider'], $configured['model_id'])) : null;
            // اگر اجرای آزمایش قیمت واقعی/تخمینی نداشت، از مدل تنظیم‌شده‌ی
            // همان گرید برای نمایش مبلغ حدودی استفاده می‌کنیم.
            $estimateModel = $configuredModel && $this->modelCostUsd($configuredModel) !== null
                ? $configuredModel
                : $runModel;

            $userCost = $userOrder ? $this->orderCost($userOrder) : null;
            $modelUsd = $this->modelCostUsd($estimateModel);
            [$usd, $source, $origin, $displayModel, $provider, $sourceLabel, $sourceDetail, $modelSource] = $this->selectCostSource(
                $userCost,
                $run,
                $runUsd,
                $runModel,
                $modelUsd,
                $estimateModel,
            );
            $hasValue = $usd !== null && $usd > 0 && $exchangeRateIrr > 0;

            return [
                'key' => $key,
                'label' => $grade['label'],
                'usd' => $hasValue ? round($usd, 6) : null,
                'toman' => $hasValue ? (int) round($usd * $exchangeRateIrr / 10) : null,
                // این کلیدها برای سازگاری گزارش‌های قبلی حفظ شده‌اند؛ جزئیات
                // دقیق‌تر منبع در origin/source_label/tone قرار می‌گیرد.
                'source' => $source,
                'origin' => $origin,
                'source_label' => $hasValue ? $sourceLabel : 'قیمت در دسترس نیست',
                'source_detail' => $hasValue ? $sourceDetail : 'برای این گرید هنوز هزینه‌ی قابل اتکایی ثبت نشده است.',
                'model_source' => $modelSource,
                'tone' => $hasValue ? match ($source) {
                    'actual' => 'actual',
                    'verified' => 'verified',
                    default => $origin === 'model' ? 'model' : 'estimated',
                } : 'unavailable',
                'model' => $displayModel instanceof AiModel ? $displayModel->shortDisplayName() : ($displayModel ?: null),
                'provider' => $provider,
                'status' => $hasValue ? 'available' : 'unavailable',
            ];
        })->values()->all();
    }

    /**
     * اولویت قیمت در لیست محصولات:
     * هزینه‌ی واقعی ساخت اخیر کاربر، هزینه‌ی واقعی آزمایش مدیر، قیمت رسمی مدل
     * در ساخت موفق کاربر، قیمت تخمینی ساخت اخیر کاربر، قیمت تخمینی آزمایش و
     * در نهایت قیمت ثبت‌شده‌ی مدل.
     */
    private function selectCostSource(
        ?array $userCost,
        mixed $run,
        ?float $runUsd,
        ?AiModel $runModel,
        ?float $modelUsd,
        ?AiModel $estimateModel,
    ): array {
        $runIsActual = $runUsd !== null && $run && $this->hasActualCost($run);
        $runModelDisplay = $run?->alias ?: $runModel;

        if ($userCost && $userCost['actual']) {
            return [
                $userCost['usd'], 'actual', 'user', $userCost['model'], $userCost['provider'],
                'آخرین ساخت موفق کاربر · هزینه ثبت‌شده‌ی provider',
                'این مبلغ از آخرین ساخت موفق کاربر برای همین سطح کیفیت و درخواست‌های ثبت‌شده‌ی provider محاسبه شده است.',
                null,
            ];
        }

        if ($runIsActual) {
            return [
                $runUsd, 'actual', 'lab', $runModelDisplay, $run?->provider ?: $runModel?->provider,
                'آخرین آزمایش مدیر · هزینه ثبت‌شده‌ی provider',
                'این مبلغ از آخرین آزمایش تکمیل‌شده‌ی مدیر سایت برای همین گرید کیفیت خوانده شده است.',
                null,
            ];
        }

        // Replicate معمولاً مبلغ نهایی هر prediction را در پاسخ برنمی‌گرداند.
        // وقتی ساخت کاربر موفق بوده و مبلغ از قیمت رسمی مدل snapshot شده است،
        // آن را «تأییدشده» نشان می‌دهیم؛ این با هزینه‌ی واقعی billing یکی نیست
        // و عمداً برچسب جدا دارد تا شفافیت مالی حفظ شود.
        if ($userCost && $userCost['verified']) {
            return [
                $userCost['usd'], 'verified', 'user', $userCost['model'], $userCost['provider'],
                'آخرین ساخت موفق کاربر · قیمت رسمی مدل',
                'این مبلغ از قیمت رسمی ثبت‌شده‌ی مدل در یک ساخت موفق گرفته شده است؛ provider مبلغ billing نهایی این درخواست را گزارش نکرده است.',
                $userCost['pricing_source'],
            ];
        }

        if ($userCost) {
            return [
                $userCost['usd'], 'estimated', 'user', $userCost['model'], $userCost['provider'],
                'آخرین ساخت کاربر · هزینه تخمینی',
                'ساخت موفق ثبت شده است، اما provider مبلغ نهایی را گزارش نکرده؛ بنابراین مقدار تخمینی نمایش داده می‌شود.',
                null,
            ];
        }

        if ($runUsd !== null) {
            return [
                $runUsd, 'estimated', 'lab', $runModelDisplay, $run?->provider ?: $runModel?->provider,
                'آزمایش مدیر · هزینه تخمینی',
                'آزمایش تکمیل شده است، اما هزینه‌ی واقعی provider در آن ذخیره نشده و مقدار تخمینی استفاده شده است.',
                null,
            ];
        }

        if ($modelUsd !== null) {
            $modelPricingSource = trim((string) data_get($estimateModel?->getAttribute('lab_pricing'), 'source', ''));
            return [
                $modelUsd, 'estimated', 'model', $estimateModel, $estimateModel?->provider,
                'قیمت ثبت‌شده‌ی مدل · تخمینی',
                $modelPricingSource !== ''
                    ? 'قیمت از کاتالوگ مدل/تنظیمات مدیر خوانده شده است: ' . $modelPricingSource
                    : 'برای این مدل قیمت پایه ثبت شده، اما هنوز با آزمایش یا ساخت واقعی تأیید نشده است.',
                $modelPricingSource !== '' ? $modelPricingSource : null,
            ];
        }

        return [null, null, null, $estimateModel ?: $runModel, $estimateModel?->provider ?: $runModel?->provider, null, null, null];
    }

    private function bestUserOrderForGrade(Collection $orders, string $grade): mixed
    {
        return $orders
            ->filter(fn ($order) => $this->orderGradeKey($order) === $grade)
            ->sortByDesc(fn ($order) => optional($order->completed_at ?: $order->created_at)?->timestamp ?? 0)
            ->first(fn ($order) => $this->orderCost($order) !== null);
    }

    private function orderGradeKey(mixed $order): ?string
    {
        $quality = data_get($order->input_payload, 'main_quality');
        if (array_key_exists($quality, self::GRADES)) return $quality;

        return match ((string) $order->model_tier_key) {
            'free', 'economy' => 'standard',
            'pro' => 'professional',
            'business' => 'best',
            default => null,
        };
    }

    private function orderCost(mixed $order): ?array
    {
        $requests = $order->relationLoaded('providerRequests')
            ? $order->providerRequests->filter(fn ($request) => $request->status === 'completed')
            : collect();
        if ($requests->isEmpty()) return null;

        $actual = (float) $requests->sum(fn ($request) => max(0, (float) $request->actual_cost_usd));
        $estimated = (float) $requests->sum(fn ($request) => max(0, (float) $request->estimated_cost_usd));
        $usd = $actual > 0 ? $actual : ($estimated > 0 ? $estimated : null);
        if ($usd === null) return null;

        $models = $requests->map(fn ($request) => $request->aiModel)->filter();
        $model = $models->map(fn ($aiModel) => $aiModel->shortDisplayName())->filter()->unique()->implode('، ');
        $provider = $models->map(fn ($aiModel) => $aiModel->provider)->filter()->unique()->implode('، ');
        $verifiedModel = $models->first(fn (AiModel $aiModel) => $this->hasOfficialPricing($aiModel));
        $pricingSource = $verifiedModel ? $this->officialPricingSource($verifiedModel) : null;

        return [
            'usd' => $usd,
            'actual' => $actual > 0,
            'verified' => $actual <= 0 && $estimated > 0 && $verifiedModel !== null,
            'pricing_source' => $pricingSource,
            'model' => $model !== '' ? $model : ($order->ai_model ?: null),
            'provider' => $provider !== '' ? $provider : ($order->ai_provider ?: null),
        ];
    }

    /** قیمت رسمی مدل برای ساخت موفق قابل اتکاست، اما billing واقعی نیست. */
    private function hasOfficialPricing(AiModel $model): bool
    {
        $config = (array) ($model->pricing_config ?? []);
        $source = strtolower(trim((string) ($config['source'] ?? '')));
        $priceSource = strtolower(trim((string) ($config['price_source'] ?? '')));

        return str_contains($source, 'official')
            || str_contains($priceSource, 'official')
            || in_array($source, ['replicate', 'fal.ai', 'openrouter'], true);
    }

    private function officialPricingSource(AiModel $model): ?string
    {
        $config = (array) ($model->pricing_config ?? []);
        return trim((string) ($config['price_source'] ?? $config['source'] ?? '')) ?: null;
    }

    private function bestRunForGrade(Collection $runs, array $gradeKeys): mixed
    {
        return $runs
            ->filter(fn ($run) => in_array((string) $run->grade_key, $gradeKeys, true))
            ->sortBy(fn ($run) => [
                $run->status === 'completed' ? 0 : 1,
                $run->is_selected ? 0 : 1,
                $run->rank ?? $run->attempt_order ?? PHP_INT_MAX,
            ])
            ->first();
    }

    private function configuredModel(array $configuration, string $grade): ?array
    {
        $selection = data_get($configuration, "quality_models.{$grade}.primary", []);
        $modelId = trim((string) data_get($selection, 'model_id', ''));
        $provider = trim((string) data_get($selection, 'provider', ''));

        return $modelId !== '' && $provider !== '' ? ['model_id' => $modelId, 'provider' => $provider] : null;
    }

    private function runCostUsd(mixed $run): ?float
    {
        $actual = (float) $run->actual_cost_usd;
        if ($actual > 0) return $actual;

        $estimated = (float) $run->estimated_cost_usd;
        return $estimated > 0 ? $estimated : null;
    }

    private function hasActualCost(mixed $run): bool
    {
        return (float) $run->actual_cost_usd > 0;
    }

    private function modelCostUsd(?AiModel $model): ?float
    {
        if (!$model) return null;

        $liveEstimate = data_get($model->getAttribute('lab_pricing'), 'usd');
        if (is_numeric($liveEstimate) && (float) $liveEstimate > 0) return (float) $liveEstimate;

        $stored = (float) $model->cost_per_generation_usd;
        if ($stored > 0) return $stored;

        $pricing = (array) ($model->pricing_config ?? []);
        $fallback = (float) ($pricing['unit_price'] ?? $pricing['price'] ?? 0);

        return $fallback > 0 ? $fallback : null;
    }

    private function modelKey(?string $provider, ?string $modelId): string
    {
        return strtolower(trim((string) $provider) . '|' . trim((string) $modelId));
    }
}
