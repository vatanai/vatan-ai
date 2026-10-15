<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductShot;
use App\Models\ShotLibrary;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ۱۰ محصول پروداکتی نمونه با حالت‌های مختلف — فقط برای دیدن بازطراحی در لوکال.
 * عکس‌ها در storage/app/public/demo-productshots (gitignore؛ روی سرور نمی‌رود).
 *
 * اجرا:   php artisan db:seed --class=ProductShotDemoSeeder
 * تکرار اجرا امن است؛ نمونه‌های قبلی (اسلاگ demo-ps-*) پاک و دوباره ساخته می‌شوند.
 */
class ProductShotDemoSeeder extends Seeder
{
    private const IMG = 'demo-productshots/d%02d.webp';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('این سیدر فقط برای لوکال است.');
            return;
        }

        $library = ShotLibrary::query()->where('is_active', true)->orderBy('sort')->orderBy('id')->get()->values();
        if ($library->isEmpty()) {
            $this->command?->error('کتابخانه‌ی شات خالی است؛ اول php artisan migrate را اجرا کنید.');
            return;
        }

        Product::withoutEvents(function () use ($library): void {
            DB::transaction(function () use ($library): void {
                $old = Product::withTrashed()->where('slug', 'like', 'demo-ps-%')->pluck('id');
                ProductShot::query()->whereIn('product_id', $old)->delete();
                Product::withTrashed()->whereIn('id', $old)->forceDelete();

                foreach ($this->scenarios() as $i => $s) {
                $product = new Product();
                $product->forceFill($this->baseAttributes() + [
                    'name_fa' => $s['name'],
                    'name_en' => 'Demo product shot ' . ($i + 1),
                    'slug' => 'demo-ps-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'description_fa' => $s['desc'],
                    'status' => 'active',
                    // نمونه‌ها باید در همه‌ی فیدهای اپ، از جمله ترندز، قابل مشاهده باشند.
                    'is_trending' => true,
                    'is_featured' => true,
                    'cover' => sprintf(self::IMG, $s['cover']),
                    'thumbnail' => sprintf(self::IMG, $s['cover']),
                    'product_code' => Product::generateUniqueProductCode(),
                    'base_likes_count' => random_int(120, 250),
                ]);
                $product->product_mode = Product::MODE_PRODUCT;
                    $product->save();

                    foreach ($s['shots'] as $sort => $shot) {
                    $lib = $library[($i * 3 + $sort) % $library->count()];
                    $config = isset($shot['q']) ? ['quality_credits' => array_combine(['standard', 'professional', 'best'], $shot['q'])] : null;
                    ProductShot::query()->create([
                        'product_id' => $product->id,
                        'shot_id' => $lib->id,
                        'name_override' => $shot['name'] ?? null,
                        'enabled' => true,
                        'is_default' => $shot['default'] ?? true,
                        'credits_override' => $shot['credits'] ?? null,
                        'sample_image' => isset($shot['img']) ? sprintf(self::IMG, $shot['img']) : null,
                        'sort' => $sort,
                        'model_configuration' => $config,
                        'allowed_aspect_ratios' => $shot['ratios'] ?? ['4:5'],
                        'aspect_ratio_default' => ($shot['ratios'] ?? ['4:5'])[0],
                        'aspect_ratio_user_selectable' => count($shot['ratios'] ?? []) > 1,
                    ]);
                    }
                }
            });
        });

        $this->command?->info('۱۰ محصول پروداکتی نمونه ساخته شد → /app/ps-v2');
    }

    /** ستون‌های قدیمی/اجباری؛ همان مقادیر امن فرم ثبت محصول پروداکتی. */
    private function baseAttributes(): array
    {
        return [
            'category' => 'کسب‌وکار',
            'prompt_template' => 'Product shot pack (see shot library).',
            'subject_type' => 'product',
            'identity_preservation' => false,
            'min_reference_images' => 1,
            'max_reference_images' => 3,
            'pricing_model' => 'per_credit',
            'media_type' => 'photo',
            'output_type' => 'image',
            'output_format' => 'png',
            'output_count' => 1,
            'allowed_aspect_ratios' => ['4:5', '1:1', '9:16'],
            'aspect_ratio' => '4:5',
            'resolution' => '1080',
            'timeout' => 180,
            'estimated_time' => 45,
            'primary_model' => '',
            'ai_provider' => 'openrouter',
            'fallback_models' => [],
            'fallback_model_providers' => [],
            'tags' => ['کسب‌وکار', 'نمونه'],
            'card_label' => 'پک',
            'card_label_enabled' => true,
            'display_mode' => 'card',
            'card_shape' => 'portrait',
            'gallery_layout' => 'slider',
            'explore_tiles' => ['1x1'],
            'shot_settings' => ['niche' => 'other'],
        ];
    }

    private function scenarios(): array
    {
        $q = fn (int $a, int $b, int $c) => [$a, $b, $c];

        return [
            // ۱) پک کامل: ۸ شات، ۴ پیشنهادی، نسبت‌های قابل انتخاب
            ['name' => 'پک کامل عطر لوکس', 'cover' => 7, 'desc' => 'یک عکس از شیشه‌ی عطرت بده؛ هشت صحنه‌ی تبلیغاتی از نمای اصلی تا لایف‌استایل تحویل بگیر.', 'shots' => [
                ['img' => 7, 'name' => 'نمای اصلی', 'ratios' => ['4:5', '1:1', '9:16']],
                ['img' => 2, 'name' => 'لایف‌استایل طبیعت'],
                ['img' => 4, 'name' => 'روی سنگ مرمر', 'ratios' => ['1:1', '4:5']],
                ['img' => 3, 'name' => 'نور نرم استودیو'],
                ['img' => 8, 'name' => 'زیر آب', 'default' => false],
                ['img' => 11, 'name' => 'پالت بنفش', 'default' => false],
                ['img' => 5, 'name' => 'دست مدل', 'default' => false, 'ratios' => ['9:16', '4:5']],
                ['img' => 13, 'name' => 'استوری عمودی', 'default' => false, 'ratios' => ['9:16']],
            ]],
            // ۲) پک کوچک: فقط ۲ شات
            ['name' => 'پک سرم پوست', 'cover' => 4, 'desc' => 'دو عکس تمیز و روشن برای صفحه‌ی محصول فروشگاهت.', 'shots' => [
                ['img' => 4, 'name' => 'پس‌زمینه‌ی سفید'],
                ['img' => 2, 'name' => 'کنار گیاه'],
            ]],
            // ۳) فقط یک شات
            ['name' => 'عکس تکی کفش اسپرت', 'cover' => 6, 'desc' => 'یک عکس قهرمان برای کاور کاتالوگ.', 'shots' => [
                ['img' => 6, 'name' => 'قهرمان کاتالوگ', 'credits' => 18],
            ]],
            // ۴) شات‌های بدون عکس نمونه (حالت جایگزین)
            ['name' => 'پک جواهرات (بدون نمونه)', 'cover' => 1, 'desc' => 'برای دیدن حالتی که بعضی شات‌ها هنوز عکس نمونه ندارند.', 'shots' => [
                ['img' => 1, 'name' => 'نمای ماکرو'],
                ['name' => 'روی پارچه‌ی مخمل'],
                ['name' => 'در دست مدل'],
                ['img' => 5, 'name' => 'گوشواره روی گوش', 'default' => false],
            ]],
            // ۵) قیمت متفاوت برای هر کیفیت
            ['name' => 'پک ژل شستشو با سه سطح کیفیت', 'cover' => 9, 'desc' => 'اعتبار هر شات در کیفیت استاندارد، حرفه‌ای و بهترین خروجی متفاوت است.', 'shots' => [
                ['img' => 9, 'name' => 'آشپزخانه‌ی روشن', 'q' => $q(8, 14, 24)],
                ['img' => 8, 'name' => 'قطرات آب', 'q' => $q(10, 16, 28)],
                ['img' => 3, 'name' => 'مینیمال سفید', 'q' => $q(6, 12, 20)],
            ]],
            // ۶) نسبت‌های مختلف قابل انتخاب
            ['name' => 'پک شبکه‌های اجتماعی رژلب', 'cover' => 11, 'desc' => 'پست مربعی، پرتره و استوری؛ نسبت هر شات را خودت انتخاب کن.', 'shots' => [
                ['img' => 11, 'name' => 'پست اینستاگرام', 'ratios' => ['1:1', '4:5']],
                ['img' => 10, 'name' => 'استوری', 'ratios' => ['9:16', '4:5', '1:1']],
                ['img' => 12, 'name' => 'بنر فروشگاه', 'ratios' => ['4:5', '1:1']],
            ]],
            // ۷) نام و توضیح خیلی طولانی
            ['name' => 'پک حرفه‌ای عکاسی تبلیغاتی محصولات مراقبت پوست و موی ارگانیک برای فروشگاه‌های اینترنتی', 'cover' => 3, 'desc' => 'این پک برای برندهایی ساخته شده که می‌خواهند کل کاتالوگ محصولاتشان یک‌دست و حرفه‌ای دیده شود. از یک عکس ساده با موبایل شروع کن؛ ما نور، صحنه، سایه و ترکیب‌بندی را مثل یک عکاسی استودیویی واقعی می‌سازیم. شکل بطری، رنگ مایع، برچسب و جنس درپوش دقیق حفظ می‌شود تا مشتری همان چیزی را ببیند که دریافت می‌کند. خروجی‌ها برای صفحه‌ی محصول، اینستاگرام و بنر تبلیغاتی آماده‌اند.', 'shots' => [
                ['img' => 3, 'name' => 'نمای اصلی با نور پنجره و سایه‌ی برگ'],
                ['img' => 2, 'name' => 'روی سنگ‌های رودخانه'],
                ['img' => 4, 'name' => 'پس‌زمینه‌ی سفید فروشگاهی'],
                ['img' => 9, 'name' => 'آشپزخانه'],
                ['img' => 8, 'name' => 'زیر آب'],
            ]],
            // ۸) بدون توضیح
            ['name' => 'پک ساعت و گجت', 'cover' => 12, 'desc' => null, 'shots' => [
                ['img' => 12, 'name' => 'گرید چهارتایی'],
                ['img' => 7, 'name' => 'تاریک و لوکس'],
                ['img' => 6, 'name' => 'روی میز چوبی'],
            ]],
            // ۹) هیچ شات پیشنهادی ندارد (۴ شات اول خودکار انتخاب می‌شوند)
            ['name' => 'پک ضدتعریق (بدون پیشنهادی)', 'cover' => 8, 'desc' => 'برای دیدن حالتی که ادمین هیچ شاتی را پیش‌فرض نکرده است.', 'shots' => [
                ['img' => 8, 'name' => 'حباب‌ها', 'default' => false],
                ['img' => 2, 'name' => 'جنگل', 'default' => false],
                ['img' => 4, 'name' => 'سفید', 'default' => false],
                ['img' => 3, 'name' => 'نور گرم', 'default' => false],
                ['img' => 13, 'name' => 'پالت سرد', 'default' => false],
                ['img' => 9, 'name' => 'آشپزخانه', 'default' => false],
            ]],
            // ۱۰) پک پرهزینه با نسبت ثابت
            ['name' => 'پک پریمیوم کرم دست', 'cover' => 13, 'desc' => 'سه صحنه‌ی سینمایی با بهترین مدل؛ هر عکس با نسبت ثابت ۴:۵.', 'shots' => [
                ['img' => 13, 'name' => 'گل‌های بهاری', 'q' => $q(30, 45, 60)],
                ['img' => 10, 'name' => 'شب و نور آبی', 'q' => $q(30, 45, 60)],
                ['img' => 5, 'name' => 'دست مدل', 'q' => $q(30, 45, 60)],
            ]],
        ];
    }
}
