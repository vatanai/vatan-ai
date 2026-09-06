<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\User;
use App\Services\Payments\PlanPaymentService;
use App\Services\PlanCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PlanSubscriptionController extends Controller
{
    public function index(Request $request, PlanCatalogService $catalog): View
    {
        $user = $request->user();
        if ($request->query('audience') === 'loyal' && auth('admin')->check()) {
            $user = tap($user?->replicate() ?? new User(), fn (User $model) => $model->customer_segment = 'loyal');
        }

        return view('site.pricing', [
            'plans' => $catalog->publicPricingPlans($user),
            'homePricing' => \App\Models\PlanSetting::homePricing(),
        ]);
    }

    /** نمونهٔ قابل‌مشاهدهٔ نتیجه پرداخت، بدون ساخت سفارش یا تغییر اعتبار. */
    public function demoResult(Request $request): View
    {
        $state = $request->string('state', 'success')->toString();
        $examples = [
            'success' => [
                'status' => PlanPurchase::COMPLETED,
                'order_number' => 'DEMO-SUCCESS-1405',
                'plan_name' => 'حرفه‌ای',
                'paid_amount' => 485000,
                'granted_tokens' => 515,
                'failure_reason' => null,
            ],
            'pending' => [
                'status' => PlanPurchase::REDIRECTED,
                'order_number' => 'DEMO-PENDING-1405',
                'plan_name' => 'حرفه‌ای',
                'paid_amount' => 485000,
                'granted_tokens' => 515,
                'failure_reason' => null,
            ],
            'failed' => [
                'status' => PlanPurchase::FAILED,
                'order_number' => 'DEMO-FAILED-1405',
                'plan_name' => 'حرفه‌ای',
                'paid_amount' => 485000,
                'granted_tokens' => 515,
                'failure_reason' => 'پرداخت توسط درگاه تأیید نشد. هیچ مبلغ یا اعتباری در وطن ثبت نشده است.',
            ],
        ];
        $state = array_key_exists($state, $examples) ? $state : 'success';

        $planPurchase = new PlanPurchase($examples[$state]);
        $planPurchase->setRelation('plan', new Plan(['slug' => 'professional']));

        return view('site.payment-result', [
            'planPurchase' => $planPurchase,
            'isDemo' => true,
        ]);
    }

    public function checkout(Request $request, string $plan, PlanCatalogService $catalog): View|RedirectResponse
    {
        $planModel = $this->findPublicPlan($plan, $catalog);
        $user = $request->user();
        $offer = $planModel->offerFor($user);

        if ($planModel->billing_type === 'custom') {
            return redirect('/#contact')->with('success', 'برای دریافت پیشنهاد اختصاصی با تیم فروش تماس بگیرید.');
        }
        if ((int) $offer['price'] <= 0) {
            return redirect()->route('pricing.index')->with('success', 'اعتبار هدیه هنگام ثبت‌نام به حساب شما افزوده می‌شود.');
        }
        if (! $offer['visible'] || ! $offer['purchasable']) {
            abort(404);
        }

        return view('site.checkout', compact('planModel', 'offer', 'user'));
    }

    public function startPayment(
        Request $request,
        string $plan,
        PlanCatalogService $catalog,
        PlanPaymentService $payments,
    ): RedirectResponse {
        $planModel = $this->findPublicPlan($plan, $catalog);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:190'],
            'phone' => ['nullable', 'string', 'max:20'],
            'gateway' => ['required', Rule::in(['zarinpal'])],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'برای ادامه، پذیرش قوانین و شرایط استفاده لازم است.',
        ]);

        try {
            $purchase = $payments->initiate(
                $request->user(),
                $planModel,
                ['name' => $data['name'], 'email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null],
                route('payments.callback', ['purchase' => '__ORDER__']),
            );

            if (! $purchase->gateway_track_id) {
                throw new RuntimeException('کد رهگیری درگاه دریافت نشد.');
            }

            return redirect()->away(rtrim((string) config('services.zarinpal.start_url'), '/') . '/' . $purchase->gateway_track_id);
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }
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
        $planModel = $catalog->publicPricingPlans(auth()->user())
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
