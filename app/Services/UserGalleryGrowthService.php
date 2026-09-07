<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\UserGalleryConfig;
use App\Models\UserGalleryEvent;
use App\Models\UserGalleryItem;
use App\Models\UserGalleryPreference;
use App\Models\UserGallerySuggestion;
use Illuminate\Support\Facades\Schema;

class UserGalleryGrowthService
{
    public function preference(User $user): UserGalleryPreference
    {
        return UserGalleryPreference::forUser($user);
    }

    public function syncPreference(User $user, array $values): UserGalleryPreference
    {
        $preference = $this->preference($user);
        $enabled = (bool) ($values['suggestions_enabled'] ?? false);
        $preference->forceFill([
            'suggestions_enabled' => $enabled,
            'marketing_enabled' => $enabled && (bool) ($values['marketing_enabled'] ?? false),
            'occasion_enabled' => $enabled && (bool) ($values['occasion_enabled'] ?? false),
            'reminders_enabled' => $enabled && (bool) ($values['reminders_enabled'] ?? false),
            'consented_at' => $enabled ? ($preference->consented_at ?: now()) : $preference->consented_at,
            'revoked_at' => $enabled ? null : now(),
        ])->save();

        UserGalleryEvent::query()->create([
            'user_id' => $user->id,
            'action' => $enabled ? 'suggestions_consent_granted' : 'suggestions_consent_revoked',
            'metadata' => [
                'marketing_enabled' => $preference->marketing_enabled,
                'occasion_enabled' => $preference->occasion_enabled,
                'reminders_enabled' => $preference->reminders_enabled,
            ],
        ]);

        return $preference->fresh();
    }

    public function generateForUser(User $user): int
    {
        if (! Schema::hasTable('user_gallery_suggestions') || ! UserGalleryConfig::current()->suggestions_enabled) {
            return 0;
        }

        $preference = $this->preference($user);
        if (! $preference->suggestions_enabled || ! $user->gallerySetting?->enabled) {
            return 0;
        }

        $type = $preference->occasion_enabled ? 'occasion' : ($preference->marketing_enabled ? 'new_product' : null);
        if ($type === null) {
            return 0;
        }

        $products = Product::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('output_type')->orWhere('output_type', 'image');
            })
            ->latest('id')
            ->limit(3)
            ->get();
        if ($products->isEmpty()) {
            return 0;
        }

        $created = 0;
        $items = $user->galleryItems()->where('expires_at', '>', now())->latest('id')->limit(3)->get();
        foreach ($items as $item) {
            foreach ($products as $product) {
                $exists = UserGallerySuggestion::query()
                    ->where('user_id', $user->id)
                    ->where('user_gallery_item_id', $item->id)
                    ->where('product_id', $product->id)
                    ->whereIn('status', ['suggested', 'viewed', 'clicked'])
                    ->exists();
                if ($exists) {
                    continue;
                }

                $suggestion = UserGallerySuggestion::query()->create([
                    'user_id' => $user->id,
                    'user_gallery_item_id' => $item->id,
                    'product_id' => $product->id,
                    'suggestion_type' => $type,
                    'status' => 'suggested',
                    'title' => $type === 'occasion' ? 'یک ساخت مناسبتی برای تصویرت' : 'یک بازآفرینی تازه با تصویرت',
                    'body' => 'پیش‌نمایش پیشنهاد فقط بعد از انتخاب شما به ساخت واقعی تبدیل می‌شود و قبل از آن هزینه‌ای ندارد.',
                    'preview_payload' => [
                        'preview_url' => route('profile.gallery.preview', $item),
                        'product_name' => $product->name_fa ?: $product->name_en,
                        'watermarked' => true,
                    ],
                    'scheduled_at' => now(),
                    'expires_at' => $item->expires_at,
                ]);
                app(UserGalleryCampaignService::class)->prepareSuggestion($suggestion, $preference);
                $created++;
            }
        }

        return $created;
    }

    public function markViewed(UserGallerySuggestion $suggestion): void
    {
        if ($suggestion->status === 'suggested') {
            $suggestion->forceFill(['status' => 'viewed', 'viewed_at' => now()])->save();
        }
    }

    public function markClicked(UserGallerySuggestion $suggestion): void
    {
        $suggestion->forceFill(['status' => 'clicked', 'clicked_at' => now()])->save();
    }

    public function dismiss(UserGallerySuggestion $suggestion): void
    {
        $suggestion->forceFill(['status' => 'dismissed'])->save();
    }
}
