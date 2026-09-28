<?php

namespace App\Services;

use App\Models\ModelQualityPreset;
use App\Models\Product;

/** سیاست واحد تلاش مجدد ساخت تصویر برای پیش‌فرض‌های کیفیت. */
class ImageGenerationRetryPolicy
{
    public const DEFAULT_PRIMARY_MAX_ATTEMPTS = 3;

    public const DEFAULT_FALLBACK_MAX_ATTEMPTS = 1;

    public const DEFAULT_DELAYS_SECONDS = [5, 10];

    public function defaults(): array
    {
        return [
            'enabled' => true,
            'primary_max_attempts' => self::DEFAULT_PRIMARY_MAX_ATTEMPTS,
            'primary_retry_delays_seconds' => self::DEFAULT_DELAYS_SECONDS,
            'fallback_max_attempts' => self::DEFAULT_FALLBACK_MAX_ATTEMPTS,
        ];
    }

    /** تنظیم پیش‌فرض را بدون حذف گزینه‌های دیگر پیکربندی اضافه و نرمال می‌کند. */
    public function withDefaults(array $configuration): array
    {
        $configuration['image_retry_policy'] = $this->normalize(
            (array) ($configuration['image_retry_policy'] ?? []),
            $this->defaults(),
        );

        return $configuration;
    }

    /**
     * پیش‌فرض متصل همیشه منبع نهایی سیاست است؛ برای محصولات سفارشی فقط
     * سیاستی اجرا می‌شود که صریحاً روی خود محصول ذخیره شده باشد.
     */
    public function forProduct(Product $product): array
    {
        $configuration = (array) ($product->model_configuration ?? []);
        $presetKey = trim((string) ($configuration['quality_preset_key'] ?? ''));

        if ($presetKey !== '' && $presetKey !== 'custom') {
            try {
                $preset = ModelQualityPreset::query()
                    ->where('preset_key', $presetKey)
                    ->first();
                if ($preset) {
                    $presetConfiguration = (array) $preset->configuration;

                    return $this->normalize(
                        (array) ($presetConfiguration['image_retry_policy'] ?? []),
                        $this->defaults(),
                    );
                }
            } catch (\Throwable) {
                // هنگام اجرای migration یا تست‌های ایزوله، سیاست ذخیره‌شده‌ی
                // خود محصول همچنان قابل استفاده است.
            }
        }

        $stored = (array) ($configuration['image_retry_policy'] ?? []);
        if ($stored === []) {
            return [
                'enabled' => false,
                'primary_max_attempts' => 1,
                'primary_retry_delays_seconds' => [],
                'fallback_max_attempts' => 1,
            ];
        }

        return $this->normalize($stored, $this->defaults());
    }

    private function normalize(array $policy, array $fallback): array
    {
        $enabled = filter_var(
            $policy['enabled'] ?? $fallback['enabled'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        );
        $primaryAttempts = max(1, min(3, (int) ($policy['primary_max_attempts'] ?? $fallback['primary_max_attempts'])));
        $fallbackAttempts = max(1, min(3, (int) ($policy['fallback_max_attempts'] ?? $fallback['fallback_max_attempts'])));
        $delays = collect((array) ($policy['primary_retry_delays_seconds'] ?? $fallback['primary_retry_delays_seconds']))
            ->filter(fn ($delay): bool => is_numeric($delay))
            ->map(fn ($delay): int => max(0, min(60, (int) $delay)))
            ->values()
            ->all();

        $requiredDelays = max(0, $primaryAttempts - 1);
        while (count($delays) < $requiredDelays) {
            $delays[] = self::DEFAULT_DELAYS_SECONDS[count($delays)]
                ?? self::DEFAULT_DELAYS_SECONDS[array_key_last(self::DEFAULT_DELAYS_SECONDS)];
        }

        return [
            'enabled' => $enabled ?? (bool) $fallback['enabled'],
            'primary_max_attempts' => $primaryAttempts,
            'primary_retry_delays_seconds' => array_slice($delays, 0, $requiredDelays),
            'fallback_max_attempts' => $fallbackAttempts,
        ];
    }
}
