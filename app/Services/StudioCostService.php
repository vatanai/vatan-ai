<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\FinanceExchangeRate;
use App\Models\Product;
use App\Models\StudioCostRule;
use App\Models\StudioPricingSetting;
use Illuminate\Support\Facades\Schema;

class StudioCostService
{
    public const CREDIT_VALUE_TOMAN = 1000;

    public function quote(Product $product, array $options = [], ?AiModel $model = null): array
    {
        $mediaType = (string) ($options['media_type'] ?? ($product->isVideoProduct() ? 'video' : 'image'));
        $resolution = (string) ($options['resolution'] ?? '');
        $aspectRatio = (string) ($options['aspect_ratio'] ?? '');
        $duration = isset($options['duration']) ? (int) $options['duration'] : null;
        $count = max(1, min(6, (int) ($options['count'] ?? 1)));
        $unitUsd = $this->modelUnitPrice($model, $mediaType, $resolution, $duration, $aspectRatio);
        $pricingSource = $unitUsd !== null ? data_get($model?->pricing_config, 'source', 'کاتالوگ مدل') : null;
        if (($unitUsd === null || $unitUsd <= 0) && $model) {
            $live = app(ProviderPricingService::class)->estimate($model, 1, true, [
                'duration' => $duration,
                'resolution' => $resolution,
                'aspect_ratio' => $aspectRatio,
                'count' => $count,
            ]);
            if (is_numeric($live['usd'] ?? null) && (float) $live['usd'] > 0) {
                $unitUsd = (float) $live['usd'];
                if ($mediaType === 'video' && str_contains(strtolower((string) ($live['unit'] ?? '')), 'second')) {
                    $unitUsd *= max(1, min(15, (int) $duration));
                }
                $pricingSource = $live['source'] ?? 'قیمت لحظه‌ای پروایدر';
            }
        }
        $costKnown = $unitUsd !== null && $unitUsd > 0;
        $rate = $this->latestExchangeRate();
        $costTomanPerOutput = $costKnown ? $unitUsd * $rate : null;
        $profitPercent = $this->profitPercent($mediaType);
        $sellPricePerOutput = $costTomanPerOutput === null ? null : $costTomanPerOutput * (1 + ($profitPercent / 100));
        $creditsPerOutput = $sellPricePerOutput === null ? null : max(1, (int) ceil($sellPricePerOutput / self::CREDIT_VALUE_TOMAN));
        $totalCostToman = $costTomanPerOutput === null ? null : $costTomanPerOutput * ($mediaType === 'image' ? $count : 1);
        $totalSellPrice = $sellPricePerOutput === null ? null : $sellPricePerOutput * ($mediaType === 'image' ? $count : 1);

        return [
            'configured' => $costKnown,
            'cost_known' => $costKnown,
            'rule_id' => null,
            'model_id' => $model?->openrouter_model_id,
            'provider' => $model?->provider,
            'pricing_source' => $costKnown ? ($pricingSource ?: 'کاتالوگ مدل') : 'قیمت مدل ثبت نشده است',
            'credits_per_output' => $creditsPerOutput,
            'credits' => $creditsPerOutput === null ? null : $creditsPerOutput * ($mediaType === 'image' ? $count : 1),
            'base_cost_usd' => $unitUsd === null ? null : round($unitUsd, 6),
            'exchange_rate_toman' => round($rate, 2),
            'cost_toman' => $totalCostToman === null ? null : round($totalCostToman, 2),
            'profit_percent' => round($profitPercent, 2),
            'profit_toman' => $totalSellPrice === null ? null : round($totalSellPrice - $totalCostToman, 2),
            'sell_price_toman' => $totalSellPrice === null ? null : round($totalSellPrice, 2),
            'credit_value_toman' => self::CREDIT_VALUE_TOMAN,
            'count' => $count,
        ];
    }

    public function modelUnitPrice(?AiModel $model, string $mediaType = 'image', string $resolution = '', ?int $duration = null, string $aspectRatio = ''): ?float
    {
        if (!$model) return null;

        $config = (array) ($model->pricing_config ?? []);
        $normalizedResolution = $this->normalizeResolution($resolution);
        $tierMap = (array) ($config['resolution_tiers'] ?? $config['tiers'] ?? []);
        foreach ([$resolution, $normalizedResolution, strtoupper($resolution)] as $key) {
            if ($key !== '' && is_numeric($tierMap[$key] ?? null) && (float) $tierMap[$key] > 0) {
                return $this->applyDuration((float) $tierMap[$key], $model, $mediaType, $duration, $config);
            }
        }
        // وقتی provider برای رزولوشن‌ها tier صریح داده است، استفاده از
        // unit_price پایه برای tier ناشناخته می‌تواند کمتر از بهای واقعی تمام
        // شود؛ در این حالت قیمت را ناشناخته اعلام می‌کنیم تا ساخت با مبلغ
        // اشتباه انجام نشود.
        if ($tierMap !== [] && $resolution !== '') {
            return null;
        }

        $durationMap = (array) ($config['duration_prices'] ?? []);
        if ($mediaType === 'video' && $duration && is_numeric($durationMap[(string) $duration] ?? null) && (float) $durationMap[(string) $duration] > 0) {
            return (float) $durationMap[(string) $duration];
        }

        $unitPrice = $config['unit_price'] ?? $config['price'] ?? $model->cost_per_generation_usd;
        if (!is_numeric($unitPrice) || (float) $unitPrice <= 0) return null;

        return $this->applyUnitPricing((float) $unitPrice, $model, $mediaType, $resolution, $duration, $config);
    }

    private function applyUnitPricing(float $price, AiModel $model, string $mediaType, string $resolution, ?int $duration, array $config): float
    {
        if ($mediaType === 'image') {
            $unit = strtolower((string) ($config['unit'] ?? $config['pricing_type'] ?? $model->pricing_type ?? ''));
            if (str_contains($unit, 'megapixel')) {
                $price *= $this->resolutionMegapixels($resolution);
            }
        }

        return $this->applyDuration($price, $model, $mediaType, $duration, $config);
    }

    private function resolutionMegapixels(string $resolution): float
    {
        return match (strtolower(trim($resolution))) {
            '4k', '2160', '2160p' => 16.0,
            '2k', '1440', '1440p' => 4.0,
            '1080', '1080p' => 2.25,
            '1k', '1024', '1024p', '' => 1.0,
            default => 1.0,
        };
    }

    private function applyDuration(float $price, AiModel $model, string $mediaType, ?int $duration, array $config): float
    {
        if ($mediaType !== 'video' || !$duration) return $price;
        $unit = strtolower((string) ($config['unit'] ?? $config['pricing_type'] ?? $model->pricing_type ?? ''));
        $isPerSecond = str_contains($unit, 'second') || str_contains($unit, 'per_second')
            || ($model->provider === 'openrouter' && !str_contains($unit, 'generation'));

        return $isPerSecond ? $price * max(1, min(15, $duration)) : $price;
    }

    private function normalizeResolution(string $resolution): string
    {
        return match (strtolower(trim($resolution))) {
            '2160', '2160p', '4k' => '4K',
            '1440', '1440p', '2k' => '2K',
            '1080', '1080p' => '1080p',
            '720', '720p' => '720p',
            '480', '480p' => '480p',
            default => trim($resolution),
        };
    }

    public function pricingSettings(): StudioPricingSetting
    {
        return StudioPricingSetting::query()->firstOrCreate(['id' => 1], [
            'image_profit_percent' => 10,
            'video_profit_percent' => 10,
        ]);
    }

    public function profitPercent(string $mediaType): float
    {
        $settings = $this->pricingSettings();
        return max(0, (float) ($mediaType === 'video' ? $settings->video_profit_percent : $settings->image_profit_percent));
    }

    public function matchingRule(Product $product, string $mediaType, string $resolution = '', string $aspectRatio = '', ?int $duration = null, ?AiModel $model = null): ?StudioCostRule
    {
        if (!Schema::hasTable('studio_cost_rules')) {
            return null;
        }

        $rules = StudioCostRule::query()
            ->where('is_active', true)
            ->where('media_type', $mediaType)
            ->where(fn ($query) => $query->whereNull('product_id')->orWhere('product_id', $product->id))
            ->where(fn ($query) => $query->whereNull('resolution')->orWhere('resolution', $resolution))
            ->where(fn ($query) => $query->whereNull('aspect_ratio')->orWhere('aspect_ratio', $aspectRatio))
            ->where(fn ($query) => $query->whereNull('duration_seconds')->orWhere('duration_seconds', $duration))
            ->where(fn ($query) => $query->whereNull('provider')->orWhere('provider', $model?->provider ?? (string) $product->ai_provider))
            ->where(fn ($query) => $query->whereNull('ai_model_id')->orWhere('ai_model_id', $model?->id))
            ->get();

        return $rules->sortByDesc(function (StudioCostRule $rule): int {
            return ($rule->product_id ? 32 : 0)
                + ($rule->ai_model_id ? 16 : 0)
                + ($rule->resolution ? 8 : 0)
                + ($rule->aspect_ratio ? 4 : 0)
                + ($rule->duration_seconds ? 2 : 0)
                + ($rule->provider ? 1 : 0);
        })->first();
    }

    private function latestExchangeRate(): float
    {
        if (Schema::hasTable('finance_exchange_rates') && Schema::hasColumn('finance_exchange_rates', 'rate_to_toman')) {
            $rate = FinanceExchangeRate::query()->where('currency', 'USD')->latest('rate_date')->value('rate_to_toman');
            if ((float) $rate > 0) {
                return (float) $rate;
            }
        }

        return 100000;
    }
}
