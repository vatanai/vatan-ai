<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ReferralLink;
use App\Models\User;
use App\Services\ReferralProgramService;
use App\Support\ReferralCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function visit(Request $request, string $code, ReferralProgramService $referrals): RedirectResponse
    {
        $this->capture($request, $code, $referrals);

        return redirect()->route('site.home.root');
    }

    public function productVisit(Request $request, string $code, Product $product, ReferralProgramService $referrals): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);
        $this->capture($request, $code, $referrals, $product);

        return redirect()->route('app.product', $product->route_slug);
    }

    public function linkVisit(Request $request, ReferralLink $referralLink, ReferralProgramService $referrals): RedirectResponse
    {
        if (! $referralLink->isActive()) {
            abort(404);
        }

        $referralLink->loadMissing(['inviter', 'product']);
        // انتساب اول در نشست حفظ می‌شود؛ کلیک تکراری نباید لینک فعال را به ۴۰۴ تبدیل کند.
        $referrals->captureLinkVisit($request, $referralLink);

        return $referralLink->product
            ? redirect()->route('app.product', $referralLink->product->route_slug)
            : redirect()->to($referralLink->destination_url);
    }

    public function createLink(Request $request): RedirectResponse
    {
        abort_unless(\App\Models\ReferralSetting::current()->referralIsActive(), 403);

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);
        $product = Product::query()->whereKey($data['product_id'])->where('status', 'active')->firstOrFail();

        $existing = ReferralLink::query()
            ->where('inviter_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->where('status', 'active')
            ->whereNull('deactivated_at')
            ->latest('id')
            ->first();

        if ($existing) {
            return back()->with('success', 'برای این محصول قبلاً لینک فعال ساخته‌ای.');
        }

        do {
            // لینک‌های قبلی نباید تغییر کنند؛ لینک‌های جدید پنج نویسهٔ حرفی/عددی هستند.
            $slug = strtolower(ReferralCode::five());
        } while (ReferralLink::query()->where('slug', $slug)->exists());

        ReferralLink::query()->create([
            'inviter_id' => $request->user()->id,
            'product_id' => $product->id,
            'slug' => $slug,
            'destination_url' => route('app.product', $product->route_slug),
            'status' => 'active',
        ]);

        return back()->with('success', 'لینک رفرال محصول ساخته شد.');
    }

    public function deactivateLink(Request $request, ReferralLink $referralLink): RedirectResponse
    {
        abort_unless((int) $referralLink->inviter_id === (int) $request->user()->id, 403);

        $referralLink->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);

        return back()->with('success', 'لینک رفرال غیرفعال شد و سوابق آن حفظ شد.');
    }

    private function capture(Request $request, string $code, ReferralProgramService $referrals, ?Product $product = null): void
    {
        $inviter = User::query()
            ->where('referral_code', strtoupper($code))
            ->where('status', 'active')
            ->first();

        if ($inviter) {
            $referrals->captureVisit($inviter, $request, null, $product);
        }
    }
}
