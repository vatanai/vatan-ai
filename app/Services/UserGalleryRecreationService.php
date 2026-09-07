<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGalleryConfig;
use App\Models\UserGalleryItem;
use App\Models\UserGalleryRecreation;
use Illuminate\Validation\ValidationException;

class UserGalleryRecreationService
{
    public function ownedItem(User $user, int $itemId): UserGalleryItem
    {
        $item = $user->galleryItems()->whereKey($itemId)->first();
        if (! $item || ! $item->expires_at || $item->expires_at->isPast()) {
            throw ValidationException::withMessages(['gallery_item_id' => 'این تصویر گالری دیگر قابل استفاده نیست.']);
        }

        return $item;
    }

    public function freeRemaining(User $user): int
    {
        $limit = max(0, (int) UserGalleryConfig::current()->free_recreations_per_month);
        $used = $user->galleryRecreations()
            ->where('pricing_mode', 'monthly_free')
            ->whereIn('status', ['queued', 'processing', 'completed'])
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return max(0, $limit - $used);
    }

    public function begin(User $user, UserGalleryItem $item, int $productId, int $listCreditCost): UserGalleryRecreation
    {
        $pricingMode = $this->freeRemaining($user) > 0 ? 'monthly_free' : 'credits';

        return UserGalleryRecreation::query()->create([
            'user_id' => $user->id,
            'user_gallery_item_id' => $item->id,
            'product_id' => $productId,
            'pricing_mode' => $pricingMode,
            'list_credit_cost' => max(0, $listCreditCost),
            'charged_credit_cost' => $pricingMode === 'monthly_free' ? 0 : max(0, $listCreditCost),
            'status' => 'processing',
            'started_at' => now(),
            'metadata' => ['policy' => 'user_gallery_recreation_v1'],
        ]);
    }

    public function attachOrder(UserGalleryRecreation $recreation, ?int $orderId): void
    {
        $recreation->forceFill(['order_id' => $orderId])->save();
    }

    public function complete(UserGalleryRecreation $recreation, int $chargedCreditCost): void
    {
        $recreation->forceFill([
            'charged_credit_cost' => max(0, $chargedCreditCost),
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();
        app(UserGalleryCostService::class)->recordGeneration($recreation, $chargedCreditCost);
    }

    public function fail(UserGalleryRecreation $recreation, string $message): void
    {
        $recreation->forceFill([
            'status' => 'failed',
            'error_message' => mb_substr($message, 0, 1000),
            'completed_at' => now(),
        ])->save();
    }
}
