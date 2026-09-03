<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('studio_pricing_settings')) {
            return;
        }

        DB::table('studio_pricing_settings')->updateOrInsert(
            ['id' => 1],
            [
                'image_profit_percent' => 10,
                'video_profit_percent' => 10,
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        // درصد فعلی کسب‌وکار با rollback به مقدار قبلی برنمی‌گردد؛ مقدار جدید
        // باید فقط از پنل مدیریت و به‌صورت آگاهانه تغییر کند.
    }
};
