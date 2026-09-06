<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_product_managers') && ! Schema::hasColumn('telegram_product_managers', 'permissions')) {
            Schema::table('telegram_product_managers', function (Blueprint $table): void {
                $table->json('permissions')->nullable()->after('metadata');
            });
        }

        if (! Schema::hasTable('telegram_product_settings')) {
            Schema::create('telegram_product_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        $defaults = [
            'metadata_prompt' => 'تو یک ویراستار ارشد و متخصص سئوی محصولات تصویری هستی. متن مدیر را به داده‌ی دقیق و آماده‌ی ذخیره در پلتفرم وطن تبدیل کن. منظور مدیر را حفظ کن و اگر نام یا توضیح ناقص بود، با حداقل حدس منطقی کاملش کن. خروجی فقط JSON معتبر با کلیدهای name_fa، name_en، description_fa، description_en، category، tags و product_prompt باشد. نام فارسی کوتاه و حرفه‌ای، نام انگلیسی طبیعی، توضیح فارسی کاربردی و متقاعدکننده، توضیح انگلیسی دقیق، category فقط یکی از دسته‌های مجاز، tags حداکثر ۸ عبارت کوتاه و product_prompt یک پرامپت انگلیسی دقیق و آماده‌ی مدل تولید تصویر باشد. هیچ توضیحی خارج از JSON ننویس و به تصویر یا مسیر فایل اشاره نکن.',
            'prompt_optimizer' => 'پرامپت محصول را برای تولید تصویر حرفه‌ای، دقیق و قابل تکرار بهینه کن. موضوع اصلی، سوژه، سبک، ترکیب‌بندی، نور، رنگ، جنس، زاویه دید و محدودیت‌های مهم را حفظ کن؛ ابهام، تکرار و عبارت‌های بی‌اثر را حذف کن و چیزی خلاف خواسته‌ی مدیر اضافه نکن. خروجی فقط متن پرامپت نهایی انگلیسی باشد و هیچ مقدمه یا نقل‌قولی نداشته باشد.',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('telegram_product_settings')->where('key', $key)->exists()) {
                DB::table('telegram_product_settings')->insert(['key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        if (Schema::hasTable('telegram_product_managers')) {
            DB::table('telegram_product_managers')->where('telegram_id', 217979733)->update([
                'permissions' => json_encode([
                    'create_product' => true,
                    'edit_product' => true,
                    'publish_product' => true,
                    'manage_prompts' => true,
                    'manage_bot_settings' => true,
                    'view_reports' => true,
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('telegram_product_registrations')) {
            Schema::create('telegram_product_registrations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->unique()->constrained('products')->cascadeOnDelete();
                $table->foreignId('telegram_product_manager_id')->nullable()->constrained('telegram_product_managers')->nullOnDelete();
                $table->uuid('draft_id')->nullable();
                $table->unsignedBigInteger('telegram_id')->nullable()->index();
                $table->longText('input_prompt')->nullable();
                $table->json('ai_result')->nullable();
                $table->string('status', 30)->default('draft');
                $table->timestamps();
                $table->index(['telegram_product_manager_id', 'created_at'], 'telegram_product_registrations_manager_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_product_registrations');
        Schema::dropIfExists('telegram_product_settings');
        if (Schema::hasTable('telegram_product_managers') && Schema::hasColumn('telegram_product_managers', 'permissions')) {
            Schema::table('telegram_product_managers', fn (Blueprint $table) => $table->dropColumn('permissions'));
        }
    }
};
