<?php

namespace App\Services;

use App\Models\ModelTierDefault;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;

/** منبع واحد نگاشت پلن کاربر به مسیر اجرای مدل محصول. */
class ModelTierService
{
    public const DEFINITIONS = [
        'free' => ['grade' => 4, 'name' => 'رایگان', 'description' => 'مدل اقتصادی برای اعتبار هدیه و شروع کار'],
        'economy' => ['grade' => 3, 'name' => 'اقتصادی', 'description' => 'تعادل کیفیت و هزینه برای استفاده روزمره'],
        'pro' => ['grade' => 2, 'name' => 'حرفه‌ای', 'description' => 'کیفیت بالاتر برای خروجی‌های حرفه‌ای'],
        'business' => ['grade' => 1, 'name' => 'بیزینس', 'description' => 'بهترین مدل‌های فعال برای خروجی‌های کلیدی'],
    ];

    /**
     * سطحی که کاربر هنگام ساخت انتخاب می‌کند، از پلن خریداری‌شده مستقل است.
     * پلن فقط اعتبار و نرخ مؤثر را تعیین می‌کند؛ مدل را کیفیت خروجی تعیین می‌کند.
     */
    public const OUTPUT_QUALITY_DEFINITIONS = [
        'standard' => [
            'name' => 'استاندارد',
            'description' => 'متعادل برای ساخت روزمره',
            'credits' => 12,
            'tier_key' => 'economy',
            'grade' => 3,
        ],
        'professional' => [
            'name' => 'حرفه‌ای',
            'description' => 'جزئیات و پایداری بیشتر',
            'credits' => 20,
            'tier_key' => 'pro',
            'grade' => 2,
        ],
        'best' => [
            'name' => 'بهترین خروجی',
            'description' => 'بالاترین کیفیت برای نتیجه کلیدی',
            'credits' => 50,
            'tier_key' => 'business',
            'grade' => 1,
        ],
    ];

    public function tierKeyForUser(?User $user): string
    {
        $key = $user?->plan?->model_tier_key;

        return array_key_exists($key, self::DEFINITIONS) ? $key : 'free';
    }

    public function tierMeta(string $tierKey, ?Plan $plan = null): array
    {
        $base = self::DEFINITIONS[$tierKey] ?? self::DEFINITIONS['free'];

        return $base + [
            'key' => $tierKey,
            'plan_name' => $plan?->name ?: ('پلن ' . $base['name']),
        ];
    }

    /** کاربر رایگان فقط استاندارد و کاربر پلن‌دار هر سه کیفیت را می‌بیند. */
    public function outputQualityOptions(?User $user, ?Product $product = null): array
    {
        $hasPaidPlan = $this->hasPaidPlan($user);
        $productCosts = $product?->qualityCreditCosts() ?? [];

        return collect(self::OUTPUT_QUALITY_DEFINITIONS)
            ->map(function (array $quality, string $key) use ($hasPaidPlan) {
                return $quality + [
                    'key' => $key,
                    'available' => $hasPaidPlan || $key === 'standard',
                    'display_grade' => $quality['grade'],
                ];
            })
            ->map(function (array $quality) use ($productCosts): array {
                if (isset($productCosts[$quality['key']])) {
                    $quality['credits'] = (int) $productCosts[$quality['key']];
                }
                return $quality;
            })
            ->values()
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function resolveOutputQuality(?User $user, string $requestedKey, ?Product $product = null): ?array
    {
        $key = array_key_exists($requestedKey, self::OUTPUT_QUALITY_DEFINITIONS)
            ? $requestedKey
            : 'standard';
        $quality = self::OUTPUT_QUALITY_DEFINITIONS[$key];
        $hasPaidPlan = $this->hasPaidPlan($user);

        if (! $hasPaidPlan && $key !== 'standard') {
            return null;
        }

        $quality['credits'] = $product?->qualityCreditCost($key) ?? $quality['credits'];

        return $quality + [
            'key' => $key,
            'available' => true,
            // استاندارد رایگان از مدل‌های ویژه اعتبار هدیه استفاده می‌کند؛
            // کیفیت‌های بالاتر فقط بعد از فعال‌شدن پلن پرداختی قابل انتخاب‌اند.
            'execution_tier_key' => ($key === 'standard' && ! $hasPaidPlan)
                ? 'free'
                : $quality['tier_key'],
        ];
    }

    public function hasPaidPlan(?User $user): bool
    {
        return $user?->plan !== null
            && $user->plan->billing_type !== 'free'
            && (int) $user->plan->price > 0;
    }

    /**
     * نسخه اجرای محصول را برای سطح کاربر آماده می‌کند. در نبود پیکربندی جدید،
     * تنظیمات قدیمی محصول بدون تغییر استفاده می‌شوند.
     */
    public function executionProduct(Product $product, string $tierKey): Product
    {
        $tier = $product->modelTierConfiguration($tierKey);
        $primary = (array) ($tier['primary'] ?? []);
        $fallback = (array) ($tier['fallback'] ?? []);

        if (empty($primary['model_id']) || empty($primary['provider'])) {
            return $product;
        }

        $executionProduct = $product->replicate();
        $executionProduct->primary_model = $primary['model_id'];
        $executionProduct->ai_provider = $primary['provider'];
        $executionProduct->fallback_models = !empty($fallback['model_id']) ? [$fallback['model_id']] : [];
        $executionProduct->fallback_model_providers = !empty($fallback['provider']) ? [$fallback['provider']] : [];

        return $executionProduct;
    }

    /**
     * مدل اجرای محصول از کیفیتی می‌آید که کاربر انتخاب کرده است، نه از پلن خرید.
     * پلن فقط دسترسی «بهترین خروجی» و میزان اعتبار را تعیین می‌کند.
     */
    public function executionProductForQuality(Product $product, ?User $user, string $qualityKey, string $legacyTierKey): Product
    {
        $selection = $product->qualityModelConfiguration($qualityKey, ! $this->hasPaidPlan($user));
        $primary = (array) ($selection['primary'] ?? []);
        $fallback = (array) ($selection['fallback'] ?? []);

        if (empty($primary['model_id']) || empty($primary['provider'])) {
            return $this->executionProduct($product, $legacyTierKey);
        }

        $executionProduct = $product->replicate();
        $executionProduct->primary_model = $primary['model_id'];
        $executionProduct->ai_provider = $primary['provider'];
        $executionProduct->fallback_models = ! empty($fallback['model_id']) ? [$fallback['model_id']] : [];
        $executionProduct->fallback_model_providers = ! empty($fallback['provider']) ? [$fallback['provider']] : [];

        return $executionProduct;
    }
}
