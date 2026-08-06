<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $common = [
            ['title' => 'کیفیت اصلی تصاویر', 'value' => '', 'included' => 'yes', 'highlighted' => false],
            ['title' => 'بدون واترمارک', 'value' => '', 'included' => 'yes', 'highlighted' => false],
        ];

        $plans = [
            [
                'plan_code' => 'PLN-FREE',
                'slug' => 'free',
                'name' => '🎁 پلن رایگان',
                'price' => 0,
                'tokens' => 1,
                'billing_type' => 'free',
                'short_description' => 'آشنایی با کیفیت خروجی وطن',
                'icon' => 'fa-solid fa-gift',
                'badge_text' => 'شروع رایگان',
                'purchase_limit' => 1,
                'features' => [
                    ['title' => '۱ توکن هدیه', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ...$common,
                    ['title' => 'دسترسی به تمامی استایل‌های عمومی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'انتقال توکن به ماه بعد', 'value' => '', 'included' => 'no', 'highlighted' => false],
                    ['title' => 'اولویت پردازش', 'value' => '', 'included' => 'no', 'highlighted' => false],
                    ['title' => 'پشتیبانی اولویت‌دار', 'value' => '', 'included' => 'no', 'highlighted' => false],
                ],
                'loyal' => ['price' => 0, 'tokens' => 1, 'bonus_tokens' => 0],
            ],
            [
                'plan_code' => 'PLN-START',
                'slug' => 'start',
                'name' => '🚀 پلن Start',
                'price' => 299000,
                'tokens' => 10,
                'billing_type' => 'monthly',
                'short_description' => 'شروع فعالیت و استفاده شخصی',
                'icon' => 'fa-solid fa-rocket',
                'badge_text' => null,
                'features' => [
                    ['title' => '۱۰ توکن ماهانه', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ...$common,
                    ['title' => 'دسترسی به تمامی استایل‌های عمومی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'انتقال توکن‌های استفاده‌نشده به ماه بعد', 'value' => 'تا سقف ۳۰ روز', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'اولویت پردازش', 'value' => '', 'included' => 'no', 'highlighted' => false],
                    ['title' => 'پشتیبانی اولویت‌دار', 'value' => '', 'included' => 'no', 'highlighted' => false],
                ],
                'loyal' => ['price' => 269100, 'tokens' => 10, 'bonus_tokens' => 1],
            ],
            [
                'plan_code' => 'PLN-PRO',
                'slug' => 'pro',
                'name' => '⭐ پلن Pro',
                'price' => 599000,
                'tokens' => 25,
                'billing_type' => 'monthly',
                'short_description' => 'فروشندگان اینستاگرام و آنلاین‌شاپ‌ها',
                'icon' => 'fa-solid fa-star',
                'badge_text' => 'پیشنهاد ما',
                'is_featured' => true,
                'features' => [
                    ['title' => '۲۵ توکن ماهانه', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ...$common,
                    ['title' => 'دسترسی به تمامی استایل‌های عمومی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'انتقال توکن‌های استفاده‌نشده به ماه بعد', 'value' => 'تا سقف ۳۰ روز', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'اولویت در صف پردازش', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ['title' => 'پردازش سریع‌تر', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'پشتیبانی اولویت‌دار', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'دسترسی زودتر به قابلیت‌های جدید', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                ],
                'loyal' => ['price' => 539100, 'tokens' => 25, 'bonus_tokens' => 3],
            ],
            [
                'plan_code' => 'PLN-PREMIUM',
                'slug' => 'premium',
                'name' => '💎 پلن Premium',
                'price' => 1199000,
                'tokens' => 60,
                'billing_type' => 'monthly',
                'short_description' => 'کاربران حرفه‌ای و برندهای در حال رشد',
                'icon' => 'fa-solid fa-gem',
                'badge_text' => null,
                'features' => [
                    ['title' => '۶۰ توکن ماهانه', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ...$common,
                    ['title' => 'دسترسی به تمامی استایل‌های عمومی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'انتقال توکن‌های استفاده‌نشده به ماه بعد', 'value' => 'تا سقف ۳۰ روز', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'اولویت پردازش بالا', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ['title' => 'پشتیبانی اولویت‌دار', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'دسترسی زودتر به قابلیت‌های جدید', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                ],
                'loyal' => ['price' => 1079100, 'tokens' => 60, 'bonus_tokens' => 6],
            ],
            [
                'plan_code' => 'PLN-BUSINESS',
                'slug' => 'business',
                'name' => '🏢 پلن Business',
                'price' => 3999000,
                'tokens' => 200,
                'billing_type' => 'monthly',
                'short_description' => 'فروشگاه‌های اینترنتی و تیم‌های تولید محتوا',
                'icon' => 'fa-solid fa-building',
                'badge_text' => null,
                'features' => [
                    ['title' => '۲۰۰ توکن ماهانه', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ...$common,
                    ['title' => 'انتقال توکن‌های استفاده‌نشده به ماه بعد', 'value' => 'تا سقف ۳۰ روز', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'اولویت پردازش بسیار بالا', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ['title' => 'تولید انبوه', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'پشتیبانی ویژه', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'اعضای تیم', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'گزارش مصرف', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'دسترسی زودتر به قابلیت‌های جدید', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                ],
                'loyal' => ['price' => 3599100, 'tokens' => 200, 'bonus_tokens' => 20],
            ],
            [
                'plan_code' => 'PLN-ENTERPRISE',
                'slug' => 'enterprise',
                'name' => '👑 پلن Enterprise',
                'price' => 9999000,
                'price_prefix' => 'از',
                'tokens' => 0,
                'token_label' => 'توکن نامحدود* (بر اساس سیاست استفاده منصفانه)',
                'is_unlimited' => true,
                'billing_type' => 'custom',
                'short_description' => 'سازمان‌ها، آژانس‌ها و کسب‌وکارهای بزرگ',
                'icon' => 'fa-solid fa-crown',
                'badge_text' => 'سازمانی',
                'features' => [
                    ['title' => 'توکن نامحدود*', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ['title' => 'انتقال توکن', 'value' => 'در صورت تعریف سقف قراردادی', 'included' => 'limited', 'highlighted' => false],
                    ['title' => 'بالاترین اولویت پردازش', 'value' => '', 'included' => 'yes', 'highlighted' => true],
                    ['title' => 'ظرفیت پردازشی اختصاصی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'تولید انبوه', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'کاربران نامحدود', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'شخصی‌سازی سرویس', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'مدیر حساب اختصاصی', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                    ['title' => 'پشتیبانی VIP', 'value' => '', 'included' => 'yes', 'highlighted' => false],
                ],
                'loyal' => ['price' => 8999100, 'tokens' => 0, 'bonus_tokens' => 0],
            ],
        ];

        foreach ($plans as $index => $plan) {
            $loyal = $plan['loyal'];
            unset($plan['loyal']);
            $features = $plan['features'];
            unset($plan['features']);

            DB::table('plans')->updateOrInsert(
                ['slug' => $plan['slug']],
                array_merge([
                    'short_description' => null,
                    'description' => null,
                    'image_path' => null,
                    'price_prefix' => null,
                    'token_label' => null,
                    'is_unlimited' => false,
                    'is_featured' => false,
                    'purchase_limit' => null,
                    'status' => 'active',
                    'sort_order' => $index + 1,
                    'version' => 1,
                    'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                    'audience_overrides' => json_encode([
                        'loyal' => array_merge($loyal, ['visible' => true, 'purchasable' => true]),
                    ], JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                    'created_at' => now(),
                ], $plan)
            );
        }
    }

    public function down(): void
    {
        DB::table('plans')->whereIn('slug', ['free', 'start', 'pro', 'premium', 'business', 'enterprise'])->delete();
    }
};
