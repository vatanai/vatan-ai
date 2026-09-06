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
}
