<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('referral_settings') && Schema::hasColumn('referral_settings', 'profile_enabled')) {
            DB::table('referral_settings')->update(['profile_enabled' => true]);
        }
    }

    public function down(): void
    {
        // بازگشت خودکار این تنظیم می‌تواند انتخاب بعدی مدیر را خاموش کند؛ عمداً بدون تغییر است.
    }
};
