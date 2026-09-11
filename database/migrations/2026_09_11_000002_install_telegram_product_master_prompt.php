<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_product_settings')) {
            return;
        }

        $legacyPrompt = 'تو یک ویراستار ارشد و متخصص سئوی محصولات تصویری هستی. متن مدیر را به داده‌ی دقیق و آماده‌ی ذخیره در پلتفرم وطن تبدیل کن. منظور مدیر را حفظ کن و اگر نام یا توضیح ناقص بود، با حداقل حدس منطقی کاملش کن. خروجی فقط JSON معتبر با کلیدهای name_fa، name_en، description_fa، description_en، category، tags و product_prompt باشد. نام فارسی کوتاه و حرفه‌ای، نام انگلیسی طبیعی، توضیح فارسی کاربردی و متقاعدکننده، توضیح انگلیسی دقیق، category فقط یکی از دسته‌های مجاز، tags حداکثر ۸ عبارت کوتاه و product_prompt یک پرامپت انگلیسی دقیق و آماده‌ی مدل تولید تصویر باشد. هیچ توضیحی خارج از JSON ننویس و به تصویر یا مسیر فایل اشاره نکن.';
        $masterPrompt = 'تو تحلیلگر ارشد محصول تصویری و متخصص آماده‌سازی محصولات پلتفرم وطن هستی. متن مدیر را کامل بررسی کن و از روی پرامپت، نام و توضیحات او اطلاعات دقیق و آماده‌ی ثبت محصول بساز. منظور مدیر را حفظ کن، از حدس بی‌دلیل پرهیز کن، نام فارسی و انگلیسی حرفه‌ای انتخاب کن، توضیح فارسی کاربردی بنویس، دسته‌بندی و برچسب‌های مرتبط را انتخاب کن، مشخص کن محصول چهره‌محور هست یا نه و یک پرامپت نهایی انگلیسی، دقیق و آماده‌ی بارگذاری تولید کن. اگر متن مدیر ناقص بود، فقط با حداقل اطلاعات منطقی آن را کامل کن. خروجی باید کوتاه، دقیق و قابل استفاده برای ثبت محصول باشد.';

        $setting = DB::table('telegram_product_settings')->where('key', 'metadata_prompt')->first();
        if (! $setting) {
            DB::table('telegram_product_settings')->insert([
                'key' => 'metadata_prompt',
                'value' => $masterPrompt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        // اگر مدیر قبلاً متن را شخصی‌سازی کرده، تنظیم او نباید با مهاجرت جایگزین شود.
        if (trim((string) $setting->value) === '' || trim((string) $setting->value) === $legacyPrompt) {
            DB::table('telegram_product_settings')->where('key', 'metadata_prompt')->update([
                'value' => $masterPrompt,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // تنظیمات سفارشی مدیر نباید در rollback حذف یا بازنویسی شود.
    }
};
