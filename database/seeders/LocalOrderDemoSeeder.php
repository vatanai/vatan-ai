<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LocalOrderDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new \RuntimeException('این Seeder فقط برای محیط local قابل اجرا است.');
        }

        $products = Product::query()->orderBy('id')->get();
        if ($products->isEmpty()) {
            throw new \RuntimeException('برای ساخت سفارش نمونه، حداقل یک محصول لازم است.');
        }

        DB::transaction(function () use ($products) {
            $users = $this->seedUsers();
            $discounts = $this->seedDiscounts($products);
            $this->seedOrders($users, $products, $discounts);
        });
    }

    private function seedUsers(): array
    {
        $rows = [
            ['سارا', 'احمدی', '09120001001', 'active', 86, 160, 74],
            ['محمد', 'رضوی', '09120001002', 'active', 42, 90, 48],
            ['نیلوفر', 'کریمی', '09120001003', 'active', 115, 220, 105],
            ['علیرضا', 'موسوی', '09120001004', 'suspended', 20, 75, 55],
            ['زهرا', 'حسینی', '09120001005', 'active', 64, 140, 76],
            ['رضا', 'تهرانی', '09120001006', 'active', 31, 80, 49],
            ['فاطمه', 'نوری', '09120001007', 'active', 150, 240, 90],
            ['امیر', 'شریفی', '09120001008', 'active', 9, 60, 51],
            ['مریم', 'اکبری', '09120001009', 'active', 55, 120, 65],
            ['کیان', 'فرهمند', '09120001010', 'active', 73, 130, 57],
        ];

        $users = [];
        foreach ($rows as $index => [$name, $lastName, $phone, $status, $tokens, $purchased, $used]) {
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $users[] = User::updateOrCreate(
                ['email' => "demo.order.{$number}@vatan.local"],
                [
                    'name' => $name, 'last_name' => $lastName, 'phone' => $phone,
                    'password' => Hash::make('Demo12345!'), 'status' => $status,
                    'tokens' => $tokens, 'tokens_purchased' => $purchased,
                    'tokens_used' => $used, 'referral_earnings' => $index % 3 === 0 ? 125000 : 0,
                ]
            );
        }
        return $users;
    }

    private function seedDiscounts($products): array
    {
        $firstProduct = [$products->first()->id];
        $selectedProducts = $products->take(3)->pluck('id')->all();
        $rows = [
            ['WELCOME20', 'خوش‌آمدگویی کاربران جدید', 'percent', 20, 15, 10, 200, 1, 'all', null, true, true, now()->subDays(10), now()->addMonths(2)],
            ['PORTRAIT10', 'تخفیف محصولات پرتره', 'fixed', 10, null, 15, 80, 2, 'products', $selectedProducts, false, true, now()->subDays(5), now()->addMonth()],
            ['FREEDEMO', 'یک ساخت رایگان آزمایشی', 'free', 0, null, 0, 25, 1, 'products', $firstProduct, false, true, now()->subDay(), now()->addDays(7)],
            ['SUMMER35', 'کمپین تابستانه', 'percent', 35, 20, 20, 150, 3, 'all', null, false, true, now()->subDays(2), now()->addDays(20)],
            ['EXPIRED15', 'کمپین پایان‌یافته نمونه', 'percent', 15, 8, 10, 50, 1, 'all', null, false, false, now()->subMonths(2), now()->subMonth()],
        ];

        $discounts = [];
        foreach ($rows as [$code,$name,$type,$value,$cap,$minimum,$limit,$perUser,$scope,$productIds,$firstOnly,$active,$starts,$ends]) {
            $discounts[$code] = Discount::updateOrCreate(['code' => $code], [
                'name' => $name, 'type' => $type, 'value' => $value,
                'max_discount_credits' => $cap, 'min_order_credits' => $minimum,
                'usage_limit' => $limit, 'usage_limit_per_user' => $perUser,
                'scope' => $scope, 'product_ids' => $productIds,
                'category_ids' => null, 'first_order_only' => $firstOnly,
                'is_active' => $active, 'starts_at' => $starts, 'ends_at' => $ends,
                'description' => 'داده نمایشی لوکال برای بررسی کامل رابط و گزارش‌ها.',
            ]);
        }
        return $discounts;
    }

    private function seedOrders(array $users, $products, array $discounts): void
    {
        $cases = [
            ['completed','paid','completed',12,2,'WELCOME20',1,null,now()->subDays(9),18_400],
            ['processing','paid','processing',20,0,null,1,null,now()->subMinutes(4),null],
            ['review','paid','failed',15,0,null,2,'پاسخ مدل در زمان مقرر دریافت نشد.',now()->subHours(3),62_000],
            ['pending','pending','queued',18,0,null,0,null,now()->subMinutes(18),null],
            ['completed','partially_refunded','completed',20,5,'PORTRAIT10',1,null,now()->subDays(4),27_300],
            ['cancelled','refunded','stopped',15,15,'FREEDEMO',0,'لغو به درخواست کاربر.',now()->subDays(6),null],
            ['processing','paid','retrying',18,6,'SUMMER35',3,null,now()->subHours(1),null],
            ['review','failed','stopped',12,0,null,1,'رزرو اعتبار ناموفق بود.',now()->subDays(2),null],
            ['completed','paid','completed',0,0,null,1,null,now()->subDay(),8_900],
            ['completed','paid','completed',20,7,'SUMMER35',1,null,now()->subHours(7),34_600],
        ];

        foreach ($cases as $i => [$status,$payment,$processing,$original,$discountAmount,$discountCode,$attempts,$error,$created,$duration]) {
            $number = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $product = $products[$i % $products->count()];
            $discount = $discountCode ? $discounts[$discountCode] : null;
            $final = max(0, $original - $discountAmount);
            $refunded = $payment === 'refunded' ? $final : ($payment === 'partially_refunded' ? min(4, $final) : 0);

            $order = Order::updateOrCreate(['order_number' => "DEMO-ORD-{$number}"], [
                'user_id' => $users[$i]->id, 'product_id' => $product->id,
                'discount_id' => $discount?->id, 'discount_code' => $discount?->code,
                'status' => $status, 'payment_status' => $payment, 'processing_status' => $processing,
                'original_credits' => $original, 'discount_credits' => $discountAmount,
                'final_credits' => $final, 'refunded_credits' => $refunded,
                'payment_reference' => $payment === 'failed' ? null : 'LOCAL-' . (720010 + $i),
                'ai_model' => $product->primary_model, 'ai_provider' => 'OpenRouter',
                'queue_duration_ms' => in_array($processing, ['queued','stopped']) ? null : 1200 + ($i * 350),
                'processing_duration_ms' => $duration, 'attempts' => $attempts,
                'input_payload' => ['demo' => true, 'aspect_ratio' => $i % 2 ? '4:5' : '1:1', 'quality' => $i % 3 ? '2K' : '1K'],
                'output_payload' => $processing === 'completed' ? [['title' => 'خروجی نمونه', 'path' => null]] : null,
                'error_message' => $error, 'admin_note' => $i === 2 ? 'نیازمند بررسی وضعیت سرویس‌دهنده.' : null,
                'source' => ['app','direct','blogger:@vatan_demo','campaign:social'][$i % 4],
                'paid_at' => in_array($payment, ['paid','partially_refunded','refunded']) ? $created->copy()->addMinute() : null,
                'processing_started_at' => in_array($processing, ['processing','completed','failed','retrying']) ? $created->copy()->addMinutes(2) : null,
                'completed_at' => $processing === 'completed' ? $created->copy()->addMinutes(4) : null,
                'cancelled_at' => $status === 'cancelled' ? $created->copy()->addMinutes(6) : null,
                'refunded_at' => $refunded > 0 ? $created->copy()->addMinutes(8) : null,
                'created_at' => $created, 'updated_at' => $created->copy()->addMinutes(9),
            ]);

            $order->events()->delete();
            $order->events()->create(['type' => 'created', 'title' => 'سفارش ثبت شد', 'description' => 'سفارش نمونه از مسیر واقعی اپلیکیشن ثبت شد.', 'created_at' => $created]);
            if ($processing === 'completed') $order->events()->create(['type' => 'completed', 'title' => 'پردازش تکمیل شد', 'description' => 'خروجی با موفقیت تولید و ذخیره شد.', 'created_at' => $created->copy()->addMinutes(4)]);
            if ($processing === 'failed') $order->events()->create(['type' => 'failed', 'title' => 'پردازش ناموفق بود', 'description' => $error, 'created_at' => $created->copy()->addMinutes(5)]);
            if ($status === 'cancelled') $order->events()->create(['type' => 'cancel', 'title' => 'سفارش لغو شد', 'description' => $error, 'created_at' => $created->copy()->addMinutes(6)]);
            if ($refunded > 0) $order->events()->create(['type' => 'refund', 'title' => $payment === 'refunded' ? 'بازپرداخت کامل' : 'بازپرداخت جزئی', 'description' => "{$refunded} اعتبار به کاربر بازگردانده شد.", 'metadata' => ['credits' => $refunded], 'created_at' => $created->copy()->addMinutes(8)]);
        }

        foreach ($discounts as $discount) {
            $discount->update(['used_count' => Order::where('discount_id', $discount->id)->count()]);
        }
    }
}
