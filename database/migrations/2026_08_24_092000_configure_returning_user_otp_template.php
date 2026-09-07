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
        DB::table('sms_templates')->where('event_key', 'login_otp')->update(['is_default' => false]);
        DB::table('sms_templates')->updateOrInsert(
            ['event_key' => 'login_otp', 'provider_template_id' => '523374'],
            [
                'name' => 'کد ورود کاربران بازگشتی وطن',
                'body' => "سلام {name} عزیز،\n\nخوش اومدی به وطن 🌱\n\nکد ورودت: {code}\n\nپلتفرم وطن",
                'provider_method' => 'shared',
                'provider_variables' => json_encode(['name', 'code'], JSON_UNESCAPED_UNICODE),
                'provider_approval_status' => 'approved',
                'provider_note' => 'تأییدشده در پنل ملی‌پیامک با کد متن 523374',
                'provider_submitted_at' => null,
                'provider_checked_at' => $now,
                'is_active' => true,
                'is_default' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        if (Schema::hasTable('sms_templates')) {
            DB::table('sms_templates')
                ->where('event_key', 'login_otp')
                ->where('provider_template_id', '523374')
                ->delete();
        }
    }
};
