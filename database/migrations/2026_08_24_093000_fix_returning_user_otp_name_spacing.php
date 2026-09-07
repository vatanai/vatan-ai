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

        DB::table('sms_templates')
            ->where('event_key', 'login_otp')
            ->where('provider_template_id', '523374')
            ->update([
                'body' => "سلام {name} عزیز،\n\nخوش اومدی به وطن 🌱\n\nکد ورودت: {code}\n\nپلتفرم وطن",
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('sms_templates')) {
            return;
        }

        DB::table('sms_templates')
            ->where('event_key', 'login_otp')
            ->where('provider_template_id', '523374')
            ->update([
                'body' => "سلام {name}عزیز،\n\nخوش اومدی به وطن 🌱\n\nکد ورودت: {code}\n\nپلتفرم وطن",
                'updated_at' => now(),
            ]);
    }
};
