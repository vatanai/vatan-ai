<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // پلن‌های نسخه قبلی فقط غیرفعال می‌شوند تا خریدها و تاریخچه‌شان حفظ شود.
        DB::table('plans')->whereIn('slug', ['start', 'pro', 'premium', 'business'])->update([
            'status' => 'inactive',
            'is_featured' => false,
            'updated_at' => $now,
        ]);

        $plans = [
            [
                'slug' => 'vatan-gift', 'plan_code' => 'PLN-VTGIFT', 'name' => 'هدیه وطن',
                'price' => 0, 'tokens' => 40, 'billing_type' => 'free', 'model_tier_key' => 'free',
                'icon' => 'fa-solid fa-gift', 'sort_order' => 1, 'is_featured' => false,
                'short_description' => 'برای شروع', 'description' => 'اعتبار شروع برای تجربه ساخت در وطن',
                'features' => [
                    'دسترسی به تمامی استایل‌های عمومی', 'اعتبار بدون تاریخ انقضا', 'دسترسی به دو سطح کیفیت خروجی',
                    'بهترین نرخ هر اعتبار در پلن‌های عمومی', 'اولویت پردازش بالا', 'پشتیبانی اولویت‌دار',
                ],
                'config' => [
                    'is_active' => true, 'template' => 'vatan-proposal', 'variant' => 'gift',
                    'eyebrow' => 'برای شروع', 'icon' => null, 'ribbon' => null, 'badge' => null,
                    'price' => ['prefix' => null, 'value' => 'رایگان', 'suffix' => null],
                    'credit' => '۴۰ اعتبار هدیه شروع', 'credit_detail' => null, 'credit_icon' => 'fa-solid fa-gift',
                    'estimate' => null, 'primary_stat' => ['label' => 'قیمت هر اعتبار', 'value' => '۰ تومان'],
                    'secondary_stat' => ['label' => 'صرفه‌جویی', 'value' => '۴۰٬۰۰۰ تومان هدیه', 'type' => 'saving'],
                    'button' => ['label' => 'شروع رایگان', 'action' => 'login', 'style' => 'economic'],
                ],
            ],
            [
                'slug' => 'vatan-professional', 'plan_code' => 'PLN-VTPRO', 'name' => 'حرفه‌ای',
                'price' => 485000, 'tokens' => 515, 'billing_type' => 'one_time', 'model_tier_key' => 'pro',
                'icon' => 'fa-solid fa-star', 'sort_order' => 2, 'is_featured' => true,
                'short_description' => 'برای ساخت مداوم', 'description' => 'پلن پیشنهادی عمومی وطن',
                'features' => [
                    'دسترسی به تمامی استایل‌های عمومی', 'اعتبار بدون تاریخ انقضا', 'دسترسی به هر سه کیفیت خروجی',
                    'بهترین نرخ هر اعتبار در پلن‌های عمومی', 'اولویت پردازش بالا', 'پشتیبانی اولویت‌دار',
                ],
                'config' => [
                    'is_active' => true, 'template' => 'vatan-proposal', 'variant' => 'professional',
                    'eyebrow' => 'برای ساخت مداوم', 'icon' => 'fa-solid fa-star', 'ribbon' => 'پیشنهاد وطن', 'badge' => '۱۵٪ تخفیف',
                    'price' => ['prefix' => null, 'value' => '۴۸۵٬۰۰۰', 'suffix' => 'تومان'],
                    'credit' => '۵۱۵ اعتبار', 'credit_detail' => '۴۸۵ + ۳۰ هدیه', 'credit_icon' => 'fa-solid fa-bolt',
                    'estimate' => 'حدود ۴۳ ساخت با کیفیت استاندارد', 'primary_stat' => ['label' => 'قیمت هر اعتبار', 'value' => '۹۴۲ تومان'],
                    'secondary_stat' => ['label' => 'صرفه‌جویی', 'value' => '۳۰٬۰۰۰ تومان', 'type' => 'saving'],
                    'button' => ['label' => 'انتخاب حرفه‌ای', 'action' => 'pricing', 'style' => 'primary'],
                ],
            ],
            [
                'slug' => 'vatan-advanced', 'plan_code' => 'PLN-VTADV', 'name' => 'پیشرفته',
                'price' => 2290000, 'tokens' => 2620, 'billing_type' => 'one_time', 'model_tier_key' => 'business',
                'icon' => 'fa-solid fa-gem', 'sort_order' => 3, 'is_featured' => false,
                'short_description' => 'برای تیم‌های پرکار', 'description' => 'اعتبار بیشتر با نرخ مؤثر کمتر',
                'features' => [
                    'دسترسی به تمامی استایل‌های عمومی', 'اعتبار بدون تاریخ انقضا', 'دسترسی به هر سه کیفیت خروجی',
                    'بهترین نرخ هر اعتبار در پلن‌های عمومی', 'اولویت پردازش بالا', 'پشتیبانی اولویت‌دار',
                ],
                'config' => [
                    'is_active' => true, 'template' => 'vatan-proposal', 'variant' => 'advanced',
                    'eyebrow' => 'برای تیم‌های پرکار', 'icon' => 'fa-solid fa-gem', 'ribbon' => null, 'badge' => '۲۰٪ تخفیف',
                    'price' => ['prefix' => null, 'value' => '۲٬۲۹۰٬۰۰۰', 'suffix' => 'تومان'],
                    'credit' => '۲٬۶۲۰ اعتبار', 'credit_detail' => '۲٬۲۹۰ + ۳۳۰ هدیه', 'credit_icon' => 'fa-solid fa-bolt',
                    'estimate' => 'حدود ۲۱۸ ساخت با کیفیت استاندارد', 'primary_stat' => ['label' => 'قیمت هر اعتبار', 'value' => '۸۷۴ تومان'],
                    'secondary_stat' => ['label' => 'صرفه‌جویی', 'value' => '۳۳۰٬۰۰۰ تومان', 'type' => 'saving'],
                    'button' => ['label' => 'انتخاب پیشرفته', 'action' => 'pricing', 'style' => 'economic'],
                ],
            ],
            [
                'slug' => 'vatan-business', 'plan_code' => 'PLN-VTBIZ', 'name' => 'کسب‌وکار',
                'price' => 9600000, 'tokens' => 16410, 'billing_type' => 'custom', 'model_tier_key' => 'business',
                'icon' => 'fa-solid fa-briefcase', 'sort_order' => 4, 'is_featured' => false,
                'short_description' => 'راهکار اختصاصی برای برندها', 'description' => 'راهکار اختصاصی تولید محتوای مستمر برای کسب‌وکارها',
                'features' => [
                    'دسترسی به استایل‌های اختصاصی', 'کتابخانه محصول اختصاصی برای حوزه کاری شما', 'محصول‌ها و قالب‌های ویژه برند',
                    'ارائه کیفیت 4K تمام استایل‌ها', 'اولویت پردازش و نرخ حجمی', 'دسترسی با اولویت به پشتیبان آنلاین',
                ],
                'config' => [
                    'is_active' => true, 'template' => 'vatan-proposal', 'variant' => 'business',
                    'eyebrow' => 'راهکار اختصاصی برای برندها', 'icon' => 'fa-solid fa-briefcase', 'ribbon' => null, 'badge' => 'ظرفیت تکمیل شد',
                    'price' => ['prefix' => 'شروع از', 'value' => '۹٬۶۰۰٬۰۰۰', 'suffix' => 'تومان'],
                    'credit' => 'اعتبار و نرخ حجمی اختصاصی', 'credit_detail' => null, 'credit_icon' => 'fa-solid fa-building',
                    'estimate' => 'برای برندهایی که تولید محتوای مستمر دارند', 'primary_stat' => ['label' => 'قیمت هر اعتبار', 'value' => '۵۸۵ تومان'],
                    'secondary_stat' => ['label' => 'ظرفیت پذیرش', 'value' => 'تکمیل شد', 'type' => 'capacity'],
                    'button' => ['label' => 'درخواست مشاوره', 'action' => 'contact', 'style' => 'economic'],
                ],
            ],
        ];

        foreach ($plans as $data) {
            $features = collect($data['features'])->values()->map(fn (string $title, int $index) => [
                'title' => $title, 'value' => '', 'included' => true, 'highlighted' => false, 'sort_order' => $index + 1,
            ])->all();

            $payload = [
                'plan_code' => $data['plan_code'], 'name' => $data['name'], 'price' => $data['price'], 'tokens' => $data['tokens'],
                'short_description' => $data['short_description'], 'description' => $data['description'], 'icon' => $data['icon'],
                'card_style' => 'vatan-proposal', 'badge_text' => $data['config']['badge'], 'features' => json_encode($features, JSON_UNESCAPED_UNICODE),
                'billing_type' => $data['billing_type'], 'price_prefix' => $data['config']['price']['prefix'], 'token_label' => 'اعتبار',
                'is_unlimited' => false, 'is_featured' => $data['is_featured'], 'sort_order' => $data['sort_order'],
                'status' => 'active', 'model_tier_key' => $data['model_tier_key'], 'show_model_tier' => true,
                'home_pricing_config' => json_encode($data['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => $now,
            ];

            $existing = DB::table('plans')->where('slug', $data['slug'])->first();
            if ($existing) {
                DB::table('plans')->where('id', $existing->id)->update($payload);
                continue;
            }

            DB::table('plans')->insert($payload + [
                'slug' => $data['slug'], 'version' => 1, 'created_at' => $now,
            ]);
        }

        DB::table('plan_settings')->updateOrInsert(
            ['key' => 'home_pricing'],
            ['value' => json_encode([
                'active_template' => 'vatan-proposal',
                'title' => 'پلن‌ها، بر پایه اعتبار دائمی',
                'notes' => [
                    'هر اعتبار همیشه در حساب شما می‌ماند؛ با کیفیت دلخواه خود می‌توانید بسازید.',
                    'کیفیت استاندارد، حرفه‌ای و بهترین خروجی به‌ترتیب ۱۲، ۲۰ و ۵۰ اعتبار برای هر ساخت نیاز دارند.',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'created_at' => $now, 'updated_at' => $now]
        );
    }

    public function down(): void
    {
        // حذف خودکار داده‌های زنده یا فعال‌سازی نسخه پیشین امن نیست.
    }
};
