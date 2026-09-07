<?php

namespace App\Observers;

use App\Models\AiProviderRequest;
use App\Models\Order;
use App\Models\PlanPurchase;
use App\Services\Finance\FinanceSyncService;
use App\Services\Finance\FinanceCaseLedgerService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class FinanceSourceObserver
{
    public function saved(Model $model): void
    {
        try {
            $service = app(FinanceSyncService::class);
            match (true) {
                $model instanceof PlanPurchase => $service->syncPlanPurchase($model),
                $model instanceof AiProviderRequest => $service->syncProviderRequest($model),
                $model instanceof Order => $service->syncOrder($model),
                default => null,
            };
        } catch (Throwable $exception) {
            // اسنپ‌شات‌های تحلیلی نباید مسیر اصلی را متوقف کنند.
            report($exception);
        }

        try {
            $ledger = app(FinanceCaseLedgerService::class);
            match (true) {
                $model instanceof PlanPurchase => $ledger->recordPurchase($model),
                $model instanceof AiProviderRequest => $ledger->recordProviderRequest($model),
                $model instanceof Order => $ledger->recordOrderStatus($model),
                default => null,
            };
        } catch (Throwable $exception) {
            // دفتر پرونده نیز از عملیات اصلی خرید و اجرای محصول مستقل است.
            report($exception);
        }
    }
}
