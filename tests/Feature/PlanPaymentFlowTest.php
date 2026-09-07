<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\Payments\PlanPaymentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PlanPaymentFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->string('status')->default('active');
            $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedInteger('tokens')->default(0);
            $table->unsignedInteger('tokens_purchased')->default(0);
            $table->unsignedInteger('tokens_used')->default(0);
            $table->unsignedInteger('promotional_tokens')->default(0);
            $table->string('referral_code')->nullable();
            $table->unsignedBigInteger('referred_by')->nullable();
            $table->timestamp('referral_attributed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('plan_code')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('price');
            $table->unsignedInteger('tokens');
            $table->string('status')->default('active');
            $table->string('model_tier_key')->default('professional');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('plan_purchases', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number')->nullable()->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('plan_code')->nullable();
            $table->string('plan_name');
            $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedInteger('granted_tokens')->default(0);
            $table->json('plan_snapshot');
            $table->string('status')->default('pending');
            $table->string('gateway')->nullable();
            $table->string('gateway_track_id')->nullable()->unique();
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_status')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone')->nullable();
            $table->text('failure_reason')->nullable();
            $table->json('callback_payload')->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('purchased_at');
            $table->timestamps();
        });

        Schema::create('token_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('action');
            $table->string('source')->nullable();
            $table->string('event_key')->nullable()->unique();
            $table->integer('amount');
            $table->integer('balance_before')->default(0);
            $table->integer('balance_after')->default(0);
            $table->string('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        config()->set('services.zarinpal.merchant_id', '00000000-0000-0000-0000-000000000000');
        config()->set('services.zarinpal.request_url', 'https://zarinpal.test/request');
        config()->set('services.zarinpal.verify_url', 'https://zarinpal.test/verify');
        config()->set('services.zarinpal.start_url', 'https://zarinpal.test/start');
    }

    public function test_verified_payment_credits_the_user_only_once(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-100', 'message' => 'درخواست موفق'],
                'errors' => [],
            ], 200),
            'https://zarinpal.test/verify' => Http::response([
                'data' => [
                    'code' => 100,
                    'amount' => 4_850_000,
                    'ref_id' => 'REFERENCE-100',
                    'message' => 'پرداخت تایید شد',
                ],
                'errors' => [],
            ], 200),
        ]);

        $user = $this->user(40);
        $plan = $this->plan();
        $payments = app(PlanPaymentService::class);

        $purchase = $payments->initiate($user, $plan, [
            'name' => 'کاربر آزمایشی',
            'email' => 'buyer@example.test',
            'phone' => null,
        ], 'https://vatan.test/site/payments/__ORDER__/callback');

        self::assertSame(PlanPurchase::REDIRECTED, $purchase->status);
        self::assertSame('AUTHORITY-100', $purchase->gateway_track_id);
        Http::assertSent(fn ($request) => $request->url() === 'https://zarinpal.test/request'
            && $request['amount'] === 4_850_000
            && str_contains($request['callback_url'], $purchase->order_number));

        $completed = $payments->complete($purchase, ['Authority' => 'AUTHORITY-100', 'Status' => 'OK']);
        $payments->complete($purchase, ['Authority' => 'AUTHORITY-100', 'Status' => 'OK']);

        self::assertSame(PlanPurchase::COMPLETED, $completed->status);
        self::assertSame('REFERENCE-100', $completed->gateway_reference);
        self::assertSame(555, $user->fresh()->tokens);
        self::assertSame(515, $user->fresh()->tokens_purchased);
        self::assertSame($plan->id, $user->fresh()->plan_id);
        self::assertSame(1, TokenLog::query()->where('source', 'plan_purchase')->count());
        self::assertDatabaseHas('plan_purchases', [
            'id' => $purchase->id,
            'status' => PlanPurchase::COMPLETED,
            'payment_reference' => 'REFERENCE-100',
        ]);
    }

    public function test_failed_gateway_verification_does_not_credit_the_user(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-FAILED'],
                'errors' => [],
            ], 200),
            'https://zarinpal.test/verify' => Http::response([
                'data' => [],
                'errors' => ['code' => -51, 'message' => 'پرداخت ناموفق بود'],
            ], 200),
        ]);

        $user = $this->user(40);
        $purchase = app(PlanPaymentService::class)->initiate($user, $this->plan(), [
            'name' => 'کاربر آزمایشی',
            'email' => null,
            'phone' => null,
        ], 'https://vatan.test/site/payments/__ORDER__/callback');

        $failed = app(PlanPaymentService::class)->complete($purchase, ['Authority' => 'AUTHORITY-FAILED', 'Status' => 'OK']);

        self::assertSame(PlanPurchase::FAILED, $failed->status);
        self::assertSame(40, $user->fresh()->tokens);
        self::assertSame(0, TokenLog::query()->count());
    }

    public function test_temporary_verification_outage_keeps_purchase_pending_and_reconciliation_completes_it(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-RETRY'],
                'errors' => [],
            ], 200),
            'https://zarinpal.test/verify' => Http::sequence()
                ->pushFailedConnection()
                ->push([
                    'data' => [
                        'code' => 100,
                        'amount' => 4_850_000,
                        'ref_id' => 'REFERENCE-RETRY',
                        'message' => 'پرداخت تایید شد',
                    ],
                    'errors' => [],
                ], 200),
        ]);

        $user = $this->user(40);
        $payments = app(PlanPaymentService::class);
        $purchase = $payments->initiate($user, $this->plan(), [
            'name' => 'کاربر آزمایشی',
            'email' => null,
            'phone' => null,
        ], 'https://vatan.test/site/payments/__ORDER__/callback');

        $pending = $payments->complete($purchase, ['Authority' => 'AUTHORITY-RETRY', 'Status' => 'OK']);

        self::assertSame(PlanPurchase::VERIFYING, $pending->status);
        self::assertSame(40, $user->fresh()->tokens);

        $result = $payments->reconcileUnfinishedPurchases(0);

        self::assertSame(1, $result['attempted']);
        self::assertSame(1, $result['completed']);
        self::assertSame(555, $user->fresh()->tokens);
        self::assertSame(1, TokenLog::query()->where('source', 'plan_purchase')->count());
    }

    public function test_callback_authority_mismatch_does_not_credit_the_user(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-ORIGINAL'],
                'errors' => [],
            ], 200),
        ]);

        $user = $this->user(40);
        $purchase = app(PlanPaymentService::class)->initiate($user, $this->plan(), [
            'name' => 'کاربر آزمایشی',
            'email' => null,
            'phone' => null,
        ], 'https://vatan.test/site/payments/__ORDER__/callback');

        $failed = app(PlanPaymentService::class)->complete($purchase, ['Authority' => 'AUTHORITY-TAMPERED', 'Status' => 'OK']);

        self::assertSame(PlanPurchase::FAILED, $failed->status);
        self::assertSame(40, $user->fresh()->tokens);
        self::assertSame(0, TokenLog::query()->count());
        Http::assertSentCount(1);
    }

    public function test_canceled_callback_does_not_call_verification_or_credit_the_user(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-CANCELED'],
                'errors' => [],
            ], 200),
        ]);

        $user = $this->user(40);
        $purchase = app(PlanPaymentService::class)->initiate($user, $this->plan(), [
            'name' => 'کاربر آزمایشی',
            'email' => null,
            'phone' => null,
        ], 'https://vatan.test/site/payments/__ORDER__/callback');

        $failed = app(PlanPaymentService::class)->complete($purchase, [
            'Authority' => 'AUTHORITY-CANCELED',
            'Status' => 'NOK',
        ]);

        self::assertSame(PlanPurchase::FAILED, $failed->status);
        self::assertSame(40, $user->fresh()->tokens);
        self::assertSame(0, TokenLog::query()->count());
        Http::assertSentCount(1);
    }

    public function test_multiple_paid_plans_create_distinct_successful_orders(): void
    {
        Http::fake(function ($request) {
            if ($request->url() === 'https://zarinpal.test/request') {
                return Http::response([
                    'data' => ['code' => 100, 'authority' => 'AUTHORITY-' . $request['amount']],
                    'errors' => [],
                ], 200);
            }

            $authority = (string) $request['authority'];
            $amount = (int) $request['amount'];

            return Http::response([
                'data' => [
                    'code' => 100,
                    'amount' => $amount,
                    'ref_id' => 'REFERENCE-' . $authority,
                    'message' => 'پرداخت تایید شد',
                ],
                'errors' => [],
            ], 200);
        });

        $user = $this->user(40);
        $plans = [
            $this->plan('PLAN-PRO', 'professional', 485_000, 515),
            $this->plan('PLAN-ADV', 'advanced', 2_290_000, 2_620),
            $this->plan('PLAN-BIZ', 'business', 9_600_000, 16_410),
        ];

        foreach ($plans as $plan) {
            $purchase = app(PlanPaymentService::class)->initiate($user, $plan, [
                'name' => 'کاربر آزمایشی',
                'email' => null,
                'phone' => null,
            ], 'https://vatan.test/site/payments/__ORDER__/callback');

            app(PlanPaymentService::class)->complete($purchase, ['Authority' => $purchase->gateway_track_id, 'Status' => 'OK']);
        }

        self::assertSame(3, PlanPurchase::query()->where('status', PlanPurchase::COMPLETED)->count());
        self::assertSame(19_585, $user->fresh()->tokens);
        self::assertSame($plans[2]->id, $user->fresh()->plan_id);
    }

    public function test_payment_result_demo_displays_each_state_without_creating_a_purchase(): void
    {
        $before = PlanPurchase::query()->count();

        $this->get(route('payments.demo', ['state' => 'success']))
            ->assertOk()
            ->assertSee('خرید شما با موفقیت انجام شد')
            ->assertSee('نمونه نمایشی است');

        $this->get(route('payments.demo', ['state' => 'pending']))
            ->assertOk()
            ->assertSee('پرداخت شما در انتظار بررسی است');

        $this->get(route('payments.demo', ['state' => 'failed']))
            ->assertOk()
            ->assertSee('پرداخت تکمیل نشد');

        self::assertSame($before, PlanPurchase::query()->count());
    }

    public function test_checkout_links_to_rules_and_keeps_payment_disabled_until_acceptance(): void
    {
        $user = $this->user(40);
        $plan = $this->plan();

        $this->actingAs($user)
            ->get(route('pricing.checkout', $plan->slug))
            ->assertOk()
            ->assertSee(route('privacy'), false)
            ->assertSee('قوانین، شرایط استفاده و حریم خصوصی وطن')
            ->assertSee('data-checkout-terms', false)
            ->assertSee('disabled aria-disabled="true" data-checkout-submit', false);
    }

    public function test_payment_cannot_start_without_accepting_the_rules(): void
    {
        Http::fake();
        $user = $this->user(40);
        $plan = $this->plan();

        $this->actingAs($user)
            ->from(route('pricing.checkout', $plan->slug))
            ->post(route('pricing.start-payment', $plan->slug), [
                'name' => 'کاربر آزمایشی',
                'email' => null,
                'phone' => $user->phone,
                'gateway' => 'zarinpal',
            ])
            ->assertRedirect(route('pricing.checkout', $plan->slug))
            ->assertSessionHasErrors('terms');

        self::assertSame(0, PlanPurchase::query()->count());
        Http::assertNothingSent();
    }

    public function test_payment_starts_after_the_rules_are_accepted(): void
    {
        Http::fake([
            'https://zarinpal.test/request' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTHORITY-CONSENT'],
                'errors' => [],
            ], 200),
        ]);
        $user = $this->user(40);
        $plan = $this->plan();

        $response = $this->actingAs($user)
            ->post(route('pricing.start-payment', $plan->slug), [
                'name' => 'کاربر آزمایشی',
                'email' => null,
                'phone' => $user->phone,
                'gateway' => 'zarinpal',
                'terms' => '1',
            ]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('https://zarinpal.test/start/AUTHORITY-CONSENT', $response->headers->get('Location'));

        self::assertSame(1, PlanPurchase::query()->count());
        Http::assertSentCount(1);
    }

    private function user(int $tokens): User
    {
        return User::query()->create([
            'name' => 'کاربر',
            'email' => 'payment-user@example.test',
            'password' => 'password',
            'tokens' => $tokens,
        ]);
    }

    private function plan(string $planCode = 'PLAN-PRO', string $slug = 'professional', int $price = 485_000, int $tokens = 515): Plan
    {
        return Plan::query()->create([
            'plan_code' => $planCode,
            'name' => $slug,
            'slug' => $slug,
            'price' => $price,
            'tokens' => $tokens,
            'status' => 'active',
        ]);
    }
}
