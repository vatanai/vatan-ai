<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Services\PlanCatalogService;
use App\Services\SmsEventService;
use App\Services\ReferralProgramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanSubscriptionController extends Controller
{
    public function index(Request $request, PlanCatalogService $catalog): View
    {
        $user = auth()->user();
        if ($request->query('audience') === 'loyal' && auth('admin')->check()) {
            $user = tap($user?->replicate() ?? new \App\Models\User(), fn ($model) => $model->customer_segment = 'loyal');
        }

        return view('site.pricing', $catalog->catalog($user));
    }

    public function fakePayment(Request $request, string $plan): RedirectResponse
    {
        $planModel = Plan::query()->published()
            ->where(fn ($query) => $query->where('slug', $plan)
                ->when(is_numeric($plan), fn ($q) => $q->orWhereKey((int) $plan)))
            ->firstOrFail();

        if ($planModel->billing_type === 'custom') {
            return redirect('/#contact')->with('success', 'برای دریافت پیشنهاد اختصاصی با تیم فروش تماس بگیرید.');
        }

        $user = $request->user();
        $offer = $planModel->offerFor($user);
        abort_unless($offer['visible'] && $offer['purchasable'], 403);

        if ($planModel->purchase_limit) {
            $count = PlanPurchase::where('user_id', $user->id)
                ->where('plan_id', $planModel->id)
                ->where('status', 'completed')
                ->count();
            if ($count >= $planModel->purchase_limit) {
                return back()->with('error', 'سقف خرید این پلن برای حساب شما تکمیل شده است.');
            }
        }

        $grantedTokens = $offer['tokens'] + $offer['bonus_tokens'];

        DB::transaction(function () use ($user, $planModel, $offer, $grantedTokens) {
            $lockedUser = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            $before = (int) $lockedUser->tokens;

            $lockedUser->update([
                'tokens' => $before + $grantedTokens,
                'tokens_purchased' => (int) $lockedUser->tokens_purchased + $grantedTokens,
                'plan_id' => $planModel->id,
            ]);

            PlanPurchase::create([
                'user_id' => $lockedUser->id,
                'plan_id' => $planModel->id,
                'plan_code' => $planModel->plan_code,
                'plan_name' => $planModel->name,
                'customer_segment' => $offer['segment'],
                'paid_amount' => $offer['price'],
                'granted_tokens' => $grantedTokens,
                'plan_snapshot' => [
                    'version' => $planModel->version,
                    'price' => $offer['price'],
                    'tokens' => $offer['tokens'],
                    'bonus_tokens' => $offer['bonus_tokens'],
                    'billing_type' => $planModel->billing_type,
                    'features' => $planModel->features,
                ],
                'status' => 'completed',
                'payment_reference' => 'SIM-' . strtoupper(Str::random(16)),
                'purchased_at' => now(),
            ]);

            TokenLog::create([
                'user_id' => $lockedUser->id,
                'action' => 'add',
                'amount' => $grantedTokens,
                'balance_before' => $before,
                'balance_after' => $before + $grantedTokens,
                'note' => 'خرید پلن ' . $planModel->name,
            ]);
        });

        try {
            app(ReferralProgramService::class)->handleFirstPurchase($user->fresh());
        } catch (\Throwable $exception) {
            report($exception);
        }

        if ($user->phone) {
            app(SmsEventService::class)->send('plan_purchase_success', $user->phone, [
                'name' => $user->name,
                'plan_name' => $planModel->name,
            ]);
        }

        return redirect()->route('pricing.index')->with(
            'success',
            "پلن «{$planModel->name}» فعال شد و " . number_format($grantedTokens) . ' توکن به حساب شما اضافه شد.'
        );
    }
}
