<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\AiProviderRequest;
use App\Models\AuthEvent;
use App\Models\FinanceCase;
use App\Models\FinanceExchangeRate;
use App\Models\GeneratedImage;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanPurchase;
use App\Models\Product;
use App\Models\TokenLog;
use App\Models\User;
use App\Services\CreditWalletService;
use App\Services\Finance\FinanceCaseLedgerService;
use App\Services\Finance\FinanceSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class FinanceCaseDemoSeeder extends Seeder
{
    public function run(
        FinanceCaseLedgerService $ledger,
        FinanceSyncService $financeSync,
        CreditWalletService $wallet,
    ): void {
        if (! Schema::hasTable('finance_cases')) {
            $this->command?->warn('ابتدا مایگریشن پرونده‌های مالی را اجرا کنید.');
            return;
        }

        $plans = Plan::query()->published()->where('price', '>', 0)->orderBy('price')->get();
        $products = Product::query()->where('status', 'active')->orderBy('id')->limit(6)->get();
        if ($plans->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('برای ساخت داده آزمایشی حداقل یک پلن پولی و یک محصول فعال لازم است.');
            return;
        }

        $rateToman = 105000;
        FinanceExchangeRate::query()->updateOrCreate(
            ['currency' => 'USD', 'rate_date' => now()->toDateString()],
            [
                'rate_to_irr' => $rateToman * 10,
                'rate_to_toman' => $rateToman,
                'source' => 'نرخ کنترل‌شده پرونده‌های آزمایشی',
                'is_manual' => true,
            ],
        );

        $scenarios = [
            [
                'key' => 'profit',
                'name' => 'آزمایش سودده',
                'plan' => $plans->first(),
                'gift' => 80,
                'gift_kind' => 'gift',
                'note' => 'پرونده نمونه با سود مثبت و ترکیب هزینه قطعی و تخمینی',
                'runs' => [
                    ['credits' => 70, 'estimated' => 0.16, 'actual' => 0.18, 'status' => 'completed', 'outputs' => 2, 'attempts' => 1],
                    ['credits' => 110, 'estimated' => 0.42, 'actual' => null, 'status' => 'completed', 'outputs' => 1, 'attempts' => 1],
                    ['credits' => 90, 'estimated' => 0.31, 'actual' => 0.36, 'status' => 'completed', 'outputs' => 2, 'attempts' => 2],
                ],
            ],
            [
                'key' => 'loss',
                'name' => 'آزمایش زیان‌ده',
                'plan' => $plans->first(),
                'gift' => 40,
                'gift_kind' => 'gift',
                'note' => 'پرونده نمونه پرهزینه برای نمایش هشدار زیان و اجرای ناموفق',
                'runs' => [
                    ['credits' => 120, 'estimated' => 2.55, 'actual' => 2.80, 'status' => 'completed', 'outputs' => 1, 'attempts' => 2],
                    ['credits' => 130, 'estimated' => 2.20, 'actual' => 2.35, 'status' => 'completed', 'outputs' => 1, 'attempts' => 3],
                    ['credits' => 80, 'estimated' => 0.75, 'actual' => 0.85, 'status' => 'failed', 'outputs' => 0, 'attempts' => 2],
                ],
            ],
            [
                'key' => 'upgrade',
                'name' => 'آزمایش ارتقای دستی',
                'plan' => $plans->get(1) ?: $plans->first(),
                'upgrade_plan' => $plans->last(),
                'gift' => 900,
                'gift_kind' => 'plan_upgrade',
                'note' => 'پرونده نمونه شامل خرید، ارتقای دستی پلن، اعتبار هدیه و داده ناقص',
                'runs' => [
                    ['credits' => 400, 'estimated' => 0.54, 'actual' => 0.60, 'status' => 'completed', 'outputs' => 2, 'attempts' => 1],
                    ['credits' => 500, 'estimated' => 0.82, 'actual' => null, 'status' => 'completed', 'outputs' => 3, 'attempts' => 2],
                    ['credits' => 600, 'estimated' => null, 'actual' => null, 'status' => 'completed', 'outputs' => 1, 'attempts' => 1],
                ],
            ],
            [
                'key' => 'business_scale',
                'name' => 'آزمایش مصرف سازمانی',
                'plan' => $plans->last(),
                'gift' => 500,
                'gift_kind' => 'gift',
                'note' => 'پرونده سازمانی با مصرف سنگین چند محصول و هزینه‌های قطعی برای ارزیابی سود در مقیاس بالا',
                'runs' => [
                    ['credits' => 1800, 'estimated' => 4.55, 'actual' => 4.80, 'status' => 'completed', 'outputs' => 4, 'attempts' => 2],
                    ['credits' => 2400, 'estimated' => 6.20, 'actual' => 6.60, 'status' => 'completed', 'outputs' => 5, 'attempts' => 2],
                    ['credits' => 3200, 'estimated' => 8.90, 'actual' => 9.20, 'status' => 'completed', 'outputs' => 6, 'attempts' => 3],
                ],
            ],
            [
                'key' => 'mixed_wallet',
                'name' => 'آزمایش کیف پول ترکیبی',
                'plan' => $plans->get(1) ?: $plans->first(),
                'gift' => 350,
                'gift_kind' => 'gift',
                'note' => 'پرونده با اعتبار خریداری‌شده و هدیه، خروجی‌های متعدد و یک تلاش ناموفق بازگشت‌خورده',
                'runs' => [
                    ['credits' => 720, 'estimated' => 1.10, 'actual' => 1.20, 'status' => 'completed', 'outputs' => 3, 'attempts' => 1],
                    ['credits' => 880, 'estimated' => 1.65, 'actual' => null, 'status' => 'completed', 'outputs' => 4, 'attempts' => 2],
                    ['credits' => 640, 'estimated' => 1.25, 'actual' => 1.30, 'status' => 'failed', 'outputs' => 0, 'attempts' => 2],
                ],
            ],
        ];

        foreach ($scenarios as $scenarioIndex => $scenario) {
            $plan = $scenario['plan'];
            $email = "finance.demo.{$scenario['key']}@vatan.local";
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $scenario['name'],
                    'last_name' => 'وطن',
                    'phone' => '09000000' . str_pad((string) ($scenarioIndex + 1), 3, '0', STR_PAD_LEFT),
                    'password' => Hash::make('finance-demo-only'),
                    'status' => 'active',
                    'customer_segment' => 'regular',
                    'tokens' => 0,
                    'tokens_purchased' => 0,
                    'tokens_used' => 0,
                    'promotional_tokens' => 0,
                    'plan_id' => $plan->id,
                    'registered_at' => now()->subDays(8 - $scenarioIndex),
                ],
            );

            $demoLoginAt = now()->subHours(2 + ($scenarioIndex * 5));
            $user->forceFill([
                'registered_at' => $user->registered_at ?: now()->subDays(8 - $scenarioIndex),
                'last_login_at' => $demoLoginAt,
                'login_count' => 4 + ($scenarioIndex * 3),
            ])->save();
            if (Schema::hasTable('auth_events')) {
                AuthEvent::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'event' => 'login_success',
                        'session_id' => 'finance-demo-login-' . $scenario['key'],
                    ],
                    [
                        'phone' => $user->phone,
                        'method' => $scenarioIndex % 2 === 0 ? 'sms_otp' : 'password',
                        'successful' => true,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Finance case controlled local test',
                        'metadata' => ['is_demo' => true, 'scenario' => $scenario['key']],
                        'occurred_at' => $demoLoginAt,
                    ],
                );
            }

            $purchaseNumber = 'FIN-DEMO-' . strtoupper($scenario['key']);
            $existingPurchase = PlanPurchase::query()->where('order_number', $purchaseNumber)->first();
            if ($existingPurchase?->financeCase) {
                $this->command?->line("پرونده {$purchaseNumber} از قبل وجود دارد و دوباره ساخته نشد.");
                continue;
            }

            $user->forceFill([
                'plan_id' => $plan->id,
                'tokens' => (int) $plan->tokens,
                'tokens_purchased' => (int) $plan->tokens,
                'tokens_used' => 0,
                'promotional_tokens' => 0,
            ])->save();

            $purchase = PlanPurchase::query()->updateOrCreate(
                ['order_number' => $purchaseNumber],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'plan_code' => $plan->plan_code,
                    'plan_name' => $plan->name,
                    'customer_segment' => 'regular',
                    'paid_amount' => (int) $plan->price,
                    'granted_tokens' => (int) $plan->tokens,
                    'plan_snapshot' => [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'tokens' => (int) $plan->tokens,
                        'price' => (int) $plan->price,
                        'model_tier_key' => $plan->model_tier_key,
                    ],
                    'status' => PlanPurchase::COMPLETED,
                    'gateway' => 'demo',
                    'gateway_reference' => 'TEST-' . strtoupper($scenario['key']),
                    'payment_reference' => 'finance-demo-payment-' . $scenario['key'],
                    'initiated_at' => now()->subDays(7 - $scenarioIndex),
                    'verified_at' => now()->subDays(7 - $scenarioIndex),
                    'purchased_at' => now()->subDays(7 - $scenarioIndex),
                ],
            );

            $case = $ledger->recordPurchase($purchase);
            $case?->update([
                'is_test' => true,
                'notes' => $scenario['note'],
                'metadata' => array_merge((array) $case->metadata, ['demo_key' => $scenario['key']]),
            ]);

            $this->grantDemoCredit($user, (int) $scenario['gift'], $scenario['gift_kind'], $scenario['key'], $ledger);

            if (! empty($scenario['upgrade_plan']) && $scenario['upgrade_plan']->id !== $plan->id) {
                $upgradePlan = $scenario['upgrade_plan'];
                $user->plan()->associate($upgradePlan);
                $user->save();
                $ledger->recordPlanChange($user->fresh(), $plan, $upgradePlan);
            }

            foreach ($scenario['runs'] as $runIndex => $run) {
                $product = $products->get(($scenarioIndex + $runIndex) % $products->count());
                $model = AiModel::query()
                    ->where('provider', $product->ai_provider)
                    ->where('openrouter_model_id', $product->primary_model)
                    ->first()
                    ?: AiModel::query()->where('is_active', true)->first();
                $order = Order::query()->create([
                    'order_number' => sprintf('FIN-%s-%02d', strtoupper($scenario['key']), $runIndex + 1),
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'processing_status' => 'processing',
                    'original_credits' => $run['credits'],
                    'final_credits' => $run['credits'],
                    'ai_model' => $model?->externalModelId() ?: $product->primary_model,
                    'ai_provider' => $model?->provider ?: $product->ai_provider,
                    'attempts' => $run['attempts'],
                    'source' => 'finance_demo',
                    'paid_at' => now(),
                    'processing_started_at' => now()->subSeconds(18 + ($runIndex * 7)),
                    'plan_id' => $user->plan_id,
                    'plan_name' => $user->plan?->name ?: $plan->name,
                    'model_tier_key' => $user->plan?->model_tier_key ?: $plan->model_tier_key,
                    'model_tier_name' => $user->plan?->name ?: $plan->name,
                ]);

                $reservation = $wallet->reserve($user, (int) $run['credits'], true, $order);
                $providerRequest = AiProviderRequest::query()->create([
                    'provider' => $model?->provider ?: ($product->ai_provider ?: 'demo'),
                    'ai_model_id' => $model?->id,
                    'order_id' => $order->id,
                    'external_request_id' => "finance-demo-{$scenario['key']}-{$runIndex}",
                    'status' => $run['status'] === 'completed' ? 'completed' : 'failed',
                    'estimated_cost_usd' => $run['estimated'],
                    'actual_cost_usd' => $run['actual'],
                    'error_code' => $run['status'] === 'failed' ? 'DEMO_PROVIDER_FAILURE' : null,
                    'error_message' => $run['status'] === 'failed' ? 'شکست کنترل‌شده برای تحلیل هزینه تلاش ناموفق' : null,
                    'submitted_at' => now()->subSeconds(15 + ($runIndex * 6)),
                    'completed_at' => now()->subSeconds(2),
                    'raw_response' => ['is_demo' => true, 'scenario' => $scenario['key']],
                ]);

                $usedCredits = $run['status'] === 'completed' ? (int) $run['credits'] : 0;
                $settled = $wallet->settle($user, $reservation, $usedCredits);
                $order->update([
                    'status' => $run['status'] === 'completed' ? 'completed' : 'review',
                    'processing_status' => $run['status'],
                    'final_credits' => $usedCredits,
                    'promotional_credits_used' => $settled['promotional'],
                    'paid_credits_used' => $settled['paid'],
                    'processing_duration_ms' => 12000 + ($runIndex * 6500),
                    'completed_at' => now(),
                    'error_message' => $run['status'] === 'failed' ? 'شکست آزمایشی سرویس‌دهنده' : null,
                ]);

                for ($outputIndex = 0; $outputIndex < (int) $run['outputs']; $outputIndex++) {
                    GeneratedImage::query()->create([
                        'user_id' => $user->id,
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'ai_provider_request_id' => $providerRequest->id,
                        'image_path' => (string) ($product->thumbnail ?: 'finance-demo/preview.webp'),
                        'user_prompt' => 'خروجی کنترل‌شده برای ارزیابی پرونده مالی',
                        'cost' => ((float) ($run['actual'] ?? $run['estimated'] ?? 0)) / max(1, (int) $run['outputs']),
                        'size' => 1048576 + ($outputIndex * 131072),
                    ]);
                }

                $financeSync->syncOrder($order->fresh());
            }

            $this->command?->info("پرونده آزمایشی {$purchaseNumber} ساخته شد.");
        }
    }

    private function grantDemoCredit(
        User $user,
        int $credits,
        string $kind,
        string $scenarioKey,
        FinanceCaseLedgerService $ledger,
    ): void {
        if ($credits < 1) {
            return;
        }

        $before = (int) $user->fresh()->tokens;
        $user->forceFill([
            'tokens' => $before + $credits,
            'promotional_tokens' => $user->promotionalTokenBalance() + $credits,
        ])->save();
        $log = TokenLog::query()->create([
            'user_id' => $user->id,
            'action' => 'add',
            'source' => 'manual_credit',
            'event_key' => 'finance-demo-credit-' . $scenarioKey,
            'amount' => $credits,
            'balance_before' => $before,
            'balance_after' => $before + $credits,
            'note' => $kind === 'plan_upgrade' ? 'اعتبار هدیه بابت ارتقای دستی پلن' : 'اعتبار هدیه کنترل‌شده',
            'metadata' => [
                'credit_kind' => $kind,
                'is_promotional' => true,
                'delta' => $credits,
                'is_demo' => true,
            ],
        ]);
        $ledger->recordManualCredit($log);
    }
}
