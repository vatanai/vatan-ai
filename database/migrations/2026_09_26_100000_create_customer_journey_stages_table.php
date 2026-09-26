<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_journey_stages', function (Blueprint $table): void {
            $table->unsignedTinyInteger('point')->primary();
            $table->string('short', 120);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('icon', 80)->default('fa-route');
            $table->string('task_title', 180);
            $table->text('task_body')->nullable();
            $table->string('channel', 30)->default('none');
            $table->text('message_template')->nullable();
            $table->unsignedInteger('delay_minutes')->default(0);
            $table->boolean('human_required')->default(false);
            $table->text('advance_rule')->nullable();
            $table->text('stop_rule')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('customer_journey_stages')->insert([
            ['point' => 1, 'short' => 'اولین تجربه', 'title' => 'کلیک روی لینک و ثبت‌نام در سایت', 'description' => 'کاربر وارد شده و حساب خود را ساخته اما هنوز خروجی نگرفته است.', 'icon' => 'fa-user-plus', 'task_title' => 'خوش‌آمدگویی و بررسی ورود', 'task_body' => 'ورود کاربر را بررسی کن و راهنمای کوتاه شروع کار را در اختیارش بگذار.', 'channel' => 'sms', 'message_template' => 'خوش آمدی؛ برای شروع اولین خروجی خودت را بساز.', 'delay_minutes' => 5, 'human_required' => false, 'advance_rule' => 'ثبت‌نام یا ورود موفق کاربر', 'stop_rule' => 'شماره یا اطلاعات تماس نامعتبر باشد.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 2, 'short' => 'ساخت اول', 'title' => 'اولین خروجی از سایت', 'description' => 'کاربر اولین عکس یا ویدیوی خود را ساخته و باید تجربه موفقش تثبیت شود.', 'icon' => 'fa-wand-magic-sparkles', 'task_title' => 'پیگیری اولین تجربه ساخت', 'task_body' => 'خروجی اول را بررسی کن و یک پیشنهاد ساده برای ساخت بعدی بده.', 'channel' => 'in_app', 'message_template' => 'اولین خروجی آماده است؛ اگر دوست داشتی یک نمونه دیگر هم بساز.', 'delay_minutes' => 180, 'human_required' => false, 'advance_rule' => 'حداقل یک خروجی معتبر ثبت شود.', 'stop_rule' => 'ساخت ناموفق یا نارضایتی کاربر ثبت شود.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 3, 'short' => 'ساخت دوم', 'title' => 'دومین خروجی از سایت', 'description' => 'کاربر برای بار دوم از محصول استفاده کرده و نشانه خوبی از تکرارپذیری دارد.', 'icon' => 'fa-repeat', 'task_title' => 'تقویت استفاده تکراری', 'task_body' => 'الگوی استفاده کاربر را ببین و قابلیت مرتبط بعدی را معرفی کن.', 'channel' => 'in_app', 'message_template' => 'حالا که ساخت دوم را تجربه کردی، قابلیت‌های بیشتر را امتحان کن.', 'delay_minutes' => 720, 'human_required' => false, 'advance_rule' => 'حداقل دو خروجی معتبر ثبت شود.', 'stop_rule' => 'کاربر در استفاده دوم متوقف یا ناراضی باشد.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 4, 'short' => 'استفاده مستمر', 'title' => 'تجربه ارزش و استفاده مستمر', 'description' => 'کاربر چند بار از محصول استفاده کرده و باید ارزش واقعی محصول را ببیند.', 'icon' => 'fa-chart-line', 'task_title' => 'نمایش ارزش محصول', 'task_body' => 'بهترین قابلیت مناسب این کاربر را معرفی کن و نتیجه استفاده را ثبت کن.', 'channel' => 'direct', 'message_template' => 'بر اساس استفاده‌ات، این قابلیت می‌تواند نتیجه بهتری برایت بسازد.', 'delay_minutes' => 1440, 'human_required' => false, 'advance_rule' => 'حداقل سه خروجی یا فعالیت تکرارشونده ثبت شود.', 'stop_rule' => 'کاربر چند روز غیرفعال بماند یا درخواست توقف بدهد.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 5, 'short' => 'آماده خرید', 'title' => 'آماده تصمیم برای خرید', 'description' => 'کاربر ارزش محصول را دیده و باید پیشنهاد خرید مناسب و شفاف دریافت کند.', 'icon' => 'fa-coins', 'task_title' => 'ارائه پیشنهاد خرید', 'task_body' => 'نیاز کاربر را بررسی کن، پلن مناسب را معرفی کن و مانع خرید را ثبت کن.', 'channel' => 'call', 'message_template' => 'اگر بخواهی استفاده‌ات را ادامه بدهی، این پلن برای نیاز تو مناسب‌تر است.', 'delay_minutes' => 1440, 'human_required' => true, 'advance_rule' => 'ثبت علاقه‌مندی یا شروع فرآیند خرید.', 'stop_rule' => 'پاسخ منفی قطعی یا عدم تمایل کاربر.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 6, 'short' => 'خرید اول', 'title' => 'اولین خرید از سایت', 'description' => 'اولین پرداخت موفق انجام شده و باید تجربه شروع مصرف بدون اصطکاک باشد.', 'icon' => 'fa-cart-shopping', 'task_title' => 'تأیید خرید و شروع مصرف', 'task_body' => 'خرید را بررسی کن و مطمئن شو کاربر می‌داند از اعتبار خود چطور استفاده کند.', 'channel' => 'sms', 'message_template' => 'خریدت با موفقیت انجام شد؛ راهنمای شروع مصرف را از اینجا ببین.', 'delay_minutes' => 30, 'human_required' => false, 'advance_rule' => 'اولین خرید با وضعیت تکمیل‌شده ثبت شود.', 'stop_rule' => 'پرداخت ناموفق یا درخواست بازگشت وجه.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 7, 'short' => 'مصرف موفق', 'title' => 'مصرف موفق خرید و رضایت', 'description' => 'کاربر خرید کرده و باید از خرید خود نتیجه بگیرد و رضایتش ثبت شود.', 'icon' => 'fa-face-smile', 'task_title' => 'پیگیری رضایت مشتری', 'task_body' => 'مصرف اعتبار و رضایت را بررسی کن و در صورت نیاز پشتیبانی انسانی بده.', 'channel' => 'direct', 'message_template' => 'نتیجه استفاده‌ات چطور بود؟ اگر کمکی لازم داری با ما در ارتباط باش.', 'delay_minutes' => 4320, 'human_required' => false, 'advance_rule' => 'پس از خرید حداقل یک استفاده یا تأیید رضایت ثبت شود.', 'stop_rule' => 'نارضایتی یا مشکل حل‌نشده ثبت شود.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['point' => 8, 'short' => 'خرید مجدد', 'title' => 'بازگشت و خرید مجدد', 'description' => 'کاربر سابقه خرید دارد و باید با پیشنهاد به‌موقع دوباره فعال شود.', 'icon' => 'fa-rotate', 'task_title' => 'پیگیری بازگشت مشتری', 'task_body' => 'زمان مناسب بازگشت را پیدا کن و پیشنهاد متناسب با سابقه کاربر بده.', 'channel' => 'call', 'message_template' => 'برای ادامه کار آماده‌ای؟ می‌توانیم پیشنهاد مناسب استفاده بعدی‌ات را آماده کنیم.', 'delay_minutes' => 10080, 'human_required' => true, 'advance_rule' => 'خرید دوم یا بیشتر با وضعیت تکمیل‌شده ثبت شود.', 'stop_rule' => 'کاربر درخواست عدم تماس یا توقف همکاری بدهد.', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_journey_stages');
    }
};
