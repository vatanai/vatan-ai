<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Services\ReferralProgramService;
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
        $this->capture($request, $code, $referrals);

        return redirect()->route('app.product', $product->route_slug);
    }

    private function capture(Request $request, string $code, ReferralProgramService $referrals): void
    {
        $inviter = User::query()
            ->where('referral_code', strtoupper($code))
            ->where('status', 'active')
            ->first();

        if ($inviter) {
            $referrals->captureVisit($inviter, $request);
        }
    }
}
