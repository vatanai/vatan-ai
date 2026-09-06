<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\PlanSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

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

    /** پلن‌های قابل‌نمایش در صفحه نخست و مسیر خرید. */
    public function publicPricingPlans(?User $user = null): Collection
    {
        return $this->homePricingPlans()
            ->map(function (Plan $plan) use ($user) {
                $plan->setAttribute('offer', $plan->offerFor($user));

                return $plan;
            })
            ->filter(fn (Plan $plan) => (bool) $plan->offer['visible'])
            ->values();
    }

    /**
     * کارت‌های فعال سکشن پلن‌های صفحه نخست. این داده از کاتالوگ عمومی جداست تا
     * یک پلن قدیمیِ غیرفعال به‌اشتباه دوباره در لندینگ نمایش داده نشود.
     */
    public function homePricingPlans(): Collection
    {
        try {
            if (!Schema::hasTable('plans')) {
                return collect();
            }

            return Plan::query()
                ->published()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->filter(function (Plan $plan): bool {
                    $config = is_array($plan->home_pricing_config) ? $plan->home_pricing_config : [];

                    return (bool) ($config['is_active'] ?? true);
                })
                ->map(function (Plan $plan): Plan {
                    if (is_array($plan->home_pricing_config) && $plan->home_pricing_config !== []) {
                        return $plan;
                    }

                    $variant = match ($plan->model_tier_key) {
                        'pro' => 'professional',
                        'business' => 'advanced',
                        default => 'gift',
                    };
                    $plan->setAttribute('home_pricing_config', [
                        'is_active' => true,
                        'variant' => $variant,
                        'icon' => $plan->icon,
                        'eyebrow' => $plan->short_description,
                        'button' => ['style' => $plan->is_featured ? 'primary' : 'economic'],
                    ]);

                    return $plan;
                })
                ->values()
                ->values();
        } catch (\Throwable $exception) {
            report($exception);

            return collect();
        }
    }
}
