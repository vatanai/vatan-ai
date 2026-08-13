<?php

namespace App\Services;

use App\Models\AiModel;

class ProviderPricingService
{
    public function __construct(private FalAiBillingService $falBilling) {}

    public function estimate(AiModel $model, int $count = 1, bool $allowLiveLookup = true): array
    {
        $count = max(1, $count);
        // صفحه‌ی ثبت محصول صدها مدل را برای انتخاب در آزمایشگاه آماده می‌کند.
        // نباید برای هر مدل در زمان رندر صفحه به API قیمت‌گذاری Fal.ai وصل شویم؛
        // این کار برای هر مدل یک درخواست شبکه‌ی جدا می‌ساخت و در لوکال به 504 می‌رسید.
        // قیمت واقعی همچنان در زمان اجرای آزمایش/تولید، با مقدار پیش‌فرض این متد، خوانده می‌شود.
        if ($allowLiveLookup && $model->provider === 'fal') {
            $live = $this->falBilling->pricing((string) $model->externalModelId());
            if (($live['available'] ?? false) === true) {
                return ['usd' => round((float) $live['unit_price'] * $count, 6), 'source' => $live['source'], 'unit' => $live['unit'], 'endpoint_id' => $live['endpoint_id']];
            }
        }

        $config = is_array($model->pricing_config) ? $model->pricing_config : [];
        $fallback = $config['unit_price'] ?? $config['price'] ?? $model->cost_per_generation_usd;
        if (is_numeric($fallback) && (float) $fallback > 0) {
            return ['usd' => round((float) $fallback * $count, 6), 'source' => 'قیمت ذخیره‌شده‌ی معتبر مدل (fallback)', 'unit' => $config['unit'] ?? 'unit'];
        }

        return ['usd' => null, 'source' => $model->provider === 'fal' ? 'قیمت Fal.ai در دسترس نیست' : 'قیمت گزارش نشده', 'unit' => null];
    }
}
