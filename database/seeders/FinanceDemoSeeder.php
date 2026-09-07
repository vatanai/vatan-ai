<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\FinanceCostCenter;
use App\Models\FinanceExchangeRate;
use App\Models\FinanceOrderSnapshot;
use App\Models\FinancePaymentMethod;
use App\Models\FinancePlanSnapshot;
use App\Models\FinanceTransaction;
use App\Models\FinanceVendor;
use App\Models\Product;
use App\Services\Finance\FinanceProfitCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FinanceDemoSeeder extends Seeder
{
    public function run(FinanceProfitCalculator $calculator): void
    {
        if (! Schema::hasTable('finance_transactions')) {
            $this->command?->warn('ابتدا مایگریشن‌های مالی را اجرا کنید.');
            return;
        }

        $adminId = Admin::query()->where('role', 'leader')->value('id');
        $vendor = FinanceVendor::query()->firstOrCreate(
            ['name' => 'تأمین‌کننده آزمایشی وطن'],
            ['contact_name' => 'واحد فروش', 'notes' => 'رکورد نمونه فاز اول مالی', 'is_active' => true],
        );
        $methodId = FinancePaymentMethod::query()->where('code', 'bank-transfer')->value('id');
        $centers = FinanceCostCenter::query()->pluck('id', 'code');

        foreach ([
            [2, 635000], [1, 642000], [0, 650000],
        ] as [$daysAgo, $rate]) {
            FinanceExchangeRate::query()->updateOrCreate(
                ['currency' => 'USD', 'rate_date' => now()->subDays($daysAgo)->toDateString()],
                ['rate_to_irr' => $rate, 'source' => 'داده آزمایشی', 'is_manual' => true, 'created_by' => $adminId],
            );
        }

        $rows = [
            ['other_income', 'income', 'درآمد مشاوره طراحی محصول', 85000000, 'paid', 'sales-finance', 2],
            ['server', 'expense', 'سرور پردازش ماه جاری', 42000000, 'paid', 'infrastructure', 7],
            ['salary', 'expense', 'حقوق تیم تولید', 185000000, 'paid', 'workforce', 5],
            ['advertising', 'expense', 'کمپین جذب کاربر شبکه اجتماعی', 36000000, 'paid', 'marketing', 4],
            ['tools', 'expense', 'اشتراک ابزارهای طراحی', 12500000, 'paid', 'general', 3],
            ['tax', 'expense', 'ذخیره مالیات دوره', 28000000, 'pending', 'sales-finance', 1],
            ['refund', 'expense', 'بازپرداخت سفارش آزمایشی', 4500000, 'paid', 'sales-finance', 1],
            ['investment', 'income', 'تزریق سرمایه بنیان‌گذار', 300000000, 'paid', 'sales-finance', 10],
        ];

        foreach ($rows as $index => [$category, $direction, $title, $amount, $status, $center, $daysAgo]) {
            FinanceTransaction::withTrashed()->updateOrCreate(
                ['source_key' => "demo-finance:{$index}"],
                [
                    'direction' => $direction,
                    'category' => $category,
                    'title' => $title,
                    'amount_original' => $amount,
                    'currency' => 'IRR',
                    'exchange_rate_irr' => 1,
                    'amount_irr' => $amount,
                    'status' => $status,
                    'occurred_at' => now()->subDays($daysAgo),
                    'due_at' => $status === 'pending' ? now()->addDays(10)->toDateString() : null,
                    'paid_at' => $status === 'paid' ? now()->subDays($daysAgo) : null,
                    'cost_center_id' => $centers[$center] ?? null,
                    'vendor_id' => in_array($category, ['server', 'tools'], true) ? $vendor->id : null,
                    'payment_method_id' => $methodId,
                    'notes' => 'نمونه آزمایشی برای بررسی رابط و گزارش‌های مالی',
                    'tags' => ['آزمایشی', 'فاز اول'],
                    'source_type' => 'manual',
                    'source_id' => null,
                    'source_key' => "demo-finance:{$index}",
                    'created_by' => $adminId,
                    'deleted_at' => null,
                ],
            );
        }

        $planSnapshot = FinancePlanSnapshot::query()->first();
        $products = Product::query()->limit(2)->get();
        $rate = (float) FinanceExchangeRate::query()->latest('rate_date')->value('rate_to_irr');
        $demoOrders = [
            [9800001, 'DEMO-FIN-001', 'ساخت تصویر تبلیغاتی', 'fal-ai/flux/dev', 'fal-ai', 12, 120000, 0.10, 0.12, 'organic', 2],
            [9800002, 'DEMO-FIN-002', 'رندر محصول پرهزینه', 'openai/gpt-image-1', 'openai', 25, 250000, 0.35, 0.46, 'paid-social', 1],
        ];

        foreach ($demoOrders as $index => [$orderId, $number, $productName, $model, $provider, $credits, $revenue, $estimatedUsd, $actualUsd, $channel, $daysAgo]) {
            $product = $products->get($index);
            $directCost = round($actualUsd * $rate, 2);
            FinanceOrderSnapshot::query()->updateOrCreate(
                ['order_id' => $orderId],
                $calculator->order($revenue, $directCost, 8, 12) + [
                    'plan_purchase_id' => $planSnapshot?->plan_purchase_id,
                    'plan_id' => $planSnapshot?->plan_id,
                    'product_id' => $product?->id,
                    'user_id' => $planSnapshot?->user_id,
                    'order_number' => $number,
                    'plan_name' => $planSnapshot?->plan_name ?: 'پلن نمونه',
                    'model_tier_key' => $planSnapshot?->model_tier_key ?: 'economy',
                    'product_name' => $product?->name_fa ?: $productName,
                    'primary_model' => $product?->primary_model ?: $model,
                    'fallback_models' => $product?->fallback_models ?: [],
                    'actual_model' => $model,
                    'provider' => $provider,
                    'credits_used' => $credits,
                    'estimated_cost_usd' => $estimatedUsd,
                    'actual_cost_usd' => $actualUsd,
                    'exchange_rate_irr' => $rate,
                    'estimated_cost_irr' => round($estimatedUsd * $rate, 2),
                    'actual_cost_irr' => $directCost,
                    'acquisition_channel' => $channel,
                    'formula_version' => FinanceProfitCalculator::FORMULA_VERSION,
                    'source_snapshot' => ['demo' => true, 'order_number' => $number],
                    'ordered_at' => now()->subDays($daysAgo),
                    'completed_at' => now()->subDays($daysAgo),
                    'captured_at' => now(),
                ],
            );
        }
    }
}
