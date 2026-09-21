<?php

use App\Models\PlanPurchase;
use App\Services\ReferralProgramService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * خریدهای موفقی را که قبل از فعال‌شدن پاداش درصدی ثبت شده‌اند، یک‌بار
     * با همان منطق فعلی همکاری در فروش تکمیل می‌کند.
     *
     * خود سرویس با event key یکتا عمل می‌کند؛ بنابراین اجرای دوباره‌ی مهاجرت
     * یا بازپردازش یک خرید، اعتبار تکراری ایجاد نمی‌کند.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plan_purchases')
            || ! Schema::hasTable('referral_conversions')
            || ! Schema::hasTable('referral_rewards')) {
            return;
        }

        PlanPurchase::query()
            ->where('status', PlanPurchase::COMPLETED)
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('referral_conversions')
                    ->whereColumn('referral_conversions.invitee_id', 'plan_purchases.user_id')
                    ->where('referral_conversions.status', '!=', 'rejected');
            })
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('referral_rewards')
                    ->whereColumn('referral_rewards.plan_purchase_id', 'plan_purchases.id')
                    ->where('referral_rewards.reward_type', 'purchase_reward');
            })
            ->orderBy('id')
            ->eachById(function (PlanPurchase $purchase): void {
                app(ReferralProgramService::class)->handleCompletedPurchase($purchase);
            });
    }

    public function down(): void
    {
        // پاداش‌های پرداخت‌شده قابل برگشت خودکار نیستند؛ برگشت باید از مسیر
        // رسمی بازپرداخت و ثبت سند معکوس انجام شود.
    }
};
