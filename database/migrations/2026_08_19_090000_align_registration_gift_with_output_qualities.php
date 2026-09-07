<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('referral_settings')) {
            return;
        }

        // ۴۰ اعتبار با نرخ‌های ۱۲ و ۲۰، دقیقاً سه ساخت استاندارد یا دو ساخت حرفه‌ای می‌دهد.
        DB::table('referral_settings')->update([
            'registration_gift_tokens' => 40,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // مقدار قبلی می‌تواند توسط مدیر تغییر کرده باشد؛ بازگشت خودکار انجام نمی‌شود.
    }
};
