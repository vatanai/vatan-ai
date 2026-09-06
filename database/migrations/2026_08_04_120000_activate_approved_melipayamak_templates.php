<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_templates')) {
            return;
        }

        $now = now();
        $templates = [
            'otp_code' => [
                'name' => 'رمز یک‌بارمصرف ورود پلتفرم وطن',
                'body' => 'سلام فقط یک قدم تا رسیدن به وطن مونده کد ورود: {code} می باشد. پلتفرم وطن',
                'body_id' => '506694',
                'variables' => ['code'],
            ],
            'registration_success' => [
                'name' => 'خوش‌آمدگویی اولین ورود',
                'body' => '{name} جان از اینکه به وطن خودت رسیدی خوشحالیم... برای شروع، حسابت رو با چند توکن هدیه شارژ کردیم؛ حالا وقتشه ایده‌هات رو بسازی. وطن استودیو',
                'body_id' => '506692',
                'variables' => ['name'],
            ],
            'plan_purchase_success' => [
                'name' => 'خرید موفق پلن',
                'body' => '{name} عزیز پلن {plan_name} در وطن برای تو فعال شد مسیر بازه؛ حالا دیگه با خیال راحت ایده‌هات رو خلق کن پلتفرم وطن',
                'body_id' => '506695',
                'variables' => ['name', 'plan_name'],
            ],
            'plan_purchase_failed' => [
                'name' => 'خرید ناموفق پلن',
                'body' => '{name} جان پرداخت پلن {plan_name} کامل نشد نگران نباش؛ می‌تونی دوباره به وطن برگردی و پرداختت رو ادامه بدی پلتفرم وطن',
                'body_id' => '506696',
                'variables' => ['name', 'plan_name'],
            ],
        ];

        foreach ($templates as $eventKey => $template) {
            DB::table('sms_templates')->where('event_key', $eventKey)->update(['is_default' => false]);

            DB::table('sms_templates')->updateOrInsert(
                ['event_key' => $eventKey, 'name' => $template['name']],
                [
                    'body' => $template['body'],
                    'provider_method' => 'shared',
                    'provider_template_id' => $template['body_id'],
                    'provider_variables' => json_encode($template['variables'], JSON_UNESCAPED_UNICODE),
                    'provider_approval_status' => 'approved',
                    'provider_note' => 'تأییدشده در پنل ملی‌پیامک',
                    'provider_submitted_at' => null,
                    'provider_checked_at' => $now,
                    'is_active' => true,
                    'is_default' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        if (Schema::hasTable('referral_settings') && Schema::hasColumn('referral_settings', 'registration_sms_enabled')) {
            DB::table('referral_settings')->update(['registration_sms_enabled' => true]);
        }
    }

    public function down(): void
    {
        DB::table('sms_templates')
            ->whereIn('provider_template_id', ['506692', '506694', '506695', '506696'])
            ->delete();
    }
};
