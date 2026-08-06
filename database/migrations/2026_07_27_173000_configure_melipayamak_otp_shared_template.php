<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sms_templates') || !Schema::hasColumn('sms_templates', 'provider_template_id')) {
            return;
        }

        $now = now();
        DB::table('sms_templates')->updateOrInsert(
            ['event_key' => 'otp_code', 'name' => 'رمز یک‌بارمصرف وطن'],
            [
                'body' => 'کد تایید تلفن همراه در پلتفرم وطن: {code} می‌باشد.',
                'provider_method' => 'shared',
                'provider_template_id' => '502328',
                'provider_variables' => json_encode(['code'], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'is_default' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('sms_templates')
            ->where('event_key', 'otp_code')
            ->where('provider_template_id', '502328')
            ->delete();
    }
};
