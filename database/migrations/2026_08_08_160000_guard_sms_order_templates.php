<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sms_templates')) {
            return;
        }

        $now = now();
        $templates = [
            'purchase_success' => [
                'name' => 'ثبت سفارش ساخت تصویر',
                'body' => '{name} عزیز، سفارش {order_number} ثبت شد. موجودی: {balance} اعتبار.',
                'provider_template_id' => '504181',
                'provider_variables' => ['name', 'order_number', 'balance'],
                'is_active' => false,
                'is_default' => false,
                'provider_approval_status' => 'pending',
                'provider_note' => 'پیام شروع سفارش فعلاً ارسال نمی‌شود؛ فقط پیام تکمیل سفارش فعال بماند.',
            ],
            'order_completed' => [
                'name' => 'آماده شدن سفارش تصویر',
                'body' => '{name} عزیز، سفارش {order_number} آماده شد و اکنون قابل مشاهده است.',
                'provider_template_id' => '504179',
                'provider_variables' => ['name', 'order_number'],
                'is_active' => true,
                'is_default' => true,
                'provider_approval_status' => 'pending',
                'provider_note' => 'الگوی مخصوص تکمیل سفارش؛ نباید از قالب‌های پلن استفاده کند.',
            ],
        ];

        foreach ($templates as $eventKey => $template) {
            DB::table('sms_templates')->where('event_key', $eventKey)->update([
                'is_default' => false,
                'updated_at' => $now,
            ]);

            DB::table('sms_templates')->updateOrInsert(
                ['event_key' => $eventKey, 'provider_template_id' => $template['provider_template_id']],
                [
                    'name' => $template['name'],
                    'body' => $template['body'],
                    'provider_method' => 'shared',
                    'provider_variables' => json_encode($template['provider_variables'], JSON_UNESCAPED_UNICODE),
                    'provider_approval_status' => $template['provider_approval_status'],
                    'provider_note' => $template['provider_note'],
                    'provider_submitted_at' => null,
                    'provider_checked_at' => null,
                    'is_active' => $template['is_active'],
                    'is_default' => $template['is_default'],
                    'sent_count' => 0,
                    'last_sent_at' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('sms_templates')
            ->whereIn('event_key', ['purchase_success', 'order_completed'])
            ->whereIn('provider_template_id', ['506695', '506696'])
            ->update([
                'is_active' => false,
                'is_default' => false,
                'provider_note' => 'غیرفعال شد چون شناسه قالب متعلق به پیامک پلن است، نه سفارش تصویر.',
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        // جلوگیری از برگشت به نگاشت اشتباه پیامک‌ها.
    }
};
