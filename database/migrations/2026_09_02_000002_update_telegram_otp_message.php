<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('telegram_bot_contents')) {
            return;
        }

        DB::table('telegram_bot_contents')
            ->where('content_key', 'registration_otp')
            ->update([
                'body' => 'کد تأیید برای شماره‌ات پیامک شد؛ همان کد را همین‌جا بفرست تا حساب وطن برایت آماده شود.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('telegram_bot_contents')) {
            return;
        }

        DB::table('telegram_bot_contents')
            ->where('content_key', 'registration_otp')
            ->update([
                'body' => 'کد تأیید پیامک‌شده را بفرست تا حساب وطن برایت آماده شود.',
                'updated_at' => now(),
            ]);
    }
};
