<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\PlanSetting;
use App\Models\TokenLog;
use App\Services\PlanCatalogService;
use App\Services\PlanDiscountService;
use App\Services\SmsEventService;
use App\Services\ReferralProgramService;
use App\Services\Payments\PlanPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use RuntimeException;
use InvalidArgumentException;

class PlanSubscriptionController extends Controller
{
    public function index(Request $request, PlanCatalogService $catalog): View
    {
        $user = auth()->user();
        if ($request->query('audience') === 'loyal' && auth('admin')->check()) {
            $user = tap($user?->replicate() ?? new \App\Models\User(), fn ($model) => $model->customer_segment = 'loyal');
        }

        $catalogData = $catalog->catalog($user);
        $catalogData['plans'] = $catalog->homePricingPlans($user)->take(4)->values();
        $catalogData['homePricing'] = PlanSetting::homePricing();

        return view('site.pricing', $catalogData);
    }

    /** نمونه‌ی نتیجه‌ی پرداخت برای پیش‌نمایش؛ بدون ساخت سفارش یا تغییر موجودی. */
    public function demoResult(Request $request): View
    {
        $state = $request->string('state', 'success')->toString();
        $examples = [
            'success' => ['status' => PlanPurchase::COMPLETED, 'order_number' => 'DEMO-SUCCESS-1405', 'plan_name' => 'حرفه‌ای', 'paid_amount' => 485000, 'granted_tokens' => 515, 'failure_reason' => null],
            'pending' => ['status' => PlanPurchase::REDIRECTED, 'order_number' => 'DEMO-PENDING-1405', 'plan_name' => 'حرفه‌ای', 'paid_amount' => 485000, 'granted_tokens' => 515, 'failure_reason' => null],
            'failed' => ['status' => PlanPurchase::FAILED, 'order_number' => 'DEMO-FAILED-1405', 'plan_name' => 'حرفه‌ای', 'paid_amount' => 485000, 'granted_tokens' => 515, 'failure_reason' => 'پرداخت توسط درگاه تأیید نشد. هیچ مبلغ یا اعتباری در وطن ثبت نشده است.'],
        ];
        $state = array_key_exists($state, $examples) ? $state : 'success';
        $planPurchase = new PlanPurchase($examples[$state]);
        $planPurchase->setRelation('plan', new Plan(['slug' => 'professional']));

        return view('site.payment-result', ['planPurchase' => $planPurchase, 'isDemo' => true]);
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
        if ($request->filled('referral_code')) {
            app(ReferralProgramService::class)->attributeExistingUser($user, $request->input('referral_code'), $request);
        }
        $offer = app(ReferralProgramService::class)->purchaseOffer($user, $planModel, $planModel->offerFor($user));
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

        $purchase = DB::transaction(function () use ($request, $user, $planModel, $offer, $grantedTokens) {
            $lockedUser = $user->newQuery()->lockForUpdate()->findOrFail($user->id);
            $before = (int) $lockedUser->tokens;

            $lockedUser->update([
                'tokens' => $before + $grantedTokens,
                'tokens_purchased' => (int) $lockedUser->tokens_purchased + $grantedTokens,
                'plan_id' => $planModel->id,
            ]);

            return PlanPurchase::create([
                'user_id' => $lockedUser->id,
                'plan_id' => $planModel->id,
                'plan_code' => $planModel->plan_code,
                'plan_name' => $planModel->name,
                'customer_segment' => $offer['segment'],
                'paid_amount' => $offer['price'],
                'original_amount' => $offer['original_price'] ?? $offer['price'],
                'discount_amount' => $offer['discount_amount'] ?? 0,
                'referral_conversion_id' => $offer['referral_conversion_id'] ?? null,
                'referral_snapshot' => $offer['referral_conversion_id'] ? [
                    'discount_percent' => $offer['referral_discount_percent'] ?? 0,
                    'code' => $request->input('referral_code'),
                ] : null,
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

        app(ReferralProgramService::class)->attachPurchaseReferral($purchase, $request->input('referral_code'));

        try {
            app(ReferralProgramService::class)->handleFirstPurchase($user->fresh());
            app(ReferralProgramService::class)->handleCompletedPurchase($purchase->fresh(['user']));
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

    public function checkout(Request $request, string $plan, PlanCatalogService $catalog): View|RedirectResponse
    {
        $planModel = $this->findPublicPlan($plan, $catalog);
        $user = $request->user();
        app(\App\Services\ReferralProgramService::class)->attributeExistingUser($user, $request->input('referral_code'), $request);
        $offer = app(ReferralProgramService::class)->purchaseOffer($user, $planModel, $planModel->offerFor($user));

        if ($planModel->billing_type === 'custom') {
            return redirect('/#contact')->with('success', 'برای دریافت پیشنهاد اختصاصی با تیم فروش تماس بگیرید.');
        }
        if ((int) $offer['price'] <= 0) {
            return redirect()->route('pricing.index')->with('success', 'اعتبار هدیه هنگام ثبت‌نام به حساب شما افزوده می‌شود.');
        }
        abort_unless($offer['visible'] && $offer['purchasable'], 404);

        return view('site.checkout', compact('planModel', 'offer', 'user'));
    }

    public function startPayment(Request $request, string $plan, PlanCatalogService $catalog, PlanPaymentService $payments): RedirectResponse
    {
        $planModel = $this->findPublicPlan($plan, $catalog);
        app(\App\Services\ReferralProgramService::class)->attributeExistingUser($request->user(), $request->input('referral_code'), $request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gateway' => ['required', Rule::in(['zarinpal'])],
            'terms' => ['accepted'],
            'discount_code' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]{3,40}$/'],
        ], ['terms.accepted' => 'برای ادامه، پذیرش قوانین و شرایط استفاده لازم است.']);

        try {
            $purchase = $payments->initiate(
                $request->user(),
                $planModel,
                ['name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null],
                route('payments.callback', ['purchase' => '__ORDER__']),
                null,
                $data['discount_code'] ?? null,
            );

            if (! $purchase->gateway_track_id) {
                throw new RuntimeException('کد رهگیری درگاه دریافت نشد.');
            }

            return redirect()->away(rtrim((string) config('services.zarinpal.start_url'), '/') . '/' . $purchase->gateway_track_id);
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
    }

    public function applyDiscount(Request $request, string $plan, PlanCatalogService $catalog, PlanDiscountService $discounts): JsonResponse
    {
        $data = $request->validate([
            'discount_code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_-]{3,40}$/'],
        ]);
        $planModel = $this->findPublicPlan($plan, $catalog);
        $user = $request->user();
        $offer = app(ReferralProgramService::class)->purchaseOffer($user, $planModel, $planModel->offerFor($user));

        try {
            $offer = $discounts->apply($user, $planModel, $offer, $data['discount_code']);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'کد تخفیف با موفقیت اعمال شد.',
            'offer' => [
                'price' => (int) $offer['price'],
                'discount_amount_code' => (int) ($offer['discount_amount_code'] ?? 0),
                'discount_code' => $offer['discount_code'],
            ],
        ]);
    }

    public function callback(Request $request, string $purchase, PlanPaymentService $payments): RedirectResponse
    {
        $planPurchase = PlanPurchase::query()->where('order_number', $purchase)->firstOrFail();
        $payments->complete($planPurchase, $request->all());

        return redirect()->route('payments.result', $planPurchase->order_number);
    }

    public function result(Request $request, string $purchase): View
    {
        $planPurchase = $this->ownedPurchase($request, $purchase);
        return view('site.payment-result', compact('planPurchase'));
    }

    public function receipt(Request $request, string $purchase): View
    {
        $planPurchase = $this->ownedPurchase($request, $purchase);
        abort_unless($planPurchase->isCompleted(), 404);
        return view('site.payment-receipt', compact('planPurchase'));
    }

    private function findPublicPlan(string $plan, PlanCatalogService $catalog): Plan
    {
        $planModel = $catalog->catalog(auth()->user())['plans']
            ->first(fn (Plan $candidate) => $candidate->slug === $plan || (string) $candidate->id === $plan);
        abort_unless($planModel, 404);
        return $planModel;
    }

    private function ownedPurchase(Request $request, string $orderNumber): PlanPurchase
    {
        return PlanPurchase::query()
            ->with('user:id,name,last_name,email,phone')
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }
}
