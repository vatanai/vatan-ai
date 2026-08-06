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

        return [
            'plans' => $plans,
            'planDisplay' => PlanSetting::display(),
            'customerSegment' => $user?->customer_segment ?: 'regular',
        ];
    }

    public function homePlans(?User $user = null): Collection
    {
        $catalog = $this->catalog($user);
        $limit = max(1, min(6, (int) ($catalog['planDisplay']['home_limit'] ?? 4)));

        return $catalog['plans']->take($limit);
    }
}
