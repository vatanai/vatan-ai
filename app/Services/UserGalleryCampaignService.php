<?php

namespace App\Services;

use App\Models\UserGalleryCampaign;
use App\Models\UserGalleryNotification;
use App\Models\UserGalleryPreference;
use App\Models\UserGallerySuggestion;
use Illuminate\Support\Str;

class UserGalleryCampaignService
{
    public function prepareSuggestion(UserGallerySuggestion $suggestion, UserGalleryPreference $preference): ?UserGalleryNotification
    {
        if (! $preference->suggestions_enabled) {
            return null;
        }

        $campaign = UserGalleryCampaign::query()->create([
            'user_id' => $suggestion->user_id,
            'user_gallery_item_id' => $suggestion->user_gallery_item_id,
            'code' => 'gallery-' . Str::lower((string) Str::uuid()),
            'campaign_type' => $suggestion->suggestion_type,
            'status' => 'prepared',
            'title' => $suggestion->title,
            'body' => $suggestion->body,
            'consent_snapshot' => [
                'suggestions_enabled' => (bool) $preference->suggestions_enabled,
                'marketing_enabled' => (bool) $preference->marketing_enabled,
                'occasion_enabled' => (bool) $preference->occasion_enabled,
                'reminders_enabled' => (bool) $preference->reminders_enabled,
            ],
            'metadata' => ['delivery_policy' => 'in_app_only_until_explicit_action'],
            'scheduled_at' => now(),
            'expires_at' => $suggestion->expires_at,
        ]);

        $notification = UserGalleryNotification::query()->create([
            'user_id' => $suggestion->user_id,
            'user_gallery_campaign_id' => $campaign->id,
            'user_gallery_suggestion_id' => $suggestion->id,
            'channel' => 'in_app',
            'status' => 'prepared',
            'consent_checked' => true,
            'payload' => $suggestion->preview_payload,
        ]);

        app(UserGalleryCostService::class)->recordNotification($notification);

        return $notification;
    }
}
