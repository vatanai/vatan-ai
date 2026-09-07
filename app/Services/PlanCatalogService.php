<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class PlanCatalogService
{
    public function catalog(?User $user = null): array
    {
        $plans = collect();
        $planDisplay = $this->defaultPlanDisplay();

        try {
            $plans = Plan::query()
                ->published()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(function (Plan $plan) use ($user) {
                    $plan->setAttribute('offer', $plan->offerFor($user));
                    return $plan;
                })
                ->filter(fn (Plan $plan) => $plan->offer['visible'])
                ->values();

            $planDisplay = PlanSetting::display();
        } catch (\Throwable $exception) {
            // خرابی یا تفاوت دیتابیس نباید صفحه‌ی عمومی را با خطای ۵۰۰ متوقف کند.
            report($exception);
        }

        return [
            'plans' => $plans,
            'planDisplay' => $planDisplay,
            'customerSegment' => $user?->customer_segment ?: 'regular',
        ];
    }

    private function defaultPlanDisplay(): array
    {
        return [
            'mode' => 'cards',
            'home_limit' => 4,
            'show_images' => false,
            'show_comparison' => true,
            'title' => 'پلن مناسب خودت را انتخاب کن',
            'subtitle' => 'از شروع رایگان تا راهکارهای سازمانی، متناسب با میزان استفاده شما',
        ];
    }

    public function homePlans(?User $user = null): Collection
    {
        $catalog = $this->catalog($user);
        $limit = max(1, min(6, (int) ($catalog['planDisplay']['home_limit'] ?? 4)));

        return $catalog['plans']->take($limit);
    }

    /**
     * پلن‌های بخش قیمت‌گذاری صفحه‌ی اصلی را برمی‌گرداند. این مسیر باید در زمان
     * دیپلوی ناقص یا قبل از اجرای migrationها هم صفحه‌ی عمومی را از کار نیندازد؛
     * بنابراین نبود ستون تنظیمات قیمت‌گذاری به fallback عمومی برمی‌گردد.
     */
    public function homePricingPlans(?User $user = null): Collection
    {
        try {
            $plans = Plan::query()
                ->published()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->filter(function (Plan $plan) use ($user): bool {
                    $config = is_array($plan->home_pricing_config) ? $plan->home_pricing_config : [];

                    return ($config['is_active'] ?? true) && $plan->offerFor($user)['visible'];
                })
                ->values();

            return $plans->isNotEmpty() ? $plans : $this->homePlans($user);
        } catch (\Throwable $exception) {
            // در نسخه‌ای که migration ستون home_pricing_config هنوز اجرا نشده،
            // کارت‌های عمومی قدیمی همچنان باید قابل نمایش باشند.
            report($exception);

            return $this->homePlans($user);
        }
    }
}
