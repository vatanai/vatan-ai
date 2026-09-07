<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AiProviderRequest;
use App\Models\FinanceExchangeRate;
use App\Models\FinanceCase;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinancePlanSnapshot;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\Product;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\CreditWalletService;
use App\Services\Finance\FinanceCaseAnalysisService;
use App\Services\Finance\FinanceCaseLedgerService;
use App\Services\Finance\FinanceReportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinanceModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        Schema::create('admins', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('phone')->nullable()->unique(); $table->string('password');
            $table->string('password_reveal')->nullable(); $table->string('role')->default('admin');
            $table->boolean('is_active')->default(true); $table->rememberToken(); $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('email')->unique();
            $table->string('last_name')->nullable(); $table->string('phone')->nullable();
            $table->string('password'); $table->string('status')->default('active');
            $table->string('customer_segment')->default('regular'); $table->unsignedBigInteger('plan_id')->nullable();
            $table->unsignedInteger('tokens')->default(0); $table->unsignedInteger('tokens_purchased')->default(0);
            $table->unsignedInteger('tokens_used')->default(0); $table->unsignedInteger('promotional_tokens')->default(0);
            $table->string('referral_code')->nullable();
            $table->timestamps();
        });
        Schema::create('plans', function (Blueprint $table): void {
            $table->id(); $table->string('plan_code')->unique(); $table->string('name'); $table->string('slug')->unique();
            $table->unsignedBigInteger('price'); $table->unsignedInteger('tokens'); $table->string('status')->default('active');
            $table->string('model_tier_key')->default('economy'); $table->timestamps();
        });
        Schema::create('plan_purchases', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('order_number')->nullable()->unique();
            $table->string('plan_code')->nullable(); $table->string('plan_name'); $table->string('customer_segment')->default('regular');
            $table->unsignedBigInteger('paid_amount')->default(0); $table->unsignedInteger('granted_tokens')->default(0);
            $table->json('plan_snapshot'); $table->string('status')->default('completed');
            $table->string('payment_reference')->nullable()->unique(); $table->timestamp('purchased_at'); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table): void {
            $table->id(); $table->string('name_fa'); $table->string('name_en'); $table->string('slug')->unique();
            $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('category'); $table->string('status')->default('draft'); $table->string('thumbnail');
            $table->string('primary_model'); $table->string('ai_provider')->nullable(); $table->json('fallback_models')->nullable(); $table->text('prompt_template');
            $table->unsignedInteger('credit_cost')->default(5); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table): void {
            $table->id(); $table->string('order_number')->unique(); $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable(); $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('plan_name')->nullable(); $table->string('model_tier_key')->nullable(); $table->string('model_tier_name')->nullable();
            $table->string('status')->default('pending'); $table->string('payment_status')->default('unpaid');
            $table->string('processing_status')->default('queued'); $table->unsignedInteger('final_credits')->default(0);
            $table->unsignedInteger('original_credits')->default(0); $table->unsignedInteger('discount_credits')->default(0);
            $table->unsignedInteger('promotional_credits_used')->default(0); $table->unsignedInteger('paid_credits_used')->default(0);
            $table->unsignedInteger('promotional_credits_refunded')->default(0); $table->unsignedInteger('paid_credits_refunded')->default(0);
            $table->unsignedInteger('refunded_credits')->default(0); $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedInteger('processing_duration_ms')->nullable(); $table->string('source')->default('direct');
            $table->string('ai_model')->nullable(); $table->string('ai_provider')->nullable(); $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_models', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('openrouter_model_id')->nullable();
            $table->string('external_model_id')->nullable(); $table->string('provider')->nullable();
            $table->decimal('cost_per_generation_usd', 12, 6)->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_provider_requests', function (Blueprint $table): void {
            $table->id(); $table->string('provider'); $table->unsignedBigInteger('ai_model_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable(); $table->string('external_request_id');
            $table->string('status')->default('queued'); $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->decimal('actual_cost_usd', 12, 6)->nullable(); $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable(); $table->timestamps();
        });
        Schema::create('token_logs', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('action'); $table->string('source')->nullable(); $table->string('event_key')->nullable()->unique();
            $table->integer('amount'); $table->integer('balance_before')->default(0); $table->integer('balance_after')->default(0);
            $table->string('note')->nullable(); $table->json('metadata')->nullable(); $table->timestamps();
        });
        Schema::create('generated_images', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('product_id')->nullable();
            $table->string('image_path'); $table->text('user_prompt')->nullable(); $table->decimal('cost', 10, 6)->default(0);
            $table->unsignedBigInteger('size')->default(0); $table->timestamps();
        });

        $financeMigration = require database_path('migrations/2026_08_18_170000_create_finance_management_tables.php');
        $financeMigration->up();
        $caseMigration = require database_path('migrations/2026_08_22_000001_create_finance_purchase_cases.php');
        $caseMigration->up();

        Plan::query()->create([
            'plan_code' => 'PLN-TEST', 'name' => 'پلن تست', 'slug' => 'finance-test-plan',
            'price' => 1_000_000, 'tokens' => 100, 'status' => 'active', 'model_tier_key' => 'economy',
        ]);
    }

    public function test_all_admins_can_view_but_only_finance_roles_can_write(): void
    {
        $viewer = $this->admin('admin', 'viewer');
        $this->actingAs($viewer, 'admin');
        foreach (array_diff(array_keys(config('finance.sections')), ['cases']) as $section) {
            $this->get(route('admin.finance.show', ['section' => $section]))
                ->assertOk()
                ->assertSee(config("finance.sections.{$section}"));
        }
        $this->get(route('admin.finance.plans.show', Plan::query()->firstOrFail()))
            ->assertOk()
            ->assertSee('جزئیات سود پلن تست');
        $this->get(route('admin.finance.cases.index'))->assertOk()->assertSee('پرونده خریدها');

        $this->actingAs($viewer, 'admin')
            ->post(route('admin.finance.transactions.store'), $this->transactionPayload())
            ->assertForbidden();

        $finance = $this->admin('finance', 'finance');
        $this->actingAs($finance, 'admin')
            ->post(route('admin.finance.transactions.store'), $this->transactionPayload())
            ->assertRedirect();

        self::assertDatabaseHas('finance_transactions', [
            'title' => 'هزینه آزمایشی مسیر اصلی',
            'amount_toman' => 1200000,
            'created_by' => $finance->id,
        ]);
    }

    public function test_owner_can_approve_and_soft_delete_manual_transaction(): void
    {
        $owner = $this->admin('leader', 'owner');
        $transaction = FinanceTransaction::query()->create([
            'direction' => 'expense', 'category' => 'tools', 'title' => 'ابزار تست',
            'amount_original' => 1000, 'currency' => 'IRR', 'exchange_rate_irr' => 1,
            'amount_irr' => 1000, 'status' => 'paid', 'occurred_at' => now(),
            'source_type' => 'manual', 'created_by' => $owner->id,
        ]);

        $this->actingAs($owner, 'admin')
            ->patch(route('admin.finance.transactions.approve', $transaction))
            ->assertRedirect();
        self::assertNotNull($transaction->fresh()->approved_at);

        $this->actingAs($owner, 'admin')
            ->delete(route('admin.finance.transactions.destroy', $transaction))
            ->assertRedirect();
        self::assertSoftDeleted('finance_transactions', ['id' => $transaction->id]);
        self::assertDatabaseHas('finance_audit_logs', ['auditable_id' => $transaction->id, 'action' => 'deleted']);
    }

    public function test_plan_order_and_provider_cost_create_traceable_snapshots(): void
    {
        FinanceExchangeRate::query()->create([
            'currency' => 'USD', 'rate_date' => now()->toDateString(),
            'rate_to_irr' => 650000, 'rate_to_toman' => 65000, 'source' => 'test', 'is_manual' => true,
        ]);
        $user = User::query()->create([
            'name' => 'کاربر مالی', 'email' => 'finance-user@example.test',
            'password' => 'test-password', 'status' => 'active', 'tokens' => 100, 'tokens_purchased' => 100,
        ]);
        $plan = Plan::query()->firstOrFail();
        $purchase = PlanPurchase::query()->create([
            'user_id' => $user->id,
            'order_number' => 'FINANCE-TEST-ORDER',
            'plan_id' => $plan->id,
            'plan_code' => $plan->plan_code,
            'plan_name' => $plan->name,
            'paid_amount' => 1_000_000,
            'granted_tokens' => 100,
            'plan_snapshot' => ['model_tier_key' => $plan->model_tier_key],
            'status' => 'completed',
            'payment_reference' => 'FINANCE-TEST-PURCHASE',
            'purchased_at' => now()->subMinute(),
        ]);
        self::assertDatabaseHas('finance_plan_snapshots', ['plan_purchase_id' => $purchase->id]);
        self::assertDatabaseHas('finance_transactions', ['source_key' => "plan-purchase:{$purchase->id}", 'direction' => 'income']);

        $product = Product::query()->create([
            'name_fa' => 'محصول تست مالی', 'name_en' => 'Finance product',
            'slug' => 'finance-product-test', 'category' => 'test', 'thumbnail' => 'test.jpg',
            'primary_model' => 'test/model-primary', 'fallback_models' => ['test/model-fallback'],
            'prompt_template' => 'test prompt', 'credit_cost' => 10, 'status' => 'active',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id, 'product_id' => $product->id, 'plan_id' => $plan->id,
            'plan_name' => $plan->name, 'model_tier_key' => $plan->model_tier_key,
            'status' => 'completed', 'payment_status' => 'paid', 'processing_status' => 'completed',
            'final_credits' => 10, 'ai_model' => 'test/model-primary', 'ai_provider' => 'test-provider',
            'completed_at' => now(),
        ]);
        AiProviderRequest::query()->create([
            'provider' => 'test-provider', 'order_id' => $order->id,
            'external_request_id' => 'FINANCE-REQUEST-TEST', 'status' => 'completed',
            'estimated_cost_usd' => 0.20, 'actual_cost_usd' => 0.25,
            'submitted_at' => now(), 'completed_at' => now(),
        ]);

        $snapshot = FinanceOrderSnapshot::query()->where('order_id', $order->id)->firstOrFail();
        self::assertSame(100000.0, (float) $snapshot->allocated_revenue_toman);
        self::assertSame(16250.0, (float) $snapshot->direct_cost_toman);
        self::assertSame('test/model-primary', $snapshot->primary_model);
        self::assertSame(['test/model-fallback'], $snapshot->fallback_models);
        self::assertDatabaseHas('finance_transactions', [
            'source_key' => 'ai-request:' . AiProviderRequest::query()->firstOrFail()->id,
            'amount_toman' => 16250,
        ]);
    }

    public function test_purchase_case_tracks_gift_upgrade_and_exact_order_economics(): void
    {
        FinanceExchangeRate::query()->updateOrCreate(
            ['currency' => 'USD', 'rate_date' => now()->toDateString()],
            ['rate_to_irr' => 650000, 'rate_to_toman' => 65000, 'source' => 'test', 'is_manual' => true],
        );
        $admin = $this->admin('finance', 'case');
        $plan = Plan::query()->firstOrFail();
        $user = User::query()->create([
            'name' => 'کاربر پرونده', 'email' => 'finance-case@example.test', 'password' => 'test-password',
            'status' => 'active', 'plan_id' => $plan->id, 'tokens' => 100, 'tokens_purchased' => 100,
        ]);
        $purchase = PlanPurchase::query()->create([
            'order_number' => 'CASE-PURCHASE-001', 'user_id' => $user->id, 'plan_id' => $plan->id,
            'plan_code' => $plan->plan_code, 'plan_name' => $plan->name, 'paid_amount' => 1_000_000,
            'granted_tokens' => 100, 'plan_snapshot' => ['model_tier_key' => $plan->model_tier_key, 'tokens' => 100],
            'status' => 'completed', 'payment_reference' => 'CASE-PAYMENT-001', 'purchased_at' => now()->subMinute(),
        ]);
        $case = FinanceCase::query()->where('anchor_plan_purchase_id', $purchase->id)->firstOrFail();

        $this->actingAs($admin, 'admin')->postJson(route('admin.api.users.token.update', $user), [
            'action' => 'add', 'amount' => 10, 'credit_kind' => 'plan_upgrade', 'note' => 'هدیه تست',
        ])->assertOk()->assertJsonPath('new_balance', 110);
        $giftLog = TokenLog::query()->where('user_id', $user->id)->latest('id')->firstOrFail();
        self::assertSame('plan_upgrade', data_get($giftLog->metadata, 'credit_kind'));
        self::assertSame(10, $user->fresh()->promotionalTokenBalance());
        app(FinanceCaseLedgerService::class)->recordPlanChange($user, null, $plan, $admin->id);

        $product = Product::query()->create([
            'name_fa' => 'محصول پرونده', 'name_en' => 'Case product', 'slug' => 'case-product-test',
            'category' => 'test', 'thumbnail' => 'test.jpg', 'primary_model' => 'test/model', 'ai_provider' => 'test-provider',
            'prompt_template' => 'test', 'credit_cost' => 20, 'status' => 'active',
        ]);
        $order = Order::query()->create([
            'user_id' => $user->id, 'product_id' => $product->id, 'plan_id' => $plan->id,
            'status' => 'processing', 'payment_status' => 'paid', 'processing_status' => 'processing',
            'original_credits' => 20, 'final_credits' => 20, 'ai_model' => 'test/model', 'ai_provider' => 'test-provider',
        ]);
        $reservation = app(CreditWalletService::class)->reserve($user, 20, true, $order);
        app(CreditWalletService::class)->settle($user, $reservation, 20);
        AiProviderRequest::query()->create([
            'provider' => 'test-provider', 'order_id' => $order->id, 'external_request_id' => 'CASE-REQUEST-001',
            'status' => 'completed', 'estimated_cost_usd' => 0.20, 'actual_cost_usd' => 0.25,
            'submitted_at' => now(), 'completed_at' => now(),
        ]);
        $order->update(['status' => 'completed', 'processing_status' => 'completed', 'completed_at' => now()]);

        $analysis = app(FinanceCaseAnalysisService::class)->analyze($case->fresh());
        self::assertSame(10, $analysis['metrics']['credits_gift']);
        self::assertSame(20, $analysis['metrics']['credits_used']);
        self::assertSame(100000.0, $analysis['metrics']['recognized_revenue']);
        self::assertSame(16250.0, $analysis['metrics']['direct_cost_toman']);
        self::assertSame(37250.0, $analysis['products']->first()['vatan_cost']);
        self::assertDatabaseHas('finance_case_events', ['finance_case_id' => $case->id, 'event_type' => 'plan_changed']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.finance.cases.show', $case))
            ->assertOk()
            ->assertSee('تصویر اقتصادی خرید')
            ->assertSee('بهای تمام‌شده محصولات');
    }

    public function test_csv_report_is_downloadable(): void
    {
        $viewer = $this->admin('admin', 'csv');
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.finance.export', ['report' => 'daily']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_investment_is_cash_received_but_not_operating_profit(): void
    {
        foreach ([
            ['income', 'other_income', 500],
            ['income', 'investment', 1000],
            ['expense', 'tools', 100],
        ] as [$direction, $category, $amount]) {
            FinanceTransaction::query()->create([
                'direction' => $direction,
                'category' => $category,
                'title' => 'آزمون سود عملیاتی',
                'amount_original' => $amount,
                'currency' => 'IRR',
                'exchange_rate_irr' => 1,
                'amount_irr' => $amount,
                'exchange_rate_toman' => 1,
                'amount_toman' => $amount,
                'status' => 'paid',
                'occurred_at' => now(),
                'source_type' => 'manual',
            ]);
        }

        $row = app(FinanceReportService::class)
            ->summary('daily', now()->startOfDay(), now()->endOfDay())
            ->firstOrFail();

        self::assertSame(500.0, $row['revenue']);
        self::assertSame(100.0, $row['allocated_cost']);
        self::assertSame(400.0, $row['profit']);
    }

    private function admin(string $role, string $suffix): Admin
    {
        return Admin::query()->create([
            'name' => 'مدیر تست مالی',
            'email' => "finance-{$suffix}@example.test",
            'phone' => '09120000' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT),
            'password' => 'test-password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function transactionPayload(): array
    {
        return [
            'direction' => 'expense',
            'category' => 'server',
            'title' => 'هزینه آزمایشی مسیر اصلی',
            'amount_original' => 1200000,
            'currency' => 'IRT',
            'status' => 'paid',
            'occurred_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
