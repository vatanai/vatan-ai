<?php

namespace App\Http\Controllers;

use App\Models\UserGalleryItem;
use App\Models\UserGallerySuggestion;
use App\Services\UserGalleryGrowthService;
use App\Services\UserGalleryService;
use Illuminate\Http\Request;

class UserGalleryController extends Controller
{
    public function updateConsent(Request $request, UserGalleryService $gallery)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $enabled = $request->boolean('enabled');
        if ($enabled && ! $gallery->config()->enabled) {
            return back()->with('error', 'ذخیره‌سازی گالری شخصی فعلاً فعال نیست.');
        }

        $gallery->syncConsent($user, $enabled);

        return back()->with('success', $enabled
            ? 'گالری شخصی فعال شد؛ ورودی‌های جدید ساخت با رضایت شما نگهداری می‌شوند.'
            : 'گالری شخصی غیرفعال شد؛ ورودی‌های قبلی شما حذف نشدند.');
    }

    public function preview(Request $request, UserGalleryItem $item, UserGalleryService $gallery)
    {
        abort_unless($request->user()?->id === $item->user_id, 403);

        return $gallery->response($item);
    }

    public function original(Request $request, UserGalleryItem $item, UserGalleryService $gallery)
    {
        abort_unless($request->user()?->id === $item->user_id, 403);

        return $gallery->response($item, true);
    }

    public function destroy(Request $request, UserGalleryItem $item, UserGalleryService $gallery)
    {
        abort_unless($request->user()?->id === $item->user_id, 403);

        $gallery->deleteItem($item);

        return back()->with('success', 'ورودی از گالری شخصی حذف شد.');
    }

    public function updatePreferences(Request $request, UserGalleryGrowthService $growth)
    {
        $user = $request->user();
        abort_unless($user, 401);

        $growth->syncPreference($user, [
            'suggestions_enabled' => $request->boolean('suggestions_enabled'),
            'marketing_enabled' => $request->boolean('marketing_enabled'),
            'occasion_enabled' => $request->boolean('occasion_enabled'),
            'reminders_enabled' => $request->boolean('reminders_enabled'),
        ]);

        return back()->with('success', 'تنظیمات پیشنهادهای شخصی ذخیره شد.');
    }

    public function recreate(Request $request, UserGallerySuggestion $suggestion, UserGalleryGrowthService $growth)
    {
        abort_unless($request->user()?->id === $suggestion->user_id, 403);
        abort_unless($suggestion->product && $suggestion->galleryItem, 404);

        $growth->markClicked($suggestion);

        return redirect()->route('app.create', [
            'product' => $suggestion->product->route_slug,
            'gallery_item' => $suggestion->galleryItem->id,
        ]);
    }

    public function dismissSuggestion(Request $request, UserGallerySuggestion $suggestion, UserGalleryGrowthService $growth)
    {
        abort_unless($request->user()?->id === $suggestion->user_id, 403);
        $growth->dismiss($suggestion);

        return back()->with('success', 'پیشنهاد از فهرست شما حذف شد.');
    }
}
