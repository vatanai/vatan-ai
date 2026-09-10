<?php

namespace App\Services;

use App\Models\GeneratedImage;
use App\Models\GeneratedVideo;
use App\Models\PlanPurchase;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * شمارنده‌های روزانهٔ مورد استفاده در اعلان‌های پنل مدیریت.
 *
 * این سرویس فقط خلاصهٔ تعدادها را می‌خواند و هیچ تغییری در داده‌ها ایجاد نمی‌کند.
 */
class AdminDailyNotificationService
{
    public function summary(): array
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();

        $completedPurchases = PlanPurchase::query()
            ->where('status', PlanPurchase::COMPLETED)
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween('purchased_at', [$start, $end])
                    ->orWhere(function ($fallback) use ($start, $end) {
                        $fallback
                            ->whereNull('purchased_at')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->count();

        $pendingPayments = PlanPurchase::query()
            ->whereIn('status', [
                PlanPurchase::PENDING,
                PlanPurchase::REDIRECTED,
                PlanPurchase::VERIFYING,
            ])
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $generatedImages = GeneratedImage::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $generatedVideos = GeneratedVideo::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $newUsers = User::query()
            ->whereBetween('created_at', [$start, $end])
            ->count();

        return [
            'completed_purchases' => $completedPurchases,
            'pending_payments' => $pendingPayments,
            'generated_images' => $generatedImages,
            'generated_videos' => $generatedVideos,
            'generated_outputs' => $generatedImages + $generatedVideos,
            'new_users' => $newUsers,
            'total' => $completedPurchases + $pendingPayments + $generatedImages + $generatedVideos + $newUsers,
            'day_label' => $this->dayLabel($start),
        ];
    }

    private function dayLabel(CarbonInterface $date): string
    {
        return $date->format('Y/m/d');
    }
}
