<?php

namespace App\Observers;

use App\Models\Generation;
use App\Models\GrowthAttribution;
use App\Models\GrowthEvent;
use App\Models\Order;
use App\Models\PlanPurchase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class GrowthConversionObserver
{
    private const VISITOR_COOKIE = 'vtn_growth_visitor';

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Generation => $this->capture($model, 'generation_started'),
            $model instanceof Order => $this->capture($model, 'order_created'),
            $model instanceof PlanPurchase && $model->status === 'completed' => $this->capture($model, 'plan_purchase'),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        match (true) {
            $model instanceof Generation && $model->wasChanged('status') && $model->status === 'completed'
                => $this->capture($model, 'generation_completed'),
            $model instanceof Order && $model->wasChanged('payment_status') && $model->payment_status === 'paid'
                => $this->capture($model, 'purchase'),
            $model instanceof PlanPurchase && $model->wasChanged('status') && $model->status === 'completed'
                => $this->capture($model, 'plan_purchase'),
            default => null,
        };
    }

    private function capture(Model $model, string $stage): void
    {
        try {
            if (! Schema::hasTable('growth_attributions') || ! Schema::hasTable('growth_events')) {
                return;
            }

            $references = $this->references($model);
            $previous = GrowthAttribution::query()
                ->where(function ($query) use ($references) {
                    foreach ($references as $column => $value) {
                        if ($value !== null && $column !== 'user_id') {
                            $query->orWhere($column, $value);
                        }
                    }
                })
                ->latest('attributed_at')
                ->first();

            $visitorId = $previous?->visitor_id ?: $this->visitorFromRequest();
            $click = $previous
                ? GrowthEvent::where('event_uuid', $previous->click_event_uuid)->first()
                : $this->lastClick($visitorId);

            if (! $click) {
                return;
            }

            $identity = ['stage' => $stage] + array_filter(
                $references,
                fn ($value, $key) => $value !== null && $key !== 'user_id',
                ARRAY_FILTER_USE_BOTH
            );

            GrowthAttribution::updateOrCreate($identity, [
                'growth_link_id' => $click->growth_link_id,
                'click_event_uuid' => $click->event_uuid,
                'visitor_id' => $click->visitor_id,
                'user_id' => $references['user_id'],
                'attribution_model' => 'last_click',
                'is_repeat' => $this->isRepeatPurchase($model),
                'metadata' => [
                    'model' => class_basename($model),
                    'status' => $model->getAttribute('status') ?? $model->getAttribute('payment_status'),
                ],
                'attributed_at' => now(),
            ] + $references);
        } catch (Throwable $exception) {
            // خطای احتمالی تحلیل رشد نباید ساخت یا خرید اصلی کاربر را متوقف کند.
            report($exception);
        }
    }

    private function references(Model $model): array
    {
        return [
            'user_id' => $model->getAttribute('user_id'),
            'generation_id' => $model instanceof Generation ? $model->getKey() : null,
            'order_id' => $model instanceof Order ? $model->getKey() : null,
            'plan_purchase_id' => $model instanceof PlanPurchase ? $model->getKey() : null,
        ];
    }

    private function visitorFromRequest(): ?string
    {
        if (! app()->bound('request')) {
            return null;
        }

        $visitorId = (string) request()->cookie(self::VISITOR_COOKIE, '');

        return Str::isUuid($visitorId) ? $visitorId : null;
    }

    private function lastClick(?string $visitorId): ?GrowthEvent
    {
        if (! $visitorId) {
            return null;
        }

        return GrowthEvent::query()
            ->where('event_type', GrowthEvent::TYPE_CLICK)
            ->where('visitor_id', $visitorId)
            ->where('occurred_at', '>=', now()->subDays(30))
            ->latest('occurred_at')
            ->first();
    }

    private function isRepeatPurchase(Model $model): bool
    {
        if (! $model instanceof Order && ! $model instanceof PlanPurchase) {
            return false;
        }

        $userId = $model->getAttribute('user_id');
        if (! $userId) {
            return false;
        }

        $paidOrders = Schema::hasTable('orders')
            ? DB::table('orders')->where('user_id', $userId)->where('payment_status', 'paid')
                ->when($model instanceof Order, fn ($query) => $query->where('id', '!=', $model->getKey()))->count()
            : 0;
        $planPurchases = Schema::hasTable('plan_purchases')
            ? DB::table('plan_purchases')->where('user_id', $userId)->where('status', 'completed')
                ->when($model instanceof PlanPurchase, fn ($query) => $query->where('id', '!=', $model->getKey()))->count()
            : 0;

        return ($paidOrders + $planPurchases) > 0;
    }
}
