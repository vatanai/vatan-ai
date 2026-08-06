<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->string('provider_method', 20)->default('simple')->after('body');
            $table->string('provider_template_id', 100)->nullable()->after('provider_method');
            $table->json('provider_variables')->nullable()->after('provider_template_id');
        });

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
        Schema::table('sms_templates', function (Blueprint $table) {
            $table->dropColumn(['provider_method', 'provider_template_id', 'provider_variables']);
        });
    }
};
