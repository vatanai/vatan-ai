<?php

namespace App\Services;

use App\Models\UserGalleryCostEvent;
use App\Models\UserGalleryItem;
use App\Models\UserGalleryNotification;
use App\Models\UserGalleryRecreation;
use Illuminate\Support\Facades\Schema;

class UserGalleryCostService
{
    public function record(array $data): ?UserGalleryCostEvent
    {
        if (! Schema::hasTable('user_gallery_cost_events')) {
            return null;
        }

        return UserGalleryCostEvent::query()->create(array_merge([
            'status' => 'estimated',
            'units' => 0,
            'unit_cost_toman' => 0,
            'cost_toman' => 0,
            'incurred_at' => now(),
        ], $data));
    }

    public function recordStorage(UserGalleryItem $item): ?UserGalleryCostEvent
    {
        return $this->record([
            'user_id' => $item->user_id,
            'user_gallery_item_id' => $item->id,
            'cost_type' => 'storage',
            'units' => round(((int) $item->size) / 1048576, 6),
            'unit' => 'megabyte',
            'metadata' => ['retention_until' => $item->expires_at?->toIso8601String()],
        ]);
    }

    public function recordGeneration(UserGalleryRecreation $recreation, int $chargedCredits): ?UserGalleryCostEvent
    {
        return $this->record([
            'user_id' => $recreation->user_id,
            'user_gallery_item_id' => $recreation->user_gallery_item_id,
            'user_gallery_recreation_id' => $recreation->id,
            'cost_type' => 'generation',
            'status' => $recreation->pricing_mode === 'monthly_free' ? 'promotional' : 'actual',
            'units' => max(0, $chargedCredits),
            'unit' => 'credit',
            'metadata' => ['pricing_mode' => $recreation->pricing_mode],
        ]);
    }

    public function recordNotification(UserGalleryNotification $notification): ?UserGalleryCostEvent
    {
        return $this->record([
            'user_id' => $notification->user_id,
            'user_gallery_notification_id' => $notification->id,
            'cost_type' => 'notification',
            'units' => 1,
            'unit' => $notification->channel,
            'metadata' => ['status' => $notification->status, 'consent_checked' => $notification->consent_checked],
        ]);
    }
}
